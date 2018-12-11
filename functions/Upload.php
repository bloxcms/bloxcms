<?php


/*
 * @todo Use the Report class instead of the $reports variables
 * @todo If there is no parent fields's file than search for parend parent. If specified parent field without file, take its processed file, not uploaded
 */

use Exception;

class Upload
{
    private static $uploadTempDir;


    /*
     * Convert a superglobal $_FILES to "human form", keeping senior array keys of form names
     * Suitable for scalar form names and for one and two dimensional form names.
     *
     * @param array $superfiles Superglobal $_FILES
     * @param mixed $items
     * @return array
     *
     * @todo Do recursively to process arrays of dimension 3 or more.
     * @example 
     *      $normalizedFiles = Upload::format($_FILES);
     *      Form                                    Input                                           Output
     *      <input type="file" name="var">          $_FILES['var'][param] = value               $normalizedFiles['var'][param] = value   (same)
     *      <input type="file" name="var[k1]">      $_FILES['var'][param]['k1'] = value         $normalizedFiles['var']['k1'][param] = value
     *      <input type="file" name="var[k1][k2]">  $_FILES['var'][param]['k1']['k2'] = value   $normalizedFiles['var']['k1']['k2'][param] = value
     */
    public static function format($superfiles)
    {
        if (!$superfiles)
            return;
        foreach ($superfiles as $inputName => $params) {
            foreach ($params as $param => $value) {
                if (is_scalar($value))
                    $normalizedFiles[$inputName][$param] = $value;
                else {
                    foreach ($value as $k1=>$v1) {
                        if (is_scalar($v1))
                            $normalizedFiles[$inputName][$k1][$param] = $v1;
                        else {
                            foreach ($v1 as $k2=>$v2)
                                $normalizedFiles[$inputName][$k1][$k2][$param] = $v2;
                        }
                    }
                }
            }
        }
        return $normalizedFiles;
    }
    
    
    




    /*
     * Universal function for uploading files.
     * The file will be renamed (latin transliteration, additional suffix (if such file already exists), etc.
     *
     * @param array $normalizedFiles Array obtained via Upload::format($_FILES). The dimension of form names is 0,1,2.
     * @param string $dstDir Directory for uploaded files
     * @param bool $unzip The file will be automatically unzipped. The archive should contain only one file.
     * @return array Returns an array same as form names. The value is the final name of the file saved in the specified folder. 
     *
     * @todo Error report. For scripts/site-settings-update.php
     * @todo Use sys_get_temp_dir() instead of Files::makeTempFolder()
     */
    public static function uploadFiles($normalizedFiles, $dstDir, $unzip=null)
    {
        # Temporary staging folder uploadFile
        self::$uploadTempDir = Files::makeTempFolder('blox-uploads');
        # If the folder does not exist
        Files::makeDirIfNotExists($dstDir);
        # Check if the array of dimensions = 1 exists
        $isVector = function($arr){
            if (is_scalar(reset($arr)))
                return true;
        };
        foreach ($normalizedFiles as $varName => $fileParams) {
            if ($isVector($fileParams))
                $uploadedFiles[$varName] = self::uploadFile($fileParams, $dstDir, $reports, $unzip);
            else { # is matrix
                foreach ($fileParams as $k1 => $v1)
                {
                    if ($isVector($v1)) {
                        $uploadedFiles[$varName][$k1] = self::uploadFile($v1, $dstDir, $reports, $unzip);
                    } else {
                        foreach ($v1 as $k2=>$v2) {
                            if ($isVector($v2))
                                $uploadedFiles[$varName][$k1][$k2] = self::uploadFile($v2, $dstDir, $reports, $unzip);
                            else
                                Blox::prompt(Blox::getTerms('3d-params-not-allowed'), true);
                        }
                    }
                }
            }
        }
        return $uploadedFiles;
    }


