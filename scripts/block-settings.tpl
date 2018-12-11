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
  	<form action="?block-settings-update&block='.$regularId.$pagehrefQuery.'" method="post" enctype="multipart/form-data" class="wide-fields">
    <table class="hor-separators top">';
    

        #edit-button-style
        echo'
        <tr>
            <td colspan="3" class="caption">'.$terms['edit-button-style']['caption'].Admin::tooltip('template-variables-edit-button.htm', $terms['edit-button-style']['tooltip'], '#edit-button-style').'<p class="note">'.$terms['edit-button-style']['note'].'</p></td>
        </tr>';
        foreach (['top','left','z-index'] as $item) {
            $value = $oldSettings['edit-button-style'][$item];
            if (isset($_GET['invalids']['edit-button-style'][$item]))
                $value = $_GET['invalids']['edit-button-style'][$item];
            
            echo'
        	<tr>
            	<td class="name">'.$terms['edit-button-style'][$item]['name'].'</td>
            	<td><input type="text"  name="edit-button-style['.$item.']" value="'.$value.'" />';
            /*
                    if (Blox::info('site','human-urls','convert')) {
                        if ($errorHhref = Router::convert($value))
                            if ($errorHhref != $value)
                                echo'<div class="note">'.$terms['edit-button-style']['hhref'].' "<b>'.$errorHhref.'</b>"</div>'; 
                    }
                    */
                    if (isset($_GET['invalids']['edit-button-style'][$item]))
                        echo'<div class="warning small">'.$terms['edit-button-style']['errors'][$_GET['errors']['edit-button-style'][$item]].'</div>';  
                    echo'
                </td>
                <td class="note">'.$terms['edit-button-style'][$item]['note'].'</td>
            </tr>';
        }
        

            
        #block-caching
        echo'
        <tr>
            <td colspan="3" class="caption">'.$terms['block-caching']['caption'].Admin::tooltip('block-caching.htm', $terms['block-caching']['tooltip']).'<p class="note">'.$terms['block-caching']['note'].'</p></td>
        </tr>';
        foreach (['cache','absolute','cached'] as $item) {
            $value = $oldSettings['block-caching'][$item];
            echo'
        	<tr'.($item=='cached' ? ' style="display:none"' : '').'>
            	<td class="name">'.$terms['block-caching'][$item]['name'].'</td>
                <td>
                    <input type="hidden"   name="block-caching['.$item.']" value="0" />
                    <input type="checkbox" name="block-caching['.$item.']" value="1"'.($oldSettings['block-caching'][$item] ? ' checked' : '').' />
                </td>
                <td class="note">'.$terms['block-caching'][$item]['note'].'</td>
            </tr>';
        }
        echo'
    </table>
    '.$submitButtons.'
    </form>
</div>';
        
# For textareas
Blox::addToFoot(Blox::info('cms','url').'/vendor/javierjulio/textarea-autosize/dist/jquery.textarea_autosize.min.js');
Blox::addToFoot('<script>$(document).ready(function() {
    /* #enlarge-textarea-width  too */
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
