<?php

Class Files
{
    /**
     * Create a temporary directory to work with temporary files (such as files uploaded by users). Folder older than 10 days will be removed.
     *
     * @param string $folderName Name of the new folder that wil be created inside the site folder "temp". If you want to create folders in deeper level, use a relative path, for example: 'myUploads/images'.
     * @return string Absolute path to the folder
     */
    public static function makeTempFolder($folderName)
    {
        $lifetime = 864000; # ten days: 10*24*60*60
        $tempDir = Blox::info('site','dir').'/temp/'.$folderName;
        # Garbage collection
        $files = glob(Blox::info('site','dir').'/temp/*');
        foreach ($files as $fl) {
            if (is_dir($fl)){
                $dirTime = filemtime($fl);# if you delete a file the time changes
                if (($dirTime + $lifetime) < time())
                    self::deleteDir($fl); }
            else
                unlink($fl); 
        }
        if (self::makeDirIfNotExists($tempDir, 0777))
            return $tempDir;
    }


    public static function getTempFolderDir($folderName)
    {
        $aa = Blox::info('site','dir').'/temp/'.$folderName;
        if (is_dir($aa))
            return $aa;
        else 
            false;
    }
    
    
    # Recursively
    public static function makeDirIfNotExists($dr, $mode=0755)
    {
        # Check if path exist
        if (is_dir($dr) || file_exists($dr))
            return true;
        if (mkdir($dr, $mode, true)) {
            chmod($dr, $mode); # If you cannot chmod files/directories with PHP because of safe_mode restrictions, but you can use FTP to chmod them, simply use PHP's FTP-functions (eg. ftp_chmod or ftp_site) instead. Not as efficient, but works.
            return true;
        } else {
            Blox::prompt(Blox::getTerms('folder-not-created').' <b>'.$dr.'</b>',  true);
            return false;
        }
    }


    
   /**
    * Copies file and automaticly creates all direcrories of the path. If same file exists, than new file will be renamed
    *
    * @param string $srcFile Absolute path
    * @param string $dstFile Absolute path
    * @param array $options
    *
    * @return new file name or false
    */
    public static function smartCopy($srcFile, $dstFile, $options=[])
    {
        if ($options)
            Arr::formatOptions($options);
        # Defaults
        $options += ['move'=>false, 'dst-dir'=>null, 'dst-file-name'=>null, 'dst-dir-mode'=>0755, 'dst-file-mode'=>null];
        
        if ($options['dst-dir'] && $options['dst-file-name']) {
            $dstDir = $options['dst-dir'];
            $dstFilename = $options['dst-file-name'];
        } else {
            $dstFile = str_replace('\\', '/', $dstFile);
            $aa = Str::splitByMark($dstFile, '/', true);
            $dstDir = $options['dst-dir'] ?: $aa[0];
            $dstFilename = $options['dst-file-name'] ?: $aa[1];
        }
        $dstFilename = Upload::reduceFileName($dstFilename, $dstDir);
        $dstFile = $dstDir.'/'.$dstFilename;

        # A nice simple trick if you need to make sure the folder exists first:
        # TODO: Redo on makeDirIfNotExists() ??
        $aa = dirname($dstFile);
        mkdir($aa, $options['dst-dir-mode'], true);
        chmod($aa, $options['dst-dir-mode']); # Because mkdir() does not work sometimes

        $func = $options['move'] ? 'rename' : 'copy';
        if ($func($srcFile, $dstFile)) {
            if ($options['dst-file-mode'])
                chmod($dstFile, $options['dst-file-mode']);
            return $dstFilename;
        }
        return false;
    }
    
    
    
    public static function cleanDir($dr)
    {
        $files = glob($dr.'/*');
        foreach( $files as $fl ){
            if (is_dir($fl)){
                self::deleteDir($fl);
            } else {
                if (!unlink($fl))
                    Blox::prompt(Blox::getTerms('clean-dir-cannot-delete-file').' '.$fl, true);
            }
        }
    }



    public static function deleteDir($dr)
    {
        $files = glob( $dr . '/*');
        foreach( $files as $fl ){
            if (is_dir($fl)) {
                if (!self::deleteDir($fl))
                    Blox::prompt(Blox::getTerms('remove-dir-cannot-delete-folder').' '.$fl, true);
            } else {
                if (!unlink($fl))
                    Blox::prompt(Blox::getTerms('remove-dir-cannot-delete-file').' '.$fl, true);
            }
        }
        if (is_dir($dr)) {
            if (!rmdir($dr))
                Blox::prompt(Blox::getTerms('remove-dir-cannot-delete-folder').' '.$fl, true);
        }
    }
    


    /**
     * Recursive analog of glob(), Does not support flag GLOB_BRACE
     *
     * @example recursiveGlob(Blox::info('templates', 'dir').'/*.tpl')
     * 
     */
    public static function recursiveGlob($pattern, $flags = 0)
    {
        if ($files = glob($pattern, $flags)) {
            # Get an array of folders to search inside folders
            if ($aa = glob(dirname($pattern).'/*', GLOB_ONLYDIR|GLOB_NOSORT)) {                
                # Looking for files inside folders
                foreach ($aa as $d) {
                    if ($bb = self::recursiveGlob($d.'/'.basename($pattern)))
                        $files = array_merge($files, $bb);}
            }
            return $files;
        }
    }


    /**
     * equivalent of scandir() PHP5
     */
    public static function readBaseNames($folder, $ext)
    {
        if ($handle = opendir($folder)){
            while (false !== ($fl = readdir($handle))){
                if ($fl != '.' && $fl != '..'){
                    if ($ext){
                        if (preg_match("/\.$ext$/i", $fl))
                            $baseNames[] = preg_replace("/\.$ext/iu", "", $fl); # without extension
                    }
                }
            }
            closedir($handle);
            sort($baseNames, SORT_NATURAL);
            return $baseNames;
        }
    }


    /** 
     * Converts the name of the template to normal form, that is, relative paths are relative to the regular folder "templates"
     *
     * @param string $tpl - template name to be normalized
     * @param string $parentNormalizedTpl - normalized template name (path) of parent template
     * @return string 
     *
     * @example
     *   $parentNormalizedTpl = 'folderA/tpl1';
     *   $tpl = 'tpl2';          returns: 'folderA/tpl2'
     *   $tpl = 'folderB/tpl2;   returns: 'folderA/folderB/tpl2'
     *   $tpl = '../tpl2;        returns: 'tpl2'
     *   $tpl = '/folderB/tpl2;  returns: 'folderB/tpl2'
     */
    public static function normalizeTpl($tpl, $parentNormalizedTpl=null)
    {
        if ($tpl) {
            if ($parentNormalizedTpl) {
                $baseAbsDir = '/'.Str::getStringBeforeMark($parentNormalizedTpl, '/', true);
                $aa = Files::convertToAbsolute($tpl, $baseAbsDir);
                return substr($aa, 1); # removes '/'
            } elseif ('/'==$tpl[0]) { # absolute
                return substr($tpl, 1); # template base relative
            }
        }
        return false;
    }

  
    

    /**
     * @param string $path Relative or dirty absolute path. For Unix files and dirs '/','./','../'
     */
    public static function convertToAbsolute($path, $baseAbsDir=null)
    {
        if ($path{0} == '/') # If path is absolute
            return $path;
        # not absolute
        else {
            if (empty($baseAbsDir))
                return false;
            # Convert path to the dirty absolute path
            if ($baseAbsDir === '/')
                $baseAbsDir = '';
            $dirtyPath = $baseAbsDir.'/'.$path;//http://www.php.net/manual/en/function.realpath.php
            # canonize
            return (function($dirtyPath){
                $out=[];
                $pieces = explode('/', $dirtyPath);
                foreach ($pieces as $i => $folder){                    
                    if ($folder == '' || $folder == '.') # Double slash or current folder
                        continue;
                    if ($folder == '..' && $i > 0 && end($out) != '..')
                        array_pop($out); # To go to the next level up, i.e. delete the current $folder
                    else
                        $out[]= $folder;
                }
                $canonPath = '/'; # see: if ($i == 0 && $folder == '')
                $canonPath .= implode('/', $out);
                $canonPath .= (empty($folder)) ? '/' : ''; # Restore trailing slash
                return $canonPath;
            })($dirtyPath);
            //return $canonize($dirtyPath);
        }
    }

    
    
    /**
     * Convert absolute path to relative. Does not checks if file or directory exists.
     *
     * @param string $absFile - abs. path to file or dir. Keeps trailing slash in return
     * @param string $baseDir - abs. dir from witch the path is built (with or without trailing slash)
     * @return string Path to file or dir 
     */
    public static function convertToRelative($absFile, $baseAbsDir)
    {
        # Because of Windows
        $baseAbsDir = str_replace('\\', '/', $baseAbsDir);
        $absFile   = str_replace('\\', '/', $absFile);
        
        $baseAbsDirParts     = explode('/', $baseAbsDir);
        $fileParts       = explode('/', $absFile);
        $remFileParts  = $fileParts;

        foreach ($baseAbsDirParts as $ser => $part) {
            # find first non-matching dir
            if ($part === $fileParts[$ser])
                array_shift($remFileParts); # ignore this directory
            else{                
                $numOfRemParts = count($baseAbsDirParts) - $ser;# get number of remaining dirs to $baseAbsDir
                # add traversals up to first matching dir
                if ($numOfRemParts > 1) {                    
                    $padLength = (count($remFileParts) + $numOfRemParts - 1) * -1;
                    $remFileParts = array_pad($remFileParts, $padLength, '..');
                    break;
                }
            }
        }
        return implode('/', $remFileParts);
    }
    
    




    /**
     * Delete the file. In the the future this method will register files for backup.
     * 
     * @param string $fl File path
     * @param mixed $delDirs Array of folders that can be deleted if they become empty. For one folder you can use a string. For all empty folders use the value: true.
     */
    public static function unLink($fl, $delDirs=null)
    {
        if (unlink($fl))
        {
            if ($delDirs)
            {
                $dr = dirname($fl);
                if ($delDirs === true)
                    $try = true;
                elseif (is_array($delDirs)) {
                    if (in_array($dr, $delDirs))
                        $try = true;
                }
                elseif ($dr != $delDirs) # One dir
                    $try = true;

                if ($try) {
                    # Removes empty dir and its subdirs
                    if (!self::removeEmptyDir($dr))
                        ;#self::prompt('Could not delete folder '.$dr, true);
                }
            }
            return true;
        } else {
            return false;
        }
    }

    
    private static function removeEmptyDir($dr)
    {
          $isEmpty = true;
          foreach (glob($dr."/*") as $fl)
             $isEmpty &= is_dir($fl) && self::removeEmptyDir($fl);
          return $isEmpty && rmdir($dr);##
    }




    /**
     * Turn file name (without extension) into unique one for the specified directory.
     * If file with the name $name and extension $ext already exists in the directory, a hyphen and an incremental number will be added to the name ("-1").
     *
     * @param string $dst Directory to put new file
     * @param string $name File name without extension
     * @param string $ext File's extension
     * @return string The root of the new file
     */
    public static function uniquizeFilename($dst, $name, $ext)
    {
        $fileExists = function($fl) {# case insensitive file_exists()
            if (file_exists($fl))
                return true;
            if ($files = glob(dirname($fl) . '/*')) {
                $lowerFl = mb_strtolower($fl);
                foreach($files as $f)
                    if (mb_strtolower($f) == $lowerFl)
                        return true;
            }
            return false;
        };
        #
        $vacant = false;
        do {
            $checkedFilesRoots = [];
            if ($fileExists($dst.'/'.$name.'.'.$ext)) {
                if (in_array($name, $checkedFilesRoots))# Infinite lopp protection
                    $name = Str::genRandomString(8);
                $checkedFilesRoots[] = $name;
                # '-' exist
                if (list($head, $tail) = Str::splitByMark($name, '-', true)) {
                    if (preg_match('~^\d+$~', $tail)) { # digit one or more times in the end
                        $tail++;
                        $name = $head."-".$tail;
                    } else
                        $name = $name."-1";
                } else
                    $name = $name."-1";
            } else
                $vacant = true;
        }
        while (!$vacant);
        #
        return $name;
    }
            

    
    
    /**
     * Tested, not used. From http://stackoverflow.com/questions/1833518/remove-empty-subfolders-with-php
     * Removes empty dir and its subdirs
     * USE
     *   $path ='aa';
     *   echo removeEmptyDirs($path);
     * See also Files::unLink($fl, $excludeDirs);
    function removeEmptyDirs($path)
    {
      $isEmpty = true;
      foreach (glob($path."/*") as $fl)
         $isEmpty &= is_dir($fl) && removeEmptyDirs($fl);
      return $isEmpty && rmdir($path);
    }
    */
    
    


    /**
     * Tested, not used.
     * Check or set file mode 
     *
     * @param string $fl
     * @param octal $newMode Example 0755
     * @return string Mode for file or folder
    function verifyFileMode($fl, $newMode=null)
    {
        if (!file_exists($fl))
            return false;
    	# Check
        $oldMode = substr(sprintf('%o', fileperms($fl)), -4);
        if (empty($oldMode))
            return false;
        elseif (empty($newMode))
            return $oldMode;
        elseif ($oldMode != $newMode){
            if (chmod($fl, $newMode)) # Attempt to set the mode
            	return $newMode;
            else {
                Blox::error("Failed on chmod($fl, $newMode)");
                return $oldMode;}
        }
    }
    */    
}