    # Upload one file to the specified folder. Need a finite one-dimensional array upload-parameters of the file, which when downloading does not happen-the array there is wrong
    # Returns the actual name of the recorded file
    private static function uploadFile($fileParams, $dstDir, &$reports=[], $unzip=null)
    {
        if ($fileParams['name']) {
            if ($fileParams['error']===0) { # A file of this field has been uploaded
                if ($unzip && $fileParams['type'] == 'application/zip') {
                    $zip = new ZipArchive;
                    if ($zip->open($fileParams['tmp_name']) === true) {
                        if ($zip->numFiles == 1)
                            $fileName = $zip->getNameIndex(0);
                        else
                            $reports[] = 'moreThanOneFileInZip';
                        $renamedFileName = self::reduceFileName($fileName, $dstDir);

                        # You will have to first unzip it to a temp folder, rename unzipped file prior to writing it to the folder
                        # not works $zip->extractTo($dstDir, 'newname.txt')
                        # not works $zip->renameName($fileName,'newname.txt'); (directly to the zip)
                        if ($renamedFileName != $fileName){
                            if ($zip->extractTo(self::$uploadTempDir)) {
                                rename(self::$uploadTempDir.'/'.$fileName, $dstDir.'/'.$renamedFileName);
                                $fileUploadedAndUnzipped = true;
                            }
                        } elseif ($zip->extractTo($dstDir)) # Unzip immediately to destination
                                $fileUploadedAndUnzipped = true;
                        #
                        if ($fileUploadedAndUnzipped) {
                            chmod($dstDir.'/'.$renamedFileName, 0644);
                            $reports[] = 'file-uploaded-and-unzipped';
                            return $renamedFileName;
                        } else
                            $reports[] = 'could-not-unzip-uploaded-file';//red
                        $zip->close();
                    }
                    else
                        $reports[] = 'could-not-unzip-uploaded-file'; // red
                }
                # Move file $_FILES['']['tmp_name'] to $dstDir renaming
                else { # Not zipped. See line by line above
                    $renamedFileName = self::reduceFileName($fileParams['name'], $dstDir);

                    if (move_uploaded_file($fileParams['tmp_name'], "$dstDir/$renamedFileName")) {
                        chmod("$dstDir/$renamedFileName", 0644);
                        $reports[] = 'file-uploaded';

                        return $renamedFileName;
                    } else {
                        $reports[] = 'could-not-move-uploaded-file';//red
                    }
                }
            } else {
                $reports[] = "uploadError{$fileParams['error']}";//red
            }
        }
        else
            $reports[] = 'no-file-to-upload';//red
    }



    
    /*
     * Update regular Blox data of "file" type
     * 
     * @param string $tbl Data table name. Example: '$shop/catalog/goods/photos'
     * @param array $normalizedFiles Array obtained via Upload::format($_FILES)
     * @param array $wdata. Example: ['rec-id'=>180, 'block-id'=>377]
     * @param array $typesDetails. Array obtained via Tdd::getTypesDetailsByColumns() or Tdd::getTypesDetails() 
     * @return bool
     *
     * @todo Use Upload::uploadFile()
     */
    public static function updateFiles($tbl, $normalizedFiles, $wdata, $typesDetails=null)
    {
        $oldData = Data::get($tbl, $wdata);        
        foreach ($typesDetails as $col => $bb)
        {
            $typeParams = $bb['params'];
            if (isset($typeParams['file'])) # Ensure that it is file type
            {
                $dst = ($typeParams['destination'][0]) ? $typeParams['destination'][0] : 'datafiles';
                $dstDir = Blox::info('site','dir').'/'.$dst;
                Files::makeDirIfNotExists($dstDir);
                if ($normalizedFiles[$col]['name']) {
                    if ($normalizedFiles[$col]['error']===0 && Upload::fileExists($normalizedFiles[$col]['tmp_name'])) {
                        $result = self::getPlacedFileName($tbl, $oldData, $wdata, $col, $normalizedFiles[$col]['name'], $normalizedFiles[$col]['tmp_name'], $typeParams, $dstDir);
                        if ($result !== true) { # fileTypeIsAllowed
                            $uploadedData[$col] = $result;
                            $dstDirs[$col] = $dstDir; # Save to use for the later file fields
                        }
                    } else {
                        Blox::prompt(self::getUploadErrorDescription($normalizedFiles[$col]['error']),  true);
                        continue;
                    }
                }
                # File for this field was not uploaded at all, but this field uses a file of an earlier processed field
                elseif ($sourceCol = $typeParams['sourcefield'][0]) {
                    if ($uploadedData[$sourceCol]) {
                        if ($normalizedFiles[$sourceCol]) { # Parent file is realy uploaded file. Use this raw file, but not processed file.
                            if ($normalizedFiles[$sourceCol]['error']===0 && Upload::fileExists($normalizedFiles[$sourceCol]['tmp_name'])) {
                                $result = self::getPlacedFileName($tbl, $oldData, $wdata, $col, $uploadedData[$sourceCol], $normalizedFiles[$sourceCol]['tmp_name'], $typeParams, $dstDir);
                                if ($result !== true) { # fileTypeIsAllowed
                                    $uploadedData[$col] = $result;
                                    $dstDirs[$col] = $dstDir; # Save to use for the later file fields
                                }
                            } else
                                continue;
                        } else { # Parent file  is not uploaded. It is obtained from another parent file 
                            if ($srcFile = $dstDirs[$sourceCol].'/'.$uploadedData[$sourceCol]) {
                                $result = self::getPlacedFileName($tbl, $oldData, $wdata, $col, $uploadedData[$sourceCol], $srcFile, $typeParams, $dstDir);
                                if ($result !== true) { # fileTypeIsAllowed
                                    $uploadedData[$col] = $result;
                                    $dstDirs[$col] = $dstDir; # Save to use for the later file fields
                                }
                            }
                                
                        }
                    } else
                        continue;
                }
            } else
                Blox::error("Column $col has not file type");
        }

        
        if ($uploadedData && $wdata) {
            #TODO: you can put $data into global and update the record along with all fields.
            if (Data::update($tbl, $uploadedData, $wdata))
                return true; #v.13.0.13
            else
                return false; #v.13.0.13
        }
        return true; #v.13.0.13  KLUDGE
    }


