<?php

/*
 * @todo Remove all variables from an array of pageInfo[] that are not associated with it. Move them to another array
 */

$pagehrefQuery = '&pagehref='.Blox::getPageHref(true);
echo'
<div class="blox-edit">
    <div class="heading" style="margin-bottom:5px">
        '.$terms['heading'];
        echo Admin::tooltip('page-info.htm', $terms['page-settings']);
        echo' <b>'.$pageOldInfo['name'].'</b>';
        if (isset($_GET['error'])) 
            echo'. <span class="red">'.$terms['error'].'</span>';
    echo'
    </div>';

    if (!$pageOldInfo) {
        echo'<span class="red">'.$terms['settings-are-unavailable'].'</span>';
        echo $cancelButton;
    } else {
        echo'
        <table class="small" style="border-spacing:2px">
        	<tr><td>'.$terms['page-address'].'</td><td>'.Blox::info('site','url').'/'.$pageOldInfo['phref'].'</td></tr>';# parametric
            if (!Blox::info('site','human-urls','disabled')) {
                if ($pageOldInfo['hhref'])  
                    echo'<tr><td>'.$terms['human-address'].'</td><td>'.Blox::info('site','url').'/'.$pageOldInfo['hhref'].'</td></tr>';
            }
            
            if ($pageOldInfo['lastmod'])  
                echo'<tr><td>'.$terms['edited'].'</td><td>'.$pageOldInfo['lastmod'].'</td></tr>'; 
        echo'
        </table>
        <br /><br />
      	<form action="?page-info-update'.$pagehrefQuery.'" method="post"  class="wide-fields">
        <table class="form">';
            if (Blox::info('user','user-is-admin') && !$pageOldInfo['is-pseudopage']) {
    	        echo'
    	    	<tr>
        	    	<td class="name">'.$terms['page-is-hidden'].'</td>
        	    	<td>
        	        <input type="hidden" name="page-info[page-is-hidden]" value="0" />
        	        <input type="checkbox" name="page-info[page-is-hidden]" value="1"'; if ($pageOldInfo['page-is-hidden']) echo'checked'; echo' />
        	        <span class="note">'.$terms['only-for-guests'].'</span>
        	        </td>
    	        </tr>
        	    <tr><td colspan="2"><div class="hor-separator"></div></td></tr>';
    	    }
            echo'
        	<tr>
            	<td class="name">'.$terms['name'].'</td>
            	<td>
                    <textarea name="page-info[name]" rows="1">'.Text::stripTags($pageOldInfo['name']).'</textarea>
                    <div class="note">'.$terms['name-comment'].'</div>
                </td>
            </tr>
        	<tr>
            	<td class="name">'.$terms['title'].'</td>
                <td>
                    <textarea name="page-info[title]" rows="1">'.Text::stripTags($pageOldInfo['title']).'</textarea>
                    <div class="note">'.$terms['title-comment'].'</div>
                </td>
            </tr>';
            # `pseudo-pages-title-prefix`
            if ($pageOldInfo['is-pseudopage']) {
                echo'
            	<tr>
                	<td class="name">'.$terms['pseudo-pages-title-prefix'].'</td>
                	<td>
                    <input type="text" name="page-info[pseudo-pages-title-prefix]" value="'.$pageOldInfo['pseudo-pages-title-prefix'].'" style="width:80%" />
                    <div class="note">'.$terms['pseudo-pages-title-prefix-comment'].' <b>'.$pageOldInfo['id'].'</b>'; if ($pageOldInfo['base-page-name']) echo' ('.$pageOldInfo['base-page-name'].')'; echo'</div>
                    </td>
                </tr>';
            }
            
            if (!Blox::info('site','human-urls','disabled')) {
                echo'
            	<tr>
                	<td class="name">'.$terms['alias'].'</td>
                	<td>
                    <input type="text" name="page-info[alias]" value="'.$pageOldInfo['alias'].'" '; 
                        //if (!Blox::info('user','user-is-admin') || $pageOldInfo['id']==1) 
                        if ($pageOldInfo['id']==1) 
                            echo' disabled';  
                        echo' 
                    />
                    <div class="note">'.$terms['alias-comment']; if (Blox::info('site', 'transliterate')) echo $terms['alias-comment2']; echo'</div>
                    </td>
                </tr>';
            }
            echo'
        	<tr><td colspan="2"><div class="hor-separator"></div></td></tr>
        	<tr>
            	<td class="name"><br />'.$terms['key-words'].'</td>
            	<td><textarea name="page-info[keywords]" rows="5">'.Text::stripTags($pageOldInfo['keywords'], ['strip-quotes'=>true]).'</textarea></td>
            </tr>
            <tr>
            	<td class="name"><br />'.$terms['description'].'</td>
            	<td><textarea name="page-info[description]" rows="5">'.Text::stripTags($pageOldInfo['description'], ['strip-quotes'=>true]).'</textarea></td>
            </tr>
        	<tr>
            	<td class="name">'.$terms['changefreq'].'</td>
            	<td>
                <input type="radio" name="page-info[changefreq]" value="always" id="changefreq_always"'; if ($pageOldInfo['changefreq']=='always') echo' checked'; echo' /><label for="changefreq_always">'.$terms['changefreq_always'].'</label> &nbsp;
                <input type="radio" name="page-info[changefreq]" value="auto"   id="changefreq_auto"';   if ($pageOldInfo['changefreq']=='auto')   echo' checked'; echo' /><label for="changefreq_auto"  >'.$terms['changefreq_auto'].'  </label> &nbsp;
                <input type="radio" name="page-info[changefreq]" value="never"  id="changefreq_never"';  if ($pageOldInfo['changefreq']=='never')  echo' checked'; echo' /><label for="changefreq_never" >'.$terms['changefreq_never'].' </label>                
                </td>
            </tr>
        	<tr>
            	<td class="name">'.$terms['priority'].'</td>
            	<td>
                <input type="text" name="page-info[priority]" value="'.$pageOldInfo['priority'].'" style="width:30px"/> 
                <span class="note">'.$terms['priority-comment'].'</span>
                </td>
            </tr>';
            # TODO: use locale for priority decimal point formating 
            
            if (Blox::info('user','user-is-admin')) {
                echo'
    	        <tr>
                    <td colspan="2"><div class="hor-separator"></div></td>
                </tr>
    	    	<tr>
        	    	<td class="name">'.$terms['parent-phref'].'</td>
        	    	<td>
                        <input type="text" name="page-info[parent-phref]" value="'.$pageOldInfo['parent-phref'].'"';
                            if($pageOldInfo['is-pseudopage'] && $pageOldInfo['parent-key'] === null) 
                                echo' disabled';
                            elseif ($pageOldInfo['id']==1)
                                echo' disabled';
                            elseif (!$pageOldInfo['parent-page-is-adopted']) 
                                echo' disabled';
                            echo'
                        />';

                        if ($pageOldInfo['id'] != 1) {
                            echo'
                            <div class="note">'.$terms['address-of-any-kind'].'</div>
                            <input type="hidden" name="page-info[parent-page-is-adopted]" value="0" />
                            <label>&nbsp;
                                <input type="checkbox" name="page-info[parent-page-is-adopted]" value="1"'.($pageOldInfo['parent-page-is-adopted'] ? ' checked' : '').' />'.$terms['assign-manualy'].'
                            </label>
                            <label>&nbsp;
                                <input type="checkbox" disabled name="page-info[change-parent-page-for-all-siblings]" value="1" /><span>'.$terms['change-parent-page-for-all-siblings'].'</span>
                            </label>
                            ';
                        }
                        if ($_GET['error']=='adoptedParentPageIsNotCorrect') 
                            echo'. <span class="red">'.$terms['incorrect-href'].' ('.$_SESSION['Blox']['new-adopted-parent-phref'].').</span>';
                        if ($_GET['error']=='parentPageDoesNotExist') 
                            echo'. <span class="red">'.sprintf($terms['page-not-exist'], $_SESSION['Blox']['new-adopted-parent-phref']).'</span>';
                        Blox::addToFoot('
            	            <script>
                                $(function() {
                                    var inpt = $(\'[name="page-info[parent-phref]"]\');
                                    var chk1 = $(\'[name="page-info[parent-page-is-adopted]"]\');
                                    var chk2 = $(\'[name="page-info[change-parent-page-for-all-siblings]"]\');
                                    var checked1 = false;
                                    inpt.on("input", function() {chk2.prop("disabled",false)});
                                    chk1.on("click", function() { 
                                        if ($(this).prop("checked")) {
                                            inpt.prop("disabled",false);
                                            if (checked1)
                                                inpt.val("");
                                        } else {
                                            inpt.prop("disabled",true);
                                            inpt.val("'.$terms['set-default-parent-url'].'");
                                            checked1 = true;
                                        }
                                        chk2.prop("disabled",false);
                                        
                                    });
                                });
            	            </script>'
                        );
                        echo'
        	        </td>
    	        </tr>';
            }
            if ($pageOldInfo['is-pseudopage']) 
                echo'<input type="hidden" name="page-info[is-pseudopage]" value="1" />';
            foreach ($pageOldInfo as $k => $v)
                echo'<input type="hidden" name="page-info-old['.$k.']" value="'.$v.'" />';
            echo'
            <tr>
        	<td colspan="2">'.$submitButtons.'</td>
            </tr>
        </table>
        </form>';
    }
echo'
</div>';