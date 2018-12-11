<?php

/*
 * Use for chunked file upload before ?update
 *
 * @see  blueimp https://github.com/blueimp/jQuery-File-Upload , https://blueimp.net
 */

$fileuploadDir = Files::makeTempFolder('fileupload');

# Blox CMS settings
$options = [
    'script_url' => '?fileupload',
    'upload_dir' => $fileuploadDir.'/', # Put trailing slash!
    'upload_url' => Blox::info('site','url').'/temp/fileupload/', # Put trailing slash!
    ##'max_number_of_files' => 2,
    'image_versions' => [], # no thumbnail
];
require(Blox::info('cms','dir').'/vendor/blueimp/jQuery-File-Upload/server/php/UploadHandler.php');
$upload_handler = new UploadHandler($options);