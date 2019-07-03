<?php
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
echo'
<div class="blox-edit">
    <a class="button" href="?users'.$pagehrefQuery.'" title="'.$terms['users'].'"><img src="'.Blox::info('cms','url').'/assets/x-button-arrow-left.png" alt="&lt;" /></a><br /><br />
    <div class="heading">
        '.$terms['heading'].'
    </div>
    <table>
    <tr>
    <td>';
        /** 
         * @todo Search
            if ($request['search']['words']) {
                if ($request['search']['where']=='beginnings')
                    $beginning = '^';
                foreach ($request['search']['words'] as $field => $words) {
                    foreach ($words as $word) {
                        $searchTexts[$field] .= ' '.$word;
                        $patterns[$field][] = '/$beginning($word)/iu';
                        $replacements[$field][] = '<span class="red">$1</span>';
                    }
                    $searchTexts[$field] = substr($searchTexts[$field], 1);
                }
            }
            echo'
            <div class="small">
                <form action="?groups&block=groups'.$pagehrefQuery.'" method="post">
                    <input type="hidden" name="search" />
                    <input type="hidden" name="fields" value="name,description" />
                    <input type="hidden" name="where" value="beginnings" />';
                    if (Request::get('groups','search','words')['name'][0])
                        echo'<a class="smaller" href="?groups&block=groups&search'.$pagehrefQuery.'">All</a>';
                    else
                        echo'&nbsp;&nbsp;';
                    echo'
                    <input type="text" name="search[name]" value="'.$searchTexts['name'].'" disabled/>
                    <input type="submit" value="Search" disabled />
                </form>
            </div>
            <br />
        */
        echo'
      	<form action="?groups-update'.$pagehrefQuery.'" method="post"  id="groups">
            <table class="hor-separators">
            	<tr class="small center top">
                	<td>'.$terms['active'].'</td>            
                    <td>ID ('.$terms['name'].')</td>
                	<td>'.$terms['description'].'</td>
                    <td>'.$terms['group-is-editor'].'</td>
                    <td>'.$terms['deleting'].'</td>
                    <td>&nbsp;</td>
                    <td>'.$terms['groupsmembership'].'</td>
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
                        echo'
                    	<tr>
                            <!--activated-->
                        	<td align="center">
                                <input type="hidden" name="groups['.$group['id'].'][activated]" value="0" />
                                <input type="checkbox"  name="groups['.$group['id'].'][activated]" value="1"';if ($group['activated']) echo' checked';echo' title="'.$terms['actdeact'].'" />
                            </td>
                    		<!--name-->
                            <td><b>'.$group['id'].'</b> ('.$group['name'].')</td>
                    		<td class="small">'.$group['description'].' &nbsp;</td>
                    		<!--group-is-editor-->
                            <input type="hidden" name="groups['.$group['id'].'][group-is-editor]" value="0" />';
                            
                            echo'
                            <td align="center" class="smaller gray"><input type="checkbox"  name="groups['.$group['id'].'][group-is-editor]" value="1" title="'.$terms['group-is-editor2'].'"'.(Proposition::get('group-is-editor', $group['id']) ? ' checked' : '').' /></td>
                    		<!--delete-->
                            <td align="center" class="smaller gray"><input type="checkbox"  name="groups['.$group['id'].'][delete]" value="1" title="'.$terms['deleting'].'" /><span class="red">'.$terms['delete'].'</span></td>
                            <!--group params-->
                            <td align="center"><a class="button" rel="nofollow" href="?group-info&selected-group-id='.$group['id'].$pagehrefQuery.'" title="'.$terms['editing'].'"><img src="'.Blox::info('cms','url').'/assets/x-button-arrow-right.png" alt="&gt;" /></a></td>
                            <td>
                                <a class="button" rel="nofollow" href="?group-users&selected-group-id='.$group['id'].$pagehrefQuery.'" title="'.$terms['group-users-2'].'">'.$terms['group-users'].'</a>';
                                 if (Proposition::get('group-has-user', $group['id'], 'any')) echo'&nbsp;*';
                            echo'
                            </td>
                        </tr>';
                    }
                    /** 
                     * @todo Parts
                        if ($request['part']['parts'])
                        {
                            echo'<tr>';
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
    </td>
    </tr>
    </table>
</div>';