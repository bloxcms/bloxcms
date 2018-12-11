<?php
//return;
    $type = "destination('xfiles') maxWidth(942) maxHeight(1200) quality(50)";// stamp('stamp.png','bottom-right', 20, 10, 10)
    # See all settings http://bloxcms.net/documentation/file.htm

    $typeParams = Tdd::getTypeParams($type);

    # $dstDir = 'xfiles'; # You can specify it in $type
    # For text-editor-file-upload.php
    if (empty($dstDir)) {
        $dst = $typeParams['destination'][0] ? $typeParams['destination'][0] : 'xfiles';
        $dstDir = Blox::info('site','dir').'/'.$dst;
        $dstUrl = Blox::info('site','url').'/'.$dst;
    }
    #
    if (file_exists($dstDir) && is_dir($dstDir)) {
        $uploadedFileInfo = $_FILES['upload'];
        $srcFileName = $uploadedFileInfo['name'];
        if ($srcFileName) {
            if ($uploadedFileInfo['error']===0) {
                $tmpSrcFile = $uploadedFileInfo['tmp_name'];
                if ($placedFileName = Upload::getPlacedFileName('', '', '', '', $srcFileName, $tmpSrcFile, $typeParams, $dstDir))
                    $placedFileUrl = $dstUrl.'/'.$placedFileName;
                else
                    $error = $terms['file-upload-failed']; 
            } else
                Blox::prompt(Upload::getUploadErrorDescription($uploadedFileInfo['error']),  true);
        }
    } else {
        $error = $terms['no-folder-to-upload-file'];
    }
    $callback = (int)$_GET['CKEditorFuncNum'];
    echo"<script type=\"text/javascript\">window.parent.CKEDITOR.tools.callFunction(".$callback.",\"".$placedFileUrl."\", \"".$error."\" );</script>";
