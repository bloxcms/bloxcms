<?php
# TODO with Misc::paginate()
# Transition to the other parts
Blox::includeTerms($terms);
$request = Request::get($blockInfo['id']);
if ($request['part']['num-of-parts'] > 1) {
    echo'<br />
    <div class="smaller">';
         # prev
        if (empty($request['part']['prev']))
            echo' <span class="button">&#160; '.$terms['prev'].' &#160;</span>';
        else
            echo' <a class="button" href="?'.$script.'&block='.$blockInfo['id'].'&part='.$request['part']['prev'].$pagehrefQuery.'">&#160; '.$terms['prev'].' &#160;</a>';
        # by number
        foreach ($request['part']['parts'] as $p){
            if ($p == $request['part']['current']) 
                echo' <span class="button">'.$p.'</span>';
            else 
                echo' <a class="button" href="?'.$script.'&block='.$blockInfo['id'].'&part='.$p.$pagehrefQuery.'">'.$p.'</a>';}
        # next
        if (empty($request['part']['next']))
            echo' <span class="button">&#160; '.$terms['next'].' &#160;</span>';
        else
            echo' <a class="button" href="?'.$script.'&block='.$blockInfo['id'].'&part='.$request['part']['next'].$pagehrefQuery.'">&#160; '.$terms['next'].' &#160;</a>';
    echo'</div>';
}