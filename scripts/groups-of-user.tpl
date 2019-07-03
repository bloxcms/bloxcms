<?php
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
echo'
<div class="blox-edit">
<a class="button" href="?users'.$pagehrefQuery.'" title="'.$terms['users'].'"><img src="'.Blox::info('cms','url').'/assets/x-button-arrow-left.png" alt="&lt;" /></a><br /><br />
<div class="heading">';
    echo $terms['heading'].' <b>'.$userInfo['login'].'</b>';
echo'
</div>
<table><tr><td>';

    echo'
  	<form action="?groups-of-user-update&selected-user-id='.$userInfo['id'].$pagehrefQuery.'" method="post" id="groups">
        <table class="hor-separators">
        	<tr class="small center top">
            <td>'.$terms['is-member'].'</td>
        	<td>'.$terms['group-is-actived'].'</td>
        	<td>'.$terms['description'].'</td>
            </tr>';
            if ($groups)
            {
                foreach ($groups as $i => $group) {
                    /** 
                     * @todo Search
                        if ($request['search']['words']['name']) {
                            $group['name'] = @preg_replace($patterns['name'], $replacements['name'], $group['name']);
                            $group['description'] = @preg_replace($patterns['description'], $replacements['description'], $group['description']);
                        }
                    */
                    $groupId = $group['id'];
                    echo'
                	<tr>
                	<td>
                        <input type="hidden" name="groups['.$groupId.'][is-member]" value="0" />
                        <label for="group-'.$groupId.'" title="'.($groupsOfUser[$groupId] ? $terms['exclude-from-members'] : $terms['take-in-members']).'">
                            <input type="checkbox" name="groups['.$groupId.'][is-member]" value="1"'.($groupsOfUser[$groupId] ? ' checked' : '').' id="group-'.$groupId.'" /><b>'.$groupId.'</b> ('.$group['name'].')                
                        </label>
                    </td>
                	<td class="small'.($group['activated'] ? '' : ' gray').'" align="center">'.($group['activated'] ? $terms['activated'] : $terms['unactivated']).'</td>
            		<td class="small'.($group['activated'] ? '' : ' gray').'">'.$group['description'].' &nbsp;</td>
                    </tr>';
                }
                /** 
                 * @todo Parts
                    if ($request['part']['parts'])
                    {
                        echo'<td colspan="12">';
                        echo'<br />
                        <div class="smaller" style="text-align:center">';

                            if (empty($request['part']['prev']))
                                echo' &nbsp; '.$terms['prev'].' &nbsp;';
                            else
                                echo' <a href="?groups&block=groups&part='.$request['part']['prev'].$pagehrefQuery.'" class="button">&nbsp; '.$terms['prev'].' &nbsp;</a>';

                            if (empty($request['part']['next']))
                                echo' &nbsp; '.$terms['next'].' &nbsp;';
                            else
                                echo' <a href="?groups&block=groups&part='.$request['part']['next'].$pagehrefQuery.'" class="button">&nbsp; '.$terms['next'].' &nbsp;</a>';

                            //echo'<br /><br />';

                            # parts
                            echo'<div style="margin: 3px 0px 3px 0px;height:1px; font:1px;"></div>';
                            foreach ($request['part']['parts'] as $p) {
                                if ($p == $request['part']['current'])
                                    echo' '.$p;
                                else
                                    echo' <a href="?groups&block=groups&part='.$p.$pagehrefQuery.'" class="button">$p</a>';
                            }
                        echo'</div>';
                        echo'</td></tr>';
                    }
                */
            }
        echo'
        </table>
        '.$submitButtons.'
    </form>
</td></tr></table>
</div>';