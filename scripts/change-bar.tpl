<?php
$pagehref = Blox::getPageHref();
$pagehrefQuery = '&pagehref='.Url::encode($pagehref);
$pageurl = Blox::getPageUrl(); # or Blox::info('site','url').'/'.$pagehref.
$selectedTpl = $tpl;    //$tpl ?: Query2::get('old-tpl');
echo'
<div class="blox-bar">
    <div class="blox-menubar">
        '.$logoIcon.' <strong>'.$terms['tpl-changing'].' '.$regularId.Admin::tooltip('tpl-changing.htm', $terms['tpl-assignment'], '#change-bar').'</strong>
    </div>
    <div class="blox-menubar" style="padding-left:0">
        <nav class="blox-select-menu">
            <ul>
                <li><a href="#">'.$terms['selected-template'].'&#11206;'.($selectedTpl ? ' <b>'.$selectedTpl.'</b>' : '').'</a>
                    <ul>';
                        # Tpl folders
                        $request = '?change&block='.$regularId;
                        # upperFolder
                        if ($folder) {
                            $upperFolder = Str::splitByMark($folder, '/', true)[0];
                            $q0 = Query2::build('','tpl&instance&folder&old-tpl');
                            echo'<li class="icon-folder-up">';
                                echo'<a';
                                    echo' href="'.$request;
                                        echo'&folder='.urlencode($upperFolder);
                                        echo '&'.$q0.$pagehrefQuery;
                                        echo'"';
                                    echo' title="'.$upperFolder.'/"';
                                echo'>..</a>';
                            echo'</li>';
                        }
                        # Folders
                        if ($tplFolders) {
                            foreach ($tplFolders as $fd) {
                                $folderPath = ($folder ? $folder.'/' : '').$fd;
                                $d = Blox::info('templates', 'dir').'/'.$folderPath;
                                $folderNotExists = !file_exists($d) && !is_dir($d);
                                echo'<li class="icon-folder">';
                                    echo'<a';
                                        echo' href="'.$request;
                                            echo'&folder='.urlencode($folderPath);
                                            echo '&'.Query2::build('','tpl&instance&folder').$pagehrefQuery;
                                            echo'"';
                                        echo ($folderNotExists) ? ' style="text-decoration:line-through"' : '';
                                        echo' title="'.$folderPath.'/"';
                                    echo'>';
                                        echo $fd;
                                        echo ($folderNotExists) ? ' ('.$terms['no-folder'].')' : '';
                                    echo'</a>';
                                echo'</li>';
                            }
                        }
                        # tpls
                        if ($tpls) {
                            $q2 = '&'.Query2::build('','tpl&instance').$pagehrefQuery;
                            foreach ($tpls as $tp) {
                                $tplPath = ($folder ? $folder.'/' : '').$tp;
                                $tplExists = file_exists(Blox::info('templates', 'dir').'/'.$tplPath.'.tpl');
                                echo'<li class="icon-tpl'.($tplPath==$selectedTpl ? ' active' : '').'">';
                                    echo'<a';
                                        echo' href="'.$request;
                                            echo'&what=after-tpl-selected&tpl='.urlencode($tplPath);
                                            echo $q2;
                                            echo'"';
                                        echo (!$tplExists) ? ' style="text-decoration:line-through"' : '';
                                        echo' title="'.$tplPath.'"';
                                    echo'>';
                                        echo $tp;
                                        echo (!$tplExists) ? ' ('.$terms['no-file'].')' : '';
                                    echo'</a>';
                                echo'</li>';
                            }
                        }
                        echo'
                    </ul>
                </li>

                <!-- Try an instance -->
                <li title="'.$terms['instance'].'"'.($instances ? '' : ' disabled').'><a href="#">'.$terms['block'].'&#11206;'.($instance ? ' <b>'.$instance.'</b>' : '').'</a>';
                    if ($instances) {
                        echo'<ul class="icon-block">';
                        foreach ($instances as $inst) {
                            $active = false;
                            if ($instance) {
                                if ($inst==$instance) 
                                    $active = true;
                            } elseif ($inst==$regularId)
                                    $active = true;
                            echo'<li '.($active ? ' class="active"' : '').'"><a href="?change&block='.$regularId.'&instance='.$inst.'&'.Query2::build('','instance').$pagehrefQuery.'">'.$inst.'</a></li>';
                        }
                        echo'</ul>';
                    }
                    echo'
                </li>
            </ul>
                            
        </nav>';

        # KLUDGE When assigning tpl via modify button in edit window appears unwanted param 'change' in URL  Query2::build('','change') 
        $attr = '';
        if (empty($instances))
            $attr.= ' disabled';
        else {
            if (empty($instance) || $_GET['instance-option']=='change')
                $attr.= ' disabled';
            elseif ($_GET['instance-option']=='delegate')
                $attr.= ' checked';
        }
        //<label><input name="instance-option" value="cache" type="radio"'.$attr.' /> '.$terms['cache'].'</label> &#160;
        echo'
	    <form action="?check&block='.$regularId.'&what=before-assign&'.Query2::build('','change&pagehref').'" method="post" id="blox-check" data-blox-method="get">
	        <!-- Assign -->
            <label><input name="instance-option" value="change" type="radio" checked /> '.$terms['change'].'</label> &#160;
            <label><input name="instance-option" value="delegate" type="radio"'.$attr.' /> '.$terms['delegate'].'</label> &#160;
            <input type="hidden" name="pagehref" value="'.Url::encode($pagehref).'" />
            <button type="submit"';if (isEmpty($tpl) && empty($instance)) echo' disabled';echo' />'.$terms['assign'].'</button> &#160;
    	</form>
        <form action="'.$pageurl.'" method="post" id="cancel-form">
	        <button type="submit" data-blox-shortcut-key="27" data-blox-shortcut-url="'.$pageurl.'" />'.$terms['cancel'].'</button>
        </form>
    </div>
</div>';
