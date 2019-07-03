<?php
/** 
 * The intermediate script for count downloads
 * @todo Rename script from "download" to "count-url" and provide additional custom scripts
 */
if ($_GET['url']) {
//qq(Url::convertToAbsolute(urldecode($_GET['url'])));
    if ($rUrl = urldecode(Url::convertToRelative($_GET['url']))) {
        # Downloads counting
        if (Blox::info('user','user-is-admin') || Blox::info('user','user-is-editor'))
            ;
        else { # not for admin 
            if (!Store::get('statisticsIsOff')) {
                Blox::statCount('downloads', $rUrl);}
        }
        Url::redirect($rUrl,'exit');
    }
}