    # file or URL Exists and Valid
    public static function fileExists($fl)
    {
        $r = false;
        if (file_exists($fl))
            $r = true;
        elseif (Url::exists($fl))
            $r = true;
        return $r;
    }



    # It is also used in text-editor-file-upload.php
    public static function getPlacedFileName($tbl, $oldData, $wdata, $col, $srcFileName, $tmpSrcFile, $typeParams, $dstDir) # $dstDir=null
    {
        if ($oldFile = $oldData[$col]) {
            # First delete the old file to avoid overlap of names
            Files::unLink($dstDir.'/'.$oldFile); # Do not use second param!
            if (Sql::tableExists(Blox::info('db','prefix').'countupdates')) {
                $sql = 'DELETE FROM '.Blox::info('db','prefix')."countdownloads WHERE obj=?";
                Sql::query($sql, [$oldFile]);
            }
        }

        $convertedFileName = self::getConvertedFileName($tbl, $oldData, $wdata, $col, $tmpSrcFile, $srcFileName, $typeParams, $dstDir);

        if ($convertedFileName) {
            #the result is already written to the datafiles folder
            return $convertedFileName;
        } elseif (false === $convertedFileName)
            return false; # An error occurred while processing image
        else {
            # SIMILAR: below
            # Use one of the template text fields as the file name (for SEO)
            if ($typeParams['renamefilebyfield'][0] && empty($typeParams['sourcefield'][0]) && $oldData['dat'.$typeParams['renamefilebyfield'][0]]) {
                $nameLength = $typeParams['renamefilebyfield'][1] ? $typeParams['renamefilebyfield'][1] : 120;
                # at 130, 135 the picture is not displayed!
                if ($aa = Text::truncate(Text::stripTags($oldData['dat'.$typeParams['renamefilebyfield'][0]],'strip-quotes'), $nameLength, ['plain'])) {
                    $aa = preg_replace("~\.+$~u", '', $aa); # Remove dots in the end
                    if ($suffix = Str::getStringAfterMark($srcFileName, '.', true)){
                        $bb = $aa.'.'.$suffix;
                        Blox::prompt(sprintf($terms['file-renamed'], '<b>'.$srcFileName.'</b>', '<b>'.$bb.'</b>'));
                        $srcFileName = $bb;
                    }
                }
            }
            Files::makeDirIfNotExists($dstDir);
            if ($renamedFileName = self::reduceFileName($srcFileName, $dstDir, $typeParams)) {
                if (isset($_GET['insert-data-from-file-batches'])) {
                    if (copy($tmpSrcFile, $dstDir.'/'.$renamedFileName)) {
                        chmod($dstDir.'/'.$renamedFileName, 0644);
                        return $renamedFileName;
                    }
                } else {
                    if (copy($tmpSrcFile, $dstDir.'/'.$renamedFileName)) {
                        chmod($dstDir.'/'.$renamedFileName, 0644);
                        return $renamedFileName;
                    } else
                        Blox::error('Function updateDataFiles.php. Cannot copy file', true);
                }
            }
        }
    }

