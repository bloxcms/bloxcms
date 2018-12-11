<!DOCTYPE html>
<html lang="<?=Blox::info('site','lang')?>">
<head><?php
if ($z = Blox::info('site','extra-codes','head')) // !Blox::info('user','user-is-admin') && !Blox::info('user','user-is-editor') && 
    echo"\n".$z;
if (Blox::info('site','base-url') !== false)
    echo"\n".'<base href="'.Blox::info('site','base-url').'" />';?>
<?php if ($pageInfo['title']) {
    $title = array_key_exists('block', $pageInfo) ? $pageInfo['pseudo-pages-title-prefix'] : '';
    $title .= Blox::getTitlePrependix();
    $title .= $pageInfo['title'];
    $title .= Blox::getTitleAppendix();
    echo"\n".'<title>'.$title.'</title>';
}?>
<meta charset="utf-8" />
<meta http-equiv="X-UA-Compatible" content="IE=edge">
<meta name="keywords" content="<?=Text::stripTags($pageInfo['keywords'], ['strip-quotes'=>true])?>" /><?php /** @todo Remove Text::stripTags() from Keywords and Description only in new sites from version 14 */ ?>
<meta name="description" content="<?=Text::stripTags($pageInfo['description'], ['strip-quotes'=>true])?>" />
<meta name="generator" content="<?=Blox::getVersion()?>" /><?php
if (empty($GLOBALS['allow-skype'])) {
echo '
<meta name = "format-detection" content = "telephone=no" />'; # For iPad
echo '
<meta name="SKYPE_TOOLBAR" content="SKYPE_TOOLBAR_PARSER_COMPATIBLE" />
<style>span.skype_pnh_print_container {display:inline !important;}span.skype_pnh_container {display:none !important;}</style>';
}
if ($faviconFile) {
echo'
<link href="'.$faviconFile.'" rel="shortcut icon" type="image/x-icon" />
<link href="'.$faviconFile.'" rel="icon" type="image/x-icon" />';
}
echo'
<script>var Blox={};</script>';
if ($GLOBALS['Blox']['ajax'])
    Blox::addToFoot(Blox::info('cms','url').'/assets/blox.ajax.js');
if ($textEditorDir)
    include $textEditorDir.'/text-editor-link.php';
if ($cmsPublicStyled) {
    Blox::addToHead(Blox::info('cms','url').'/assets/blox.public.css');
    Blox::addToHead(Blox::info('cms','url').'/assets/blox.dropdown-menu.css');
    Blox::addToHead(Blox::info('cms','url').'/assets/blox.select-menu.css');
    Blox::addToFoot(Blox::info('cms','url').'/assets/blox.public.js', ['position'=>'top', 'after'=>'jquery-1']);
}
if ($cmsPrivateStyled) {
    Blox::addToHead(Blox::info('cms','url').'/assets/blox.private.css');
    Blox::addToHead(Blox::info('cms','url').'/vendor/fancybox/jquery.fancybox.css');
    Blox::addToFoot(Blox::info('cms','url').'/vendor/fancybox/jquery.fancybox.js');
    Blox::addToFoot(Blox::info('cms','url').'/vendor/fancybox/jquery.fancybox.settings.js');
}
Blox::addToFoot('<!--[if lt IE 9]><script>$(function() {document.body.style.height = screen.height + "px"})</script><script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script><script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script><![endif]-->');
Blox::outputXcode('Head');
echo'
</head>
<body'; if ($aa = Blox::getBodyAttributes()) echo' '.$aa; echo'>';


$scriptName = Blox::getScriptName();
if (
    (Blox::info('user','bar-is-fixed') && $scriptName == 'page') ||
    $scriptName == 'change'
) $barIsFixed = true;
    
                
if ($barHtm && !$barIsFixed)
    echo $barHtm;
echo $outerBlockHtm;
if ($barHtm && $barIsFixed)
    echo"\n".'<div id="blox-bar-fixed">'.$barHtm.'</div>';##<div id="blox-slide-bar">QQ</div>
Blox::outputXcode('Foot');
if ($z = Blox::info('site','extra-codes','foot')) // !Blox::info('user','user-is-admin') && !Blox::info('user','user-is-editor') && 
    echo"\n".$z;
echo'
</body>
</html>';