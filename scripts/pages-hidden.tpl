<?php
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
echo'
<div class="blox-edit">
<div class="heading">'.$terms['bar-title'].Admin::tooltip('page-info.htm').'</div>
<form action="?pages-hidden-update'.$pagehrefQuery.'" method="post">
<table><tr><td>';
if ($hiddenPages) {
    foreach ($hiddenPages as $hiddenPage) {
        $pageId = $hiddenPage['id'];
        echo'
        <input type="hidden"   name="hidden-pages['.$pageId.']" value="0" />
        <input type="checkbox" name="hidden-pages['.$pageId.']" value="1" checked id="'.$pageId.'" /> <label for="'.$pageId.'"><b>'.$pageId.'</b> <span class="small">('.$hiddenPage['title'].')</span></label>
        <br />';
    }
        echo'
        <br /><br />
        <div class="small" style="width:500px">
            <b>'.$terms['note'].'</b><br />
            '.$terms['note-content'].'
        </div>';
} else
    echo $terms['no-hidden-pages'];
echo'
    '.$submitButtons.'
</td></tr></table>
</form>
</div>';