   /**
    * 
    * @todo 
    *   Replace self::calculateDimensions by $img->bestFit() and do all object-wise without calculating width and height.
    *   or do with http://image.intervention.io/
    * 
    * 
    * Matches the file to the descriptor settings
    * If the file had to be converted, a new file is created immediately at the destination $convertedFileName.
    */
    private static function getConvertedFileName($tbl, $oldData, $wdata, $col, $tmpSrcFile, $srcFileName, $typeParams, $dstDir)
    {   #TODO If you upload a file with the same name, size, and date, use the old one, and do not upload this one

        # KLUDGE. The SimpleImage class is used, in which the size analysis is already incorporated, but while the analysis is carried out in the old way, through f-Yu calculateDimensions().
        # TODO: Trim an image using PHP and GD  http://stackoverflow.com/questions/1669683/crop-whitespace-from-image-in-php ,   http://zavaboy.com/2007/10/06/trim_an_image_using_php_and_gd , 
        
        if (empty($typeParams))
            return 0;

        $terms = Blox::getTerms();

        unset($typeParams['file']);
        
        list($root, $oldSuffix) = Str::splitByMark($srcFileName, '.', true);
        $oldSuffix = mb_strtolower($oldSuffix);
        $suffix = $oldSuffix; # KLUDGE: $oldSuffix - for $mode=='fit'
        if (isset($typeParams['allowedformats'])) {
            if (!in_array($suffix, $typeParams['allowedformats'])) {
                Blox::prompt(sprintf($terms['file-format-is-not-allowed'], "<b>$srcFileName</b>", $col, $tbl), true);
                return true;
            }
        }

        if (isset($typeParams['forbiddenformats'])) {
            if (in_array($suffix, $typeParams['forbiddenformats'])) {
                Blox::prompt(sprintf($terms['file-format-is-forbidden'], "<b>$srcFileName</b>", $col, $tbl), true);
                return true;
            }
        }
        
        /** TODO: use this 
        http://stackoverflow.com/questions/10662915/how-can-i-check-whether-or-not-a-file-is-an-image
        http://www.binarytides.com/php-check-if-file-is-an-image/
        http://php.net/manual/ru/function.exif-imagetype.php
        http://php.net/manual/ru/function.finfo-open.php  xxx
        */        
        $getFileType = function($suffix) {
            switch ($suffix) {# use mb_strtolower() before
                case 'gif': return 'image';
                case 'jpg': return 'image';
                case 'jpeg':return 'image';
                case 'png': return 'image';
            }
        };

        if ('image' == $getFileType($suffix))
        {
            require_once Blox::info('cms','dir')."/vendor/claviska/simpleimage/src/claviska/SimpleImage.php";
            try
            {
                $img = new \claviska\SimpleImage();
                $img->fromFile($tmpSrcFile);
                
                # sizes of uploaded image
                $width = $img->getWidth();
                $height = $img->getHeight();

                if ($mode = $typeParams['thumbnail'][0]) {
                    if ($typeParams['thumbnail'][1]) {
                        if ($mode == 'crop') {
                            $ww = $typeParams['thumbnail'][1];
                            $hh = $typeParams['thumbnail'][2] ? : $ww;
                            if (!($ww==$width && $hh==$height)) {
                                $width = $ww;
                                $height = $hh;
                                $toCrop = true;
                            }
                        } elseif ($mode == 'fit') {
                            $fitWidth = $typeParams['thumbnail'][1];
                            if (Str::isInteger($typeParams['thumbnail'][2]))
                                $fitHeight = $typeParams['thumbnail'][2];
                            else # no more params
                                $fitHeight = $fitWidth;

                            if (!($fitWidth==$width && $fitHeight==$height)) {
                                self::calculateDimensions($width, $height, $fitWidth, $fitHeight, 'enlarge'); # Image is reduced to the limits but it will be extended to final fit mode sizes in the end.
                                $toFit = true; # In any case
                            }
                        }
                    }
                } else {
                    $toResize = self::calculateDimensions($width, $height, $typeParams['maxwidth'][0], $typeParams['maxheight'][0]);
                }
                #
                if ($typeParams['format'][0]) {
                    $s = mb_strtolower($typeParams['format'][0]);
                    if ($suffix!==$s || $toFit)
                        $newSuffix = $s;
                }
                if ($suffix == 'jpeg')
                    $suffix = 'jpg';

                if ($newSuffix)
                    $suffix = $newSuffix;
                
                # Now $width, $height are sizes of new or temp image
                # image width and height store fields
                if ($typeParams['widthfield'][0])
                    $data[$typeParams['widthfield'][0]] = $width;
                if ($typeParams['heightfield'][0])
                    $data[$typeParams['heightfield'][0]] = $height;

                if ($data && $wdata) {
                    Data::update($tbl, $data, $wdata); # you can put $data into global and update the record along with all fields.
                }

                if ($typeParams['stamp'][0] && file_exists($typeParams['stamp'][0]))
                    $toStamp = true;
                
                if (isset($typeParams['quality'][0]))
                    $quality = (int)$typeParams['quality'][0];
                
                if ($quality == 100)
                    $quality = null;

                if ($toResize || $toCrop || $toFit || $newSuffix || $toStamp || null !== $quality)
                {
                    if (null === $quality)
                        $quality = 50;  # Default SimpleImageclass quality is 100. Minimum acceptable quality of GD is 30
                        
                    # For GD is usually not enough memory to process images with large sizes (2000x2000rx)
                    $maxMemoryLimit = 256;
                    if (preg_match("/(\d+?)(\D+?)/", ini_get('memory_limit'), $matches)) {
                        if ($matches[1] < $maxMemoryLimit) {
                            $aa = $matches[1] * 3;
                            if ($aa > $maxMemoryLimit)
                                $aa = $maxMemoryLimit;
                            ini_set('memory_limit', $aa.$matches[2]);
                        }
                    }

                    ##$img->autoOrient(); // New method is not tested. In v2 $img->auto_orient() causes deformation of vertical images from smartphones. Solution: http://stackoverflow.com/questions/23420548/well-this-is-new-uploading-photos-from-smartphones-not-possible
                    if ($toCrop) {
                        if ($thumbPlacement = $typeParams['thumbnail'][3]) {
                            $thumbPlacement = trim($thumbPlacement);
                            if (strpos($thumbPlacement, '-')) # NOT DEPRECATED!
                                $thumbPlacement = str_replace('-',' ',mb_strtolower($thumbPlacement)); # in the class Simple Image hyphen is not used - you need to remove it. #center|top|left|bottom|right|top left|top right|bottom left|bottom right
                        } else
                            $thumbPlacement = null;
                        $img->thumbnail($width, $height, $thumbPlacement);
                    } else { # including fit mode
                        $img->resize($width, $height);
                    }

                    # stamp \\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
                    if ($toStamp)
                    {
                        $stampOpacity = 1;
                        $field = (int) str_replace ('dat', '', $col);
                        if ($typeParams['stamp'][1]) { # There are other parameters of the stamp, written in tdd
                            
                            # Since v 13.1.1 appeared new 6-th option option: opacity (only in .tdd)
                            # In version 14.0 it will be the third option
                            $stampOpacity = $typeParams['stamp'][5] ?: 1;
                            
                            if ($aa = explode(' ', $typeParams['stamp'][1])) {
                                foreach ($aa as $bb) {
                                    if ($bb = mb_strtolower(trim($bb))) { # mb_strtolower - need?
                                        if ($bb == 'stretch') {
                                            $stampOptions['stretch'] = true;
                                            break; 
                                        } else
                                            $stampOptions['placements'][$bb] = true;
                                    }
                                }

                                if (empty($stampOptions['stretch'])) {
                                    $stampOptions['scale'] = isset($typeParams['stamp'][2]) ? $typeParams['stamp'][2] : 50;
                                    $stampOptions['indents']['horizontal'] = isset($typeParams['stamp'][3]) ? $typeParams['stamp'][3] : 0;
                                    $stampOptions['indents']['vertical'] = isset($typeParams['stamp'][4]) ? $typeParams['stamp'][4] : 0;
                                }
                            }
                        }
                        elseif ($_POST['settings']['stamps'][$field]) { # settings in the edit window
                            $stampOptions = $_POST['settings']['stamps'][$field];
                        }
                        else { # Multi mode -stamp is not configurable and settings are not transferred
                            
                            $z = Str::getStringAfterMark($tbl, Blox::info('db','prefix'));
                            $xprefix = Str::getStringBeforeMark($z, '$'); //$classifieds/photos
                                
                            $srcBlockId = $oldData['block-id'];
                            $stampOptions = Store::get($xprefix.'editSettings'.$srcBlockId)['stamps'][$field];
                        }

                        if ($stampOptions['stretch']) {
                            $overlayImg = new \claviska\SimpleImage($typeParams['stamp'][0]);
                            $overlayImg->resize($width, $height);
                            $img->overlay($overlayImg, 'center', $stampOpacity);
                        } else {
                            $aa = getimagesize($typeParams['stamp'][0]);
                            $stampWidth = $aa[0];
                            $stampHeight = $aa[1];

                            ### scale ###
                            $newStampWidth  = Math::divideIntegers($width * $stampOptions['scale'], 100, $aa);
                            $newStampHeight = Math::divideIntegers($newStampWidth * $stampHeight, $stampWidth, $aa);

                            ### placement ###
                            /*
                            $indents['horizontal'] = Math::divideIntegers($stampOptions['indents']['horizontal'] * $width,  100, $aa);
                            $indents['vertical']   = Math::divideIntegers($stampOptions['indents']['vertical']   * $height, 100, $aa);
                            */
                            $xDisplacement = Math::divideIntegers($stampOptions['indents']['horizontal'] * $width,  100, $aa);
                            $yDisplacement = Math::divideIntegers($stampOptions['indents']['vertical']   * $height, 100, $aa);

                            foreach ($stampOptions['placements'] as $k => $v)
                            {
                                # TODO: remake the overlay() instead of this
                                if ($v) {
                                    if ($xDisplacement || $yDisplacement) {
                                		switch ($k) {
                                			case 'top-left':
                                                $xDisplacement2 = $xDisplacement;
                                                $yDisplacement2 = $yDisplacement;
                                				break;
                                			case 'top-right':
                                                $xDisplacement2 = 0 - $xDisplacement;
                                                $yDisplacement2 = $yDisplacement;
                                				break;
                                			case 'top':
                                                $xDisplacement2 = 0;
                                                $yDisplacement2 = $yDisplacement;
                                				break;
                                			case 'bottom-left':
                                                $xDisplacement2 = $xDisplacement;
                                                $yDisplacement2 = 0 - $yDisplacement;
                                				break;
                                			case 'bottom-right':
                                                $xDisplacement2 = 0 - $xDisplacement;
                                                $yDisplacement2 = 0 - $yDisplacement;
                                				break;
                                			case 'bottom':
                                                $xDisplacement2 = 0;
                                                $yDisplacement2 = 0 - $yDisplacement;
                                				break;
                                			case 'left':
                                                $xDisplacement2 = $xDisplacement;
                                                $yDisplacement2 = 0;
                                				break;
                                			case 'right':
                                                $xDisplacement2 = 0 - $xDisplacement;
                                                $yDisplacement2 = 0;
                                				break;
                                			case 'center':
                                                $xDisplacement2 = 0;
                                                $yDisplacement2 = 0;
                                				break;
                                            default:
                                                $xDisplacement2 = 0;
                                                $yDisplacement2 = 0;
                                		}
                                    }
                                    $kk = str_replace('-',' ',$k); # in the SimpleImage class, the hyphen is not used - you need to remove it. #center|top|left|bottom|right|top left|top right|bottom left|bottom right
                                    $overlayImg = new \claviska\SimpleImage($typeParams['stamp'][0]);
                                    $overlayImg->resize($newStampWidth, $newStampHeight);
                                    $img->overlay($overlayImg, $kk, $stampOpacity, $xDisplacement2, $yDisplacement2);
                                }
                            }
                        }
                    }
                    #/stamp ///////////////////////////////////////////////////////////////////////////////////

                    # SIMILAR: above
                    # Use one of the template text fields as the file name (for SEO)
                    if (
                        $typeParams['renamefilebyfield'][0] && 
                        !$typeParams['sourcefield'][0] && 
                        $oldData['dat'.$typeParams['renamefilebyfield'][0]]
                    ) {
                        $nameLength = $typeParams['renamefilebyfield'][1] ? $typeParams['renamefilebyfield'][1] : 120;
                        if ($aa = Text::truncate(Text::stripTags($oldData['dat'.$typeParams['renamefilebyfield'][0]],'strip-quotes'), $nameLength, ['plain'])) {
                            Blox::prompt(sprintf($terms['file-renamed'], '<b>'.$root.$suffix.'</b>', '<b>'.$aa.$suffix.'</b>'));
                            $root = $aa;
                        }
                    }

                    if ($toFit) {
                        $xImg = new \claviska\SimpleImage();
                        # fit mode Background
                        if ($typeParams['thumbnail'][4])
                            $fitBackground = mb_strtolower(trim($typeParams['thumbnail'][4]));
                        if (!$fitBackground) {
                            if ($newSuffix && 'png' != $newSuffix) # jpg
                               $fitBackground = '#fff';
                            else
                               $fitBackground = 'transparent'; # transparent
                        }
                        $xImg->fromNew($fitWidth, $fitHeight, $fitBackground);
                        
                        # fit mode placement
                        if ($typeParams['thumbnail'][3])
                            $fitPlacement = str_replace('-',' ',mb_strtolower(trim($typeParams['thumbnail'][3]))); #SIMILAR in the SimpleImage class the hyphen is not used - you need to remove it. #center|top|left|bottom|right|top left|top right|bottom left|bottom right
                        if (!$fitPlacement)
                            $fitPlacement = 'center';
                        $xImg->overlay($img, $fitPlacement, 1);
                        if (!$newSuffix)
                            $suffix = 'png';
                        $convertedFileName = self::reduceFileName($root.'.'.$suffix, $dstDir, $typeParams);
                        $xImg->toFile($dstDir.'/'.$convertedFileName, null, $quality);
                    } else {
                        $convertedFileName = self::reduceFileName($root.'.'.$suffix, $dstDir, $typeParams);
                        $img->toFile($dstDir.'/'.$convertedFileName, null, $quality);
                    }
                    chmod("$dstDir/$convertedFileName", 0644);
                    return $convertedFileName;
                }
            } catch (Exception $e) {
            	Blox::prompt($e->getMessage(),  true);
                Blox::error($e->getMessage());
                return false;
            }

        }
        
    } // end of getConvertedFileName()






