<?php
Blox::addToHead('
    <style>
        html, body       {display:table; width:100%; height:100%; margin:0; padding:0}
        .middle-center   {display:table-cell; text-align:center; vertical-align:middle}
        .middle-centered {display:inline-block}
    </style>
');    
echo'
<div class="middle-center">
    <div class="middle-centered">    
        <img src="'.Blox::info('cms','url').'/assets/site-is-down.gif" alt="'.$terms['headline'].'" /><br />
        <span style="font:bold 21px Verdana">'.$terms['headline'].'</span><br />
        <span style="font:17px Verdana">'.$terms['note'].'</span>
        <br /><br /><br /><br /><br />
    </div>
</div>'; 
/*
THIS WEBSITE IS TEMPORARILY UNAVAILABLE.
Contact us via the information below and please mention that the website is unavailable. We apologize for any inconvenience this causes, and thank you for your patience during this time.
If you are the website owner, please contact L.E.T. Group to restore your website.
*/