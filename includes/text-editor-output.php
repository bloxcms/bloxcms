<?php 
# Textarea
if (!preg_match("/MSIE [56789]/", $_SERVER['HTTP_USER_AGENT']))
    $ieStyle = ' style="background:#fff" class="ckeditor"'; # @todo Do not this and leave the class as is
echo'
<textarea name="dat['.$field.']" id="dat['.$field.']" rows="10"'.$ieStyle.'>'.$dat[$field].'</textarea>';
//qq($fields);
# To upload images
if (!$fields[$field]['no-text-editor-file-upload'])
    Blox::addToFoot('<script type="text/javascript">CKEDITOR.replace("dat['.$field.']", {filebrowserUploadUrl: "?text-editor-file-upload"});</script>', ['after'=>'ckeditor.js']);