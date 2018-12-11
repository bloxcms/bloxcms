<?php
/** 
 * @see Settings in config.js 
 */
if (!preg_match("/MSIE [56789]/", $_SERVER['HTTP_USER_AGENT'])) # since in IE CKEditor does not work
    Blox::addToFoot(Blox::info('cms','url').'/vendor/ckeditor/ckeditor.js');
    //Blox::addToHead(Blox::info('cms','url').'/vendor/ckeditor/ckeditor.js');
    
