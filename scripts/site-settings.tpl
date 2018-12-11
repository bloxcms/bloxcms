<?php
/**
 * If you want to pass the wrong value back to the form, use $_GET['invalids']
 * @example Multidimensional division of fields - see # Error pages   ---Emails
 */

$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
echo'
<div class="blox-edit">
    <div class="heading">'.$terms['heading'].'</div>
    <br />';
    if (isset($_GET['errors']) || isset($_GET['invalids']))
        echo'<div class="warning"><b>'.$terms['there-are-errors'].'</b></div><br>';
    echo'
  	<form action="?site-settings-update'.$pagehrefQuery.'" method="post" enctype="multipart/form-data" class="wide-fields">
    <table class="hor-separators top">';
        # favicon 
        echo'
    	<tr>
    	<td class="name">'.$terms['favicon']['name'].'</td>
    	<td>
            <script type="text/javascript">
            function disableFaviconFile(obj) {
                var inputbox = document.getElementById("favicon-file-name");
                if(obj.checked) {inputbox.disabled;}
                else {inputbox.disabled="";}
            }
            </script>
            <input name="favicon[file-name]" id="favicon-file-name" type="file"  size="40" />
            <div class="explanation">
            ';
            if ($oldSettings['favicon']['file-name']) {
                echo'
                <input type="checkbox"  name="favicon[delete-file]" value="1" id="favicon-delete-file" onclick="disableFaviconFile(this)" /><span class="red"><label for="favicon-delete-file">'.$terms['favicon']['delete-file'].'</label></span> &nbsp;'.$oldSettings['favicon']['file-name'];
                $f = 'datafiles/'.$oldSettings['favicon']['file-name'];
                if (file_exists($f)) {
                    $src = $f;
                    $t = '';
                } else {
                    $src = Blox::info('cms','url').'/assets/triangle-exclamation.svg';
                    $t = $terms['favicon']['errors']['file-not-exists'];
                }
                echo' <img src="'.$src.'" title="'.$t.'" style="width:16px; vertical-align:middle" />';
            } else
                echo $terms['favicon']['errors']['file-not-uploaded'];
            if ($_GET['errors']['favicon'])
                echo'<div class="warning small">'.$terms['favicon']['errors'][$_GET['errors']['favicon']].'</div>';  
            echo'            
            </div>
        </td>
        <td class="note">'.$terms['favicon']['note'].'</td>
        </tr>';
        if (!Blox::info('site','human-urls','disabled')) {
            # human-urls
            echo'
        	<tr>
            	<td class="name">'.$terms['human-urls']['name'].'</td>
            	<td>
                    <input type="hidden"   name="human-urls[on]" value="0" />
                    <input type="checkbox" name="human-urls[on]" value="1"'; if ($oldSettings['human-urls']['on']) echo' checked'; echo' />
                </td>
                <td class="note">'.$terms['human-urls']['note'].'</td>
            </tr>';
        }
        
        # Switch on error logging
        echo'
    	<tr>
        	<td class="name">'.$terms['blox-errors']['name'].'</td>
        	<td>
                <input type="hidden"   name="blox-errors[on]" value="0" />
                <input type="checkbox" name="blox-errors[on]" value="1"'; if ($oldSettings['blox-errors']['on']) echo' checked'; echo' />
            </td>
            <td class="note">'.$terms['blox-errors']['note'].'</td>
        </tr>';
        
        

        
        #Emails 
        $host = Url::getAbsUrlComponents(Blox::info('site','url'))['host'];
        $note = '';
        if (preg_match('~^[a-z0-9.-]+\.[a-z]{2,9}$~ui', $host)) # If latin host
            $note.= $terms['emails']['note-2'].' <b>*@'.$host.'</b> ';
        $note.= $terms['emails']['note'];
        echo'
        <tr><td colspan="3" class="caption">'.$terms['emails']['caption'].'<p class="note">'.$note.'</p></td></tr>';
        foreach (['to','from','transport'] as $item) {
            $value = $oldSettings['emails'][$item];
            if (isset($_GET['invalids']['emails'][$item]))
                $value = $_GET['invalids']['emails'][$item];
            echo'
        	<tr>
            	<td class="name">'.$terms['emails'][$item]['name'].'</td>
               	<td>';
                    if ($item == 'transport') {
                        echo'<textarea name="emails['.$item.']" data-emails-transport>'.$value.'</textarea>';
                        ##Blox::addToFoot('<script>$(document).ready(function () {$("[data-emails-transport]").textareaAutoSize();})</script>');
                    } else {
                        echo'<input type="text"  name="emails['.$item.']" value="'.$value.'" />';
                    }
                    if (isset($_GET['errors']['emails'][$item]))
                        echo'<div class="warning small">'.$terms['emails']['errors'][$_GET['errors']['emails'][$item]].'</div>';
                    echo'
                </td>
                <td class="note">'.$terms['emails'][$item]['note'].'</td>
            </tr>';
        }
        /*
        # emails[domain-configured] i.e. DMARC-DKIM-SPF
        echo'
    	<tr>
        	<td class="name">'.$terms['emails']['domain-configured']['name'].'</td>
        	<td>
                <input type="hidden"   name="emails[domain-configured]" value="0" />
                <input type="checkbox" name="emails[domain-configured]" value="1"'; if ($oldSettings['emails']['domain-configured']) echo' checked'; echo' />
            </td>
            <td class="note">'.$terms['emails']['domain-configured']['note'].'</td>
        </tr>';*/
        
        
        
        
        
        
        
        
        
        # Error pages
        echo'
        <tr>
            <td colspan="3" class="caption">'.$terms['errorpages']['caption'].Admin::tooltip('error-pages.htm', $terms['errorpages']['tooltip']).'<p class="note">'.$terms['errorpages']['note'].'</p></td>
        </tr>';
        foreach (['403','404'] as $item) {
            $value = $oldSettings['errorpages'][$item];
            if (isset($_GET['invalids']['errorpages'][$item]))
                $value = $_GET['invalids']['errorpages'][$item];
            echo'
        	<tr>
            	<td class="name">'.$terms['errorpages'][$item]['name'].'</td>
            	<td><input type="text"  name="errorpages['.$item.']" value="'.$value.'" />';
                    if (Blox::info('site','human-urls','convert')) {
                        if ($errorHhref = Router::convert($value))
                            if ($errorHhref != $value)
                                echo'<div class="note">'.$terms['errorpages']['hhref'].' "<b>'.$errorHhref.'</b>"</div>'; 
                    }
                    if (isset($_GET['invalids']['errorpages'][$item]))
                        echo'<div class="warning small">'.$terms['errorpages']['errors'][$_GET['errors']['errorpages'][$item]].'</div>';  
                    echo'
                </td>
                <td class="note">'.$terms['errorpages'][$item]['note'].'</td>
            </tr>';
        }
        
        # extra-codes
        echo'
        <tr>
            <td colspan="3" class="caption">'.$terms['extra-codes']['caption'].'<p class="note">'.$terms['extra-codes']['note'].'</p></td>
        </tr>';
        foreach (['head','foot'] as $item) {
            echo'
        	<tr>
            	<td class="name">'.$terms['extra-codes'][$item]['name'].'</td>
                <td><textarea name="extra-codes['.$item.']">'.$oldSettings['extra-codes'][$item].'</textarea></td>
                </td>
                <td class="note">'.$terms['extra-codes'][$item]['note'].'</td>
            </tr>';
        }
        # other
        echo'
        <tr>
            <td colspan="3" class="caption">'.$terms['other']['caption'].'</td>
        </tr>
    	<tr>
        	<td class="name">'.$terms['ignored-url-params']['name'].'</td>
        	<td>
                <input type="text"  name="ignored-url-params" value="'.$oldSettings['ignored-url-params'].'" />
            </td>
            <td class="note">'.$terms['ignored-url-params']['note'].'</td>
        </tr>';
        echo'
    </table>
    '.$submitButtons.'
    </form>
</div>';
        
# For textareas
Blox::addToFoot(Blox::info('cms','url').'/vendor/javierjulio/textarea-autosize/dist/jquery.textarea_autosize.min.js');
Blox::addToFoot('<script>$(document).ready(function() {
    /* #enlarge-textarea-width too */
    $("textarea").each(function() {
        var $w = Math.round($(this).val().length * 0.5);
        $w2 = ($w)
            ? $w + "px"
            : "100%"
        ;
        $(this).css({
            "max-width":"800px", 
            "min-width": $(this).closest("td").css("width"), 
            "width": $w2
        });
    });
    $("textarea").textareaAutoSize();
})</script>');