   /**
    * @param int $width Initial and final width
    * @param int $height Initial and final height
    * @param int $maxWidth Limit width
    * @param int $maxHeight Limit height
    *
    * @return bool True if $width and $height are changed
    */
    private static function calculateDimensions(&$width, &$height, $maxwidth, $maxheight, $option='')
    {
        if (!$maxwidth && !$maxheight)
            return false;
        if ($maxwidth && $maxheight) {
            if (!self::bestFit($width, $height, $maxwidth, $maxheight, $option))
                return false;
        } elseif ($maxwidth) { # width only
            if (!self::bestFit($width, $height, $maxwidth, $height))
                return false;
        } elseif ($maxheight) { # Height only
            if (!self::bestFit($width, $height, $width, $maxheight))
                return false;
        }

        $width  = round($width);
        $height = round($height);
        return true;
    }



    private static function bestFit(&$width, &$height, $maxwidth, $maxheight, $option='')
    {
        if (!($maxwidth && $maxheight && $maxwidth && $maxheight)) {
            $width  = 0; 
            $height = 0;
            Blox::prompt(sprintf(Blox::getTerms('no-arg'), $width.', '.$height.', '.$maxwidth.', '.$maxheight), true);
            return false;
        }
        $origratio = $height / $width;
        
        if ($width <= $maxwidth && $height <= $maxheight) {
            if ('enlarge' == $option) {
                # Step 1 - temporarily fit the width
                $newwidth = $maxwidth;
                $newheight = $newwidth * $origratio;
                # Step 2 - fit all
                if ($newheight > $maxheight) {
                    $newheight = $maxheight;
                    $newwidth = $newheight / $origratio;
                }
            } else 
                return true;
        } else {
            # Step 1 - temporarily fit the width
            if ($width > $maxwidth) {
                $newwidth = $maxwidth;
                $newheight = $newwidth * $origratio;
            } else {
                $newwidth = $width;
                $newheight = $height;
            }
            # Step 2 - fit all
            if ($newheight > $maxheight) {
                $newheight = $maxheight;
                $newwidth = $newheight / $origratio;
            }
        }
        $width = round($newwidth);
        $height = round($newheight);
        return true;
    }



    public static function getUploadErrorDescription($errorNum)
    {
        switch ($errorNum) 
        {
            case 1: return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
            case 2: return 'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form';
            case 3: return 'The uploaded file was only partially uploaded';
            case 4: return 'No file was uploaded';
            case 6: return 'Missing a temporary folder';
            case 7: return 'Failed to write file to disk';
            default: return 'Unknown File while uploading';
        }
    }





   /**
    * Reduce the file name to latin format and make it unique. 
    *
    * @param string $fileName
    * @param string $dstDir Directory in which to check the existance of the file with same name. If such a file exists, add the suffix "-{number}"
    *
    * @return string
    */
    public static function reduceFileName($fileName, $dstDir=null, $typeParams=null)
    {
        list($root, $suffix) = Str::splitByMark($fileName, '.', true);
        # Ban executable files
        if (in_array($suffix, ['c','cgi','exe','htm','html','inc','php','php3','php4','phtml','pl','shtml'])) {
            $deny = true;
            if (isset($typeParams['allowedformats']))
                if (in_array($suffix, $typeParams['allowedformats']))
                    $deny = false;
            if ($deny) {
                Blox::prompt(sprintf(Blox::getTerms('unallowed-suffix'), $fileName), true);
                return false;
            }
        }
        $allowedChars = "a-zA-Z0-9_.-";//metacharacters:  []()#\|^$*+?
        if (preg_match("/[^".$allowedChars."]/", $root)) # Unallowed chars
        {
            $root = Str::transliterate($root) ;
            $root = str_replace(" ", "-", $root); # Replace spaces by "-" 
            $root = preg_replace("/-+/u", "-", $root); # Remove double "-"
            $root = preg_replace("/[^".$allowedChars."]/u", "", $root); # Remove unallowed chars
            $root = substr_replace($root , "", 120); # At 130 image is not displayed (tested jpg files). But most of OS allow 255 chars!
            if (!$root)
                $root = Str::genRandomString(8); # TODO: Use date-time?
        }
        
        $root = Files::uniquizeFilename($dstDir, $root, $suffix);
        $reducedFilename = $root.'.'.$suffix;
        if ($fileName != $reducedFilename)
            Blox::prompt(sprintf(Blox::getTerms('file-is-renamed'), '<b>'.$fileName.'</b>', '<b>'.$reducedFilename.'</b>'));
        return $reducedFilename;
    }


} #/class Upload