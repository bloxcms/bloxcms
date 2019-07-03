<?php
    /**
     * Included in page.php and change.php
     */
    $template->assign('terms', $terms);
    $pageInfo = Router::getCurrentPageInfo() ?: Router::getPageInfoById($pageId); # Only to get $pageInfo['outer-block-id'] # If its Unregistered pseudopage  Router::getPageInfoById() will be used
    if ($pageInfo['page-is-hidden']) {
        if (Blox::info('user','user-is-admin') || Blox::info('user','user-is-editor'))
            Permission::add('page', [$pageId], ['see'=>true]);
    }
    $outerBlockHtm = Blox::getBlockHtm($pageInfo['outer-block-id']); # must be above "Cache headers" because of Blox::info('site','nocache')
    
    if ($pageInfo['page-is-hidden']) {
        $template->assign('pageIsHidden', true);
        $userSeesHiddenPage = Permission::ask('page', [$pageId]); # + editors of blocks
        if (!$userSeesHiddenPage)
            $pageIsHiddenAndDenied = true;
    }
    
    #\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
    # "Cache headers"
    $headerTimeFormat = 'D, d M Y H:i:s \G\M\T';
    $unixNow = time();
    # Editors
    if (Blox::info('site','nocache') || Blox::info('user','id') || Blox::ajaxRequested()) {
        $humExpires = gmdate($headerTimeFormat, $unixNow-604800);# minus week
        header('Pragma: no-cache'); # HTTP/1.0 #DEPRECARED
        header('Cache-Control: no-store, no-cache, must-revalidate, proxy-revalidate, pre-check=0, post-check=0, max-age=0'); # HTTP/1.1 #DEPRECARED
        header('Expires: '.$humExpires);
        header('Last-Modified: '.$humExpires);
    } 
    # Visitors
    else { 
        # Last-Modified
        if ($pageInfo['lastmod']) {
            $unixLastmod = strtotime($pageInfo['lastmod']);
            $humLastmod = gmdate($headerTimeFormat, $unixLastmod); //Wed, 12 Jan 2011 15:04:36 GMT
            $modSince = '';
            if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE']))
                $modSince = strtotime(substr($_SERVER['HTTP_IF_MODIFIED_SINCE'], 5));
            elseif (isset($_ENV['HTTP_IF_MODIFIED_SINCE']))
                $modSince = strtotime(substr($_ENV['HTTP_IF_MODIFIED_SINCE'], 5)); 
                    
            if ($modSince && $modSince >= $unixLastmod) {
                header($_SERVER['SERVER_PROTOCOL'].' 304 Not Modified');
                exit;
            }
            header('Last-Modified: '.$humLastmod);
        }
        # Expires
        if ($pageInfo['changefreq']) {
            $intervals = [
                'always'    =>86400,#day
                'hourly'    =>86400,#day
                'daily'     =>86400,#day
                'weekly'    =>604800,#week
                'monthly'   =>2628000,#month
                'yearly'    =>31536000,#year
                'never'     =>31536000,#year
            ];
            $interval = $intervals[$pageInfo['changefreq']];
        } else {
            $interval = 31536000;#year
        }
        $unixExpires = ($unixLastmod ?: $unixNow) + $interval;
        $humExpires = gmdate($headerTimeFormat, $unixExpires);
        header('Pragma: cache'); # HTTP/1.0 #DEPRECARED
        header('Cache-control: public, max-age='.$interval); #DEPRECARED # If there's Expires remove Cache-Control (Google recomends)
        header('Expires: '.$humExpires);
    }
    # /"Cache headers"
    #////////////////////////////////////////////////////
    /**
    $isBot = function() {
        foreach (
            [ # put important to the beginning (in lower case)
                'yandex',
                'googlebot', 
                'baiduspider', 
                'ia_archiver',
                'r6_feedfetcher', 
                'netcraftsurveyagent', 
                'sogou web spider',
                'bingbot', 
                'yahoo! slurp', 
                'facebookexternalhit', 
                'printfulbot',
                'msnbot', 
                'twitterbot', 
                'unwindfetchor',
                'urlresolver',
                'butterfly', 
                'tweetmemebot',
            ] as $b
        ) {
          if (stripos(strtolower($_SERVER['HTTP_USER_AGENT']), $b) !== false ) 
            return true;
        }
        return false;
    };
    */
    $pageInfo = Router::getCurrentPageInfo() ?: Router::getPageInfoById($pageId);# KLUDGE: Get $pageInfo again since it could changed in tpls.
    # outerBlockHtm
    if (!$pageIsHiddenAndDenied) {
        if (file_exists(Blox::info('templates', 'dir').'/!main.css'))
            Blox::addToHead(Blox::info('templates', 'url').'/!main.css');
        
        # !main.js should be linked before js-files of al blocks. To control the links we use Blox::addToHead()
        if (file_exists(Blox::info('templates', 'dir').'/!main.js'))
            Blox::addToFoot(Blox::info('templates', 'url').'/!main.js');
        
        $template->assign('outerBlockId', $pageInfo['outer-block-id']); # To edit the outer block
    } else
        $outerBlockHtm = '';


    unset($_SESSION['Blox']['udat']); # If tdd works as form i.e. without tpl.
    unset($_SESSION['Blox']['dpdat']);
    unset($_SESSION['Blox']['drdat']);
    $aa = $_SESSION['Blox']['update']['submit-mode'];
    unset($_SESSION['Blox']['update']['submit-mode']);
    
    if ('submit-and-return' == $aa)
        Url::redirectToReferrer(); # For default assignments
        //Url::redirectToReferrer('exit');

    # Favicon. This is used in two places: display.php, display-page.php
    if ($faviconFileName = Blox::info('site', 'favicon','file-name'))
        if (file_exists(Blox::info('site','dir').'/datafiles/'.$faviconFileName))
            $faviconFile = Blox::info('site','url').'/datafiles/'.$faviconFileName;    
    if (empty($faviconFile))
        $faviconFile = Blox::info('cms','url').'/assets/blox-favicon.ico';
    $template->assign('faviconFile', $faviconFile);

    /**
     * isMainBarVisible
     * This should be set after Blox::getBlockHtm() where block permissions are determined     
     * This should be set before 'accessToPageisDenied' due to 'userIsEditorOfAnyBlock' and 'userIdFieldExists' in templates
     * For page-bar or change-bar
     */
	if (!$GLOBALS['Blox']['no-bar'] && Blox::info('user','user-is-activated')) {
		if (Blox::info('user','id')) {
			if (Proposition::get('user-is-editor-of-block', Blox::info('user','id'), 'any'))
				$userIsEditorOfAnyBlock = true;
            if (Admin::userIdFieldExists())
                $userIdFieldExists = true;
			if (Proposition::get('user-is-subscriber', Blox::info('user','id'), 'any'))
				$userIsSubscriberOfAnyBlock = true;
		}
    
        $recordPermission = Permission::ask('record');
		if (
			Blox::info('user','user-is-admin') ||
			Blox::info('user','user-is-editor') ||
			$recordPermission ||
			$userIsEditorOfAnyBlock ||
			$userIdFieldExists ||
            $userIsSubscriberOfAnyBlock ||
            $userSeesHiddenPage
		) $isMainBarVisible = true;
	}

    if ($isMainBarVisible) {
        $template->assign('userIsEditorOfAnyBlock', $userIsEditorOfAnyBlock);
        $template->assign('userIdFieldExists', $userIdFieldExists);
        $template->assign('userIsSubscriberOfAnyBlock', $userIsSubscriberOfAnyBlock);
        $barTplFile = Blox::info('cms','dir').'/scripts/'.$script.'-bar.tpl';
        if (file_exists($barTplFile)) {
	        if (Blox::info('user','user-is-admin')) {
                # To show or not the link to the newsletter mailing?
				if (Proposition::get('user-is-subscriber', 'any', 'any'))
	                $template->assign('userIsSubscriber_anyUser_anyBlock', true);
                $sql = 'SELECT * FROM '.Blox::info('db','prefix').'pages WHERE `page-is-hidden`=1 LIMIT 1';
                if (Sql::select($sql))
                    $template->assign('pageIsHidden_anyPage', true);
	            if (Blox::info('site','site-is-down'))
	                Blox::prompt($terms['site-is-down-prompt']);
	        }
            # logoIcon
            $logoIcon = '<img src="'.$faviconFile.'" class="blox-favicon" alt="logo" />';
            $template->assign('logoIcon', $logoIcon);
            
            # Bar
			$barHtm = $template->fetch($barTplFile);
            # Output prompts to the bar
            $barHtm .= Admin::getPromptsHtm();
			$template->assign('barHtm', $barHtm);
            
            # For the page.tpl
            $scriptName = Blox::getScriptName();
            if (
                (Blox::info('user','bar-is-fixed') && $scriptName == 'page') ||
                $scriptName == 'change'
            ) {
                Blox::addToFoot(Blox::info('cms','url').'/assets/jquery-ui.js', ['after'=>'jquery-1']);
                Blox::addToFoot(Blox::info('cms','url').'/assets/blox.bar.fixed.js', ['after'=>'jquery-ui']);
            }
        }
        
    }

    if ($pageIsHiddenAndDenied) {
        Blox::prompt(sprintf($terms['access-to-page-denied'], $pageInfo['id']), true);
        Blox::execute('?error-document&code=403&note=page-is-hidden-and-denied');
    }
    $template->assign('pageInfo', $pageInfo); # for ?change it is not necessary
    $template->assign('outerBlockHtm', $outerBlockHtm);

    # cmsPublicStyled was already linked above in page.php. This is for visitor's edit buttons 
    if (Blox::info('user','id')) {
        # For .blox-maintain-scroll
        if (!Blox::info('user','user-as-visitor') || !isset($_GET['change'])) { # if there are edit buttons
            Blox::addToFoot(Blox::info('cms','url').'/assets/jquery-1.js', ['position'=>'top']);//2018-05-28 14:31
            Blox::addToFoot(Blox::info('cms','url').'/assets/jquery.cookie.js'); # to be removed
            Blox::addToFoot(Blox::info('cms','url').'/assets/blox.maintain-scroll.js', ['after'=>'blox.public.js']);
        }
    } elseif ($_SESSION['Blox']['fresh-recs'] || $GLOBALS['Blox']['enable-user-style-for-visitor'])
        $template->assign('cmsPublicStyled', true);

    $tplFile = Blox::info('cms','dir')."/scripts/page.tpl"; # For ?change
    if (file_exists($tplFile)) { # from go.php
        $pageHtm = $template->fetch($tplFile);
        if (Blox::info('site', 'caching') && !Blox::info('user','id'))
            Cache::createFile(Blox::getPageHref(), $pageId, $pageHtm);
        echo $pageHtm;
    }

