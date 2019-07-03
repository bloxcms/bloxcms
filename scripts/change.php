<?php
    # TODO: Do not output $blockOutput=outputBlock(), as this will display the whole page
    
    if (!Blox::info('user','user-is-admin')) 
        Blox::execute('?error-document&code=403');
    $regularId = Sql::sanitizeInteger($_GET['block']);# Do not try to escape this var!
    $pageInfo = Router::getCurrentPageInfo();
    $folder = '';

    # Block, which is changing the template
    $blockInfo = Blox::getBlockInfo($regularId);
    Query2::capture('old-tpl&tpl&instance&instance-option'); # instance-option is needed?

    # Instance is selected 
    if ($_GET['instance']) {
        $tpl = urldecode(Query2::get('tpl')); # To get list of instances
        $instance = Sql::sanitizeInteger($_GET['instance']);
        $template->assign('instance', $instance);
        Query2::add('instance='.$instance); # For transfer to ?assign
        Blox::prompt($terms['options']);
    }    
    # Template is selected 
    elseif ($_GET['tpl']) {
        $tpl = Sql::sanitizeTpl(urldecode($_GET['tpl']));
        Query2::add('tpl='.urlencode($tpl)); # For transfer to ?assign in case of no instance
        Blox::prompt($terms['assign-or-look']); # Is it necessary?
    }
    # Folder of templates is selected 
    elseif (isset($_GET['folder'])) {
        $folder = Sql::sanitizeTpl(urldecode($_GET['folder']));
        Query2::add('folder='.urlencode($folder));
        $t = sprintf($terms['folder-selected'], '<b>'.$folder.'</b>');
        
        
        if ($z = Admin::getDescription($folder, true))
            $folderDescripton = '<p>'.$t.'</p><div class="blox-bar-descripton">'.$z.'</div>';
        else
            $folderDescripton = $t;
        Blox::prompt($folderDescripton);
    } 
    # First entrance
    else {
        if ($tpl = $blockInfo['tpl']) {
            Query2::add('tpl='.urlencode($tpl));
            Query2::add('old-tpl='.urlencode($tpl)); # For template rendering and for Admin::assignNewTpl()
            if ($delegators = Admin::getDelegators($regularId))
                Blox::prompt($terms['is-delegator-mod']);
        }
        if (!Blox::info('site', 'caching'))
            Cache::delete();
    }
    #
    if (!isset($folder) && ($_GET['tpl'] || $_GET['instance']))
        Blox::prompt($terms['red-border']);
    # If folder is selected show the tpl's list expanded @todo This is inverse of previous "if"?
    if (isset($folder) && !($_GET['tpl'] || $_GET['instance'])) {
        Blox::addToFoot('
            <script>
            $(function() {
                var $listitem = $(".blox-select-menu > ul > li:first-child");
                var $droplist = $(".blox-select-menu > ul > li:first-child > ul");
                open();
                $listitem.mouseenter(function() {open()});
                $droplist.mouseleave(function() {close()});
                function open() {
                    $listitem.css({background:"#ffd", "border-color":"#808080"});
                    $droplist.css({visibility:"visible",background:"#ffd"});
                }
                function close() {
                    $droplist.css({visibility:"hidden"});
                    $listitem.css({background:"transparent", "border-color":"transparent"});
                }
            });
            </script>
        ');
    }
    # description
    if ($tpl) 
        if ($tdd = Tdd::get(['tpl'=>$tpl])) { # Because $tpl !=  $blockInfo['tpl']
            if ($tdd['params']['description'])
                Blox::prompt('<strong>'.$terms['description'].' '.$tpl.'</strong>: '.$tdd['params']['description']);
    }
    $instances = Blox::getInstancesOfTpl($tpl, ['excluded-block'=>$regularId]);
    $template->assign('instances', $instances);
    $template->assign('cmsPublicStyled', true);
    # Bar
    $template->assign('tpl', $tpl);
    if (!$folder)
        $folder = Str::splitByMark($tpl, '/', true)[0];
    $template->assign('folder',$folder);
    
    $folderDir = Blox::info('templates', 'dir').($folder ? '/'.$folder : '');
        
    # Get all current folders with tpls
    if ($dd = glob($folderDir.'/*' , GLOB_ONLYDIR)) {
        foreach ($dd as $d) {
            if ($bb = Files::glob($d.'/*.tpl'))
                $tplFolders[] = Str::getStringAfterMark($d, '/', true);
        }
    }
    $template->assign('tplFolders', $tplFolders); # Current folders with tpls
    # RESERVED. Get tree of tpls # Files::glob($folderDir.'/*.tpl')
    $template->assign('tpls', Files::readBaseNames($folderDir, 'tpl')); # Current tpls
    $template->assign('regularId', $regularId);
    include Blox::info('cms','dir')."/includes/display-page.php";
