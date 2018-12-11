<?php
Blox::addToFoot(Blox::info('cms','url').'/assets/blox.public.js');
$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
echo'
<div class="blox-edit">
    <div class="heading">';
        echo $terms['heading'].Admin::tooltip('permissions-of-users.htm').'
    </div>
    <table>
    <tr>
    <td>';
        if ($aa = Request::get('users','search','texts','login')) # 'texts' is searchTexts
            $inputValue = 'value="'.$aa.'" style="background-color:rgba(255,255,0,.5)"';
        echo'
        <div class="small">
            <form action="?'.Query2::build('highlight=1&where=beginnings&fields='.urlencode('login,email,personalname,familyname'), 'search&part&sort').'" data-blox-method="get">
                <input type="text" name="search"'.$inputValue.' />
                <input type="submit" value="'.$terms['search'].'"/> <span class="gray">'.$terms['letters-and-digits'].'</span>
            </form>
        </div>
        <br />
      	<form action="?users-update'.$pagehrefQuery.'" method="post"  id="users">
            <table class="hor-separators">
            	<tr class="small center top">
            	<td'.($_GET['sort']['id'] || !isset($_GET['sort']) ? ' class="selected"' : '').'><a href="?'.Query2::build('sort[id]='.($_GET['sort']['id']=='asc' ? 'desc' : 'asc'), 'sort&part').'" title="'.$terms['sort'].'">'.$terms['id'].'</a></td>
                <td>'.$terms['active'].'</td>
                <td>'.$terms['deleting'].'</td>
                <td'.(isset($_GET['sort']['login']) ? ' class="selected"' : '').'><a href="?'.Query2::build('sort[login]='.($_GET['sort']['login']=='asc' ? 'desc' : 'asc'), 'sort&part').'" title="'.$terms['sort'].'">'.$terms['login'].'</a></td>
                <td'.(isset($_GET['sort']['email']) ? ' class="selected"' : '').'><a href="?'.Query2::build('sort[email]='.($_GET['sort']['email']=='asc' ? 'desc' : 'asc'), 'sort&part').'" title="'.$terms['sort'].'">'.$terms['email'].'</a></td>
            	<td>'.$terms['name'].'</td>
                <td>'.$terms['regdate'].'</td>
                <td class="blox-vert-sep">&#160;</td>
                <td>'.$terms['editor'].'</td>
                <td>'.$terms['editor-of-blocks'].'</td>
                <td>'.$terms['guest'].'</td>
                <td>'.$terms['subscriber'].'</td>
                <td class="blox-vert-sep">&#160;</td>
                <td>'.$terms['edit'].'</td>';
                if ($groupsExist)
                    echo'<td>'.$terms['groupsmembership'].'</td>';
                echo'
                </tr>';

                if ($users)
                {
                    foreach ($users as $i => $user) 
                    {
                        echo'
                    	<tr>';
                        echo'
                        <td class="small center'.($_GET['sort']['id'] || !isset($_GET['sort']) ? ' selected' : '').'">'.$user['id'].'</td>
                    	<td align="center">';                    
                            if (!$user['user-is-admin']) {
                                echo'
                                <input type="hidden" name="users['.$user['id'].'][user-is-activated]" value="0" />
                                <input type="checkbox"  name="users['.$user['id'].'][user-is-activated]" value="1"';
                                if ($user['user-is-activated']) echo' checked';
                                echo' title="'.$terms['actdeact'].'" />';
                            }
                            else 
                                echo'<input type="checkbox" name="" checked disabled />';
                        echo'
                        </td>
                		<!--delete-->
                        <td align="center" class="smaller gray">'; if (!$user['user-is-admin']) echo'<label><input type="checkbox"  name="users['.$user['id'].'][delete]" value="1" title="'.$terms['deleting'].'" /><span class="red">'.$terms['delete'].'</span>'; else echo'<input type="checkbox" name="" disabled />'.$terms['delete']; echo'</label></td>
                		<!--login-->
                        <td'.(isset($_GET['sort']['login']) ? ' class="selected"' : '').'><b>'.$user['login'].'</b></td>
                        <td  class="small'.(isset($_GET['sort']['email']) ? ' selected' : '').'">'.$user['email'].'</td>
        				<td class="small">'.$user['personalname'].' '.$user['familyname'].'&#160;</td>
                        <td class="small">'.$user['regdate'].'&#160;</td>
                        <td>&nbsp;</td>
                		<!--editor-->
                    	<td align="center">';
                            if (!$user['user-is-admin']) {
                                echo'
                                <input type="hidden" name="users['.$user['id'].'][user-is-editor]" value="0" />
                                <input type="checkbox" name="users['.$user['id'].'][user-is-editor]" value="1"';
                                if ($user['user-is-editor'] || $user['user-is-admin']) echo' checked';
                                echo' title="'.$terms[''].'" />';
                            } else 
                                echo'<input type="checkbox" name="" checked disabled />';
                            echo'
                        </td>
                        <!--editor of block-->
                        <td align="center">';
                            if ($user['user-is-editor-of-block']) {
                                if (!$user['user-is-admin']) 
                                    echo' <a class="button" href="?user-objects&formula=user-is-editor-of-block&selected-user-id='.$user['id'].$pagehrefQuery.'" title="'.$terms['list-of-editable-blocks'].'"><img src="'.Blox::info('cms','url').'/assets/x-button-editable-block.png" alt="B" /></a>';
                            }
                            elseif ($user['user-is-editor'] || $user['user-is-admin'])
                                echo'<span class="smaller gray">'.$terms['all-blocks'].'</span>';
                            else
                                echo'&#160;';echo'
                        </td>
        				<!--guest-->
                        <td align="center">';
                            if ($user['user-sees-hidden-page']) {
                                if (!$user['user-is-admin']) 
                                    echo' <a class="button" href="?user-objects&formula=user-sees-hidden-page&selected-user-id='.$user['id'].$pagehrefQuery.'" title="'.$terms['list-of-hidden-pages'].'"><img src="'.Blox::info('cms','url').'/assets/x-button-hidden-page.png" alt="P" /></a>';
                            } elseif ($user['user-is-editor'] || $user['user-is-admin']) {
                                echo'<span class="smaller gray">'.$terms['all-pages'].'</span>';
                            } else
                                echo'&#160;';echo'
                        </td>
        				<!--subscriber-->
                        <td align="center">';
                            if ($user['user-is-subscriber'])
                                echo' <a class="button" href="?user-objects&formula=user-is-subscriber&selected-user-id='.$user['id'].$pagehrefQuery.'" title="'.$terms['list-of-subscribed-blocks'].'"><img src="'.Blox::info('cms','url').'/assets/x-button-editable-block.png" alt="B" /></a>';
                            else
                                echo'&#160;';echo'
                        </td>
                        <td>&nbsp;</td>
                        <!--user params-->
                        <td align="center"><a class="button" rel="nofollow" href="?user-info&selected-user-id='.$user['id'].$pagehrefQuery.'" title="'.$terms['editing'].'"><img src="'.Blox::info('cms','url').'/assets/x-button-arrow-right.png" alt="&gt;" /></a></td>';
                        if ($groupsExist) {
                            echo'
                            <td>';
                                if (!$user['user-is-admin']) {
                                    echo'
                                    <a class="button" rel="nofollow" href="?groups-of-user&selected-user-id='.$user['id'].$pagehrefQuery.'" title="'.$terms['groups-of-user-2'].'">'.$terms['groups-of-user'].'</a>';
                                    if ($user['group-has-user']) 
                                        echo'&nbsp;*';
                                }
                            echo'
                            </td>';
                        }
                        echo'
                        </tr>';
                    }

                    # parts
                    if ($request['part']['parts']) {
                        echo'<td colspan="13">';
                        echo'<br />
                        <div class="smaller" style="text-align:center">';
                            if (empty($request['part']['prev']))
                                echo' &#160; '.$terms['prev'].' &#160;';
                            else
                                echo' <a href="?'.Query2::build('part='.$request['part']['prev']).'" class="button">&#160; '.$terms['prev'].' &#160;</a>';

                            if (empty($request['part']['next']))
                                echo' &#160; '.$terms['next'].' &#160;';
                            else
                                echo' <a href="?'.Query2::build('part='.$request['part']['next']).'" class="button">&#160; '.$terms['next'].' &#160;</a>';
                            echo'
                            <div style="margin: 3px 0px 3px 0px;height:1px; font:1px;"></div>';
                            foreach ($request['part']['parts'] as $p) {
                                if ($p == $request['part']['current'])
                                    echo' '.$p;
                                else
                                    echo' <a href="?'.Query2::build('part='.$p).'" class="button">'.$p.'</a>';
                            }
                        echo'</div>';
                        echo'</td></tr>';
                    }
                }
                echo'
            </table>
            '.$submitButtons.'
        </form>
    </td>
    </tr>
    </table>
</div>';