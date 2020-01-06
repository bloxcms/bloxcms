<?php
$bloxversion = '14.2.6'; # When upgrading to a new version, change it in documentation pages/_default.htm

ini_set('error_reporting', E_ALL & ~E_NOTICE & ~E_WARNING);
# ini_set ('display_errors', '1');
# ini_set ('display_startup_errors', '1');
# ini_set('error_reporting', E_ALL & ~E_NOTICE);
# ini_set('error_reporting', E_ALL);
# if (ini_get('register_globals')) ini_set('register_globals', false); It does not work. Write "php_flag register_globals off" in .htaccess

########################### UTF-8 #####################################
# DEPRECATED since PHP 5.6.0
if (version_compare(PHP_VERSION, '5.6.0') < 0) {
    #http://php.net/manual/en/mbstring.configuration.php
    ini_set('mbstring.internal_encoding', 'UTF-8'); # is not necessary from PHP 5.6    
    ini_set('mbstring.http_input', 'auto'); # PHP 5.6 and later users should leave this empty and set default_charset instead. In PHP 5.6 onwards, "UTF-8" is the default value and its value is used as the default character encoding
    ini_set('mbstring.http_output', 'UTF-8'); # PHP 5.6 and later users should leave this empty and set default_charset instead.
}
ini_set('mbstring.language', 'Neutral');
ini_set('mbstring.encoding_translation', 'On');
ini_set('mbstring.detect_order', 'auto');
ini_set('mbstring.substitute_character', 'none');
ini_set('default_charset', 'UTF-8');


(function() { # In order to hide vars from global scope

    if (true) { # Store sessions in files
        ;
        # In order to store sessions in files, you should 
        # 1) put: if (true)
        # 2) delete the table "sessions"
    } else { # Store sessions in DB
        /**
        # BUG: Does not save when ajax on Openserver web server
        (function() {
            $createTableSql = "CREATE TABLE ".Blox::info('db','prefix')."sessions (id varchar(32) NOT NULL PRIMARY KEY, time int(11) NOT NULL default '0', start int(11) NOT NULL default '0', data longtext)";                
            if (Sql::tableExists(Blox::info('db','prefix').'sessions')) {
                $sessTbl = Blox::info('db','prefix')."sessions";
                if ($result = Sql::query("CHECK TABLE ".$sessTbl)) {
                    while ($row = $result->fetch_assoc()) {
                        if ($row['Msg_text'] == 'ok') {
                            require_once Blox::info('cms','dir')."/includes/db-session.php";
                            break;
                        } else {
                            $sessionsTableDisturbed = true;
                            Blox::error('The table '.$sessTbl.' '.$row['Msg_type'].' ('.$row['Msg_text'].')');
                        }
                    }
                    if ($sessionsTableDisturbed){
                        if (Sql::query("DROP TABLE $sessTbl")) {
                            if (Sql::query($createTableSql)) {
                                Blox::error("Due to problems with table $sessTbl, it was deleted and recreated");
                                require_once Blox::info('cms','dir')."/includes/db-session.php";
                            } else
                                Blox::error("Can't create the table $sessTbl");
                        } else
                            Blox::error("Can't drop the table $sessTbl");
                    }
                }
            } elseif (Sql::query($createTableSql)) {                
                require_once Blox::info('cms','dir')."/includes/db-session.php";
            }
        })();
        */
    }
    session_start();
    # session_write_close();  TODO: Fix the AJAX Requests that Make PHP Take Too Long to Respond   http://www.phpclasses.org/blog/post/277-Fix-the-AJAX-Requests-that-Make-PHP-Take-Too-Long-to-Respond.html

    /*
    #497436375
    $_SERVER['HTTP_REFERER'] = urldecode($_SERVER['HTTP_REFERER']);
    $_SERVER['REQUEST_URI']  = urldecode($_SERVER['REQUEST_URI']); # urldecode() at the final stage in the tpl.
    */

    $urlComponents = [];
    $urlComponents['path'] = str_replace('\\', '/', substr_replace($_SERVER['SCRIPT_NAME'], '', -10));
    $getHref = function($path) {
        $h = substr($_SERVER['REQUEST_URI'], strlen($path) + 1);
        if ($h[0] == '%') { #KLUDGE: non latin hhref #497436375
            $h = urldecode($h);
            $_SERVER['REQUEST_URI'] = $path.'/'.$h; # This should solve all problems #497436375 with urldecode instead of "urldecode($_SERVER['REQUEST_URI'])". #TODO: Remove all other #497436375 codes. No! There is product code in e-shops
        }
        return $h;
    };
    $urlComponents['href'] = $getHref($urlComponents['path']);
    
    if ($GLOBALS['caching'] && !$_SESSION['Blox']['sess-user-id'] && !isset($_GET['login']) && !isset($_GET['authenticate'])) {
        (function($str) {
            if ($str) {
                # See also #cacheAlias
                $str = mb_strtolower($str);
                $str = rtrim($str, '/');
                $str = preg_replace('~[^\\pL0-9_-]+~u', '-', $str); # Replace nonletters and nondigits by "-"
                $str = preg_replace("~[-]+(?!$)~u", "-", $str); # Remove double "-" not in the end (because of not unique aliases of human urls)
                $str = ltrim($str, '-'); # Trim "-"
                if (!$str)
                    return;
            } else {
                $str = 'page-1';
            }
            $fl = 'cached/'.$str.'.html';
            if (file_exists($fl)) {
                include $fl;
                exit;
            }
        })($urlComponents['href']);
    }

    # KLUDGE: If there is an error in js, "undefined" page is requested. So index.php is called once again. This is unacceptable for forms 
    if ($_SERVER['REQUEST_URI'] == '/undefined')
        exit;
    $GLOBALS['Blox'] = [];

    # Safe conf.php.  
    if (file_exists('../conf.php')) {
        (function() {
            include '../conf.php';
            foreach (get_defined_vars() as $k => $v) {
                if (!isset($GLOBALS[$k])) # Variables of index.php have priority
                        $GLOBALS[$k] = $v;
            }
        })();
    }

    
    # Autoload Blox classes 
    spl_autoload_register(
        function($cl) {include $GLOBALS['bloxdir'].'/functions/'.$cl.'.php';}
    );
    # Autoload vendor's classes 
    require_once $GLOBALS['bloxdir'].'/vendor/autoload.php';
    # DB
    Sql::setDb($GLOBALS['dbhost'], $GLOBALS['dbuser'], $GLOBALS['dbpass'], $GLOBALS['dbname']);
    # mysql strict mode enabling
    # Error: Unknown system variable 'STRICT_TRANS_TABLES'
    # Sql::query('SET sql_mode = NO_ENGINE_SUBSTITUTION,STRICT_TRANS_TABLES');            
        
    Blox::setVersion($GLOBALS['bloxversion']);
    # 'db'=>'prefix' must be assigned before call of the 'Store' class
    Blox::addInfo(['db' => [
        'host'  => $GLOBALS['dbhost'], 
        'user'  => $GLOBALS['dbuser'], 
        'name'  => $GLOBALS['dbname'], 
        'prefix' => ($GLOBALS['prefix'] ? mb_strtolower($GLOBALS['prefix']) : '')
    ]]);
    #ignored-url-params
    if ($siteSettings = Store::get('site-settings')) {
        if ($siteSettings['ignored-url-params']) {
            if ($siteSettings['ignored-url-params'] = array_map('trim', explode(',', $siteSettings['ignored-url-params']))) {
                foreach ($siteSettings['ignored-url-params'] as $p) {
                    unset($_GET[$p]);
                    $s.= '|'.$p;
                }
                if ($urlComponents['href'] = preg_replace('~(\?|&)('.substr($s, 1).')(=[^&]*)?~u', '', $urlComponents['href']))
                    $_SERVER['REQUEST_URI'] = $urlComponents['path'].'/'.$urlComponents['href'];
            }
        }
    }
            
    if (!$GLOBALS['lang'])
        $GLOBALS['lang'] = 'ru';
    # cmslang        
    if (!$GLOBALS['cmslang'])
        $GLOBALS['cmslang'] = $GLOBALS['lang'];
    
    if (!$GLOBALS['cmslang'] || 'ru' == mb_strtolower($GLOBALS['cmslang']))
        $GLOBALS['cmslang'] = 'ru';
    
    if (!$GLOBALS['lang'] || 'russian' == mb_strtolower($GLOBALS['lang']))
        $GLOBALS['lang'] = 'ru';
    
    /** 
     * dateTimeFormat
     * @todo Format in .tdd too: types[] = 'datetime format(Y-m-d H:i:s)'
     * @todo transfer to /languages/
     */
    if ($GLOBALS['dateTimeFormat']) {
        $dateTimeFormats['datetime'] = $GLOBALS['dateTimeFormat'];
        $aa = explode(' ',$GLOBALS['dateTimeFormat']);
        if ($aa[0])
            $dateTimeFormats['date'] = $aa[0];
        if ($aa[1])
            $dateTimeFormats['time'] = $aa[1];
        unset($GLOBALS['dateTimeFormat']);
    } else {
        $dateTimeFormats = [
            'datetime'=>'Y-m-d H:i:s',
            'date'=>'Y-m-d',
            'time'=>'H:i:s',
        ];
    }

    /**
     * Emulation of url request for shell commands.
     * Call: <?php shell_exec('php D:/sites/aaa.com/index.php http://aaa.com/?block=882'); >?
     * Path to the index.php must be absolute (to calculate $siteDir)
     * URL must be absolute and parametric (to calculate the site URL)
     *
     * Usualy "max_execution_time" is 0 in that case i.e. unlimited.
     * $_SERVER array is totally different
     */
    global $argv; # Global var of arguments passed to the script when running from the command line.
    if ($argv) { 
        if (basename($argv[0]) == 'index.php') {
            if ($argv[1]) {
                $_GET = [];
                $urlComponents = Url::getAbsUrlComponents($argv[1]);
                $_SERVER['HTTP_HOST'] = $urlComponents['host'];
                $urlComponents['href'] = '?'.$urlComponents['query'];
                if ($urlComponents['path'])
                    $urlComponents['path'] = substr($urlComponents['path'], 0, -1 ); # remove trailing slash
                $_SERVER['REQUEST_URI'] = $urlComponents['path'].'/'.$urlComponents['href'];
                $indexFile = $argv[0];
                if ($z = Url::queryToArray($urlComponents['query'])) {
                    foreach ($z as $k=>$v)
                        $_GET[$k] = $v;
                }
            }
        }
    }


    # HTTP or HTTPS
    if ($urlComponents['scheme'])
        ;
    elseif ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $_SERVER['SERVER_PORT'] == 443 || $_SERVER['HTTP_X_FORWARDED_SSL'] == 'on')
        $urlComponents['scheme'] = 'https';
    elseif ($_SERVER['HTTP_X_FORWARDED_PROTO'])
        $urlComponents['scheme'] = $_SERVER['HTTP_X_FORWARDED_PROTO']; # "X" means experimental
    elseif ($_SERVER['REQUEST_SCHEME'])
        $urlComponents['scheme'] = $_SERVER['REQUEST_SCHEME']; # is documented only since Apache 2.4.
    else
        $urlComponents['scheme'] = 'http';
    
    if (!isset($urlComponents['path']))
        $urlComponents['path'] = str_replace('\\', '/', substr_replace($_SERVER['SCRIPT_NAME'], '', -10));
    $siteUrl = $urlComponents['scheme'].'://'.$_SERVER['HTTP_HOST'].$urlComponents['path'];
    $siteDir = str_replace('\\', '/', dirname($indexFile ?: $_SERVER['SCRIPT_FILENAME']));

    if (!isset($urlComponents['href']))
        $urlComponents['href'] = $getHref($urlComponents['path']);

    Blox::addInfo([
        'cms' => [
            'dir'       => $GLOBALS['bloxdir'],
            'lang'      => $GLOBALS['cmslang'],
            'url'       => $GLOBALS['bloxurl'] ?: $siteUrl.'/BLOX',
        ],
        'site' => [
            'path'      => $urlComponents['path'], 
            'dir'       => $siteDir,
            'url'       => $siteUrl,
            'base-url'   => (isset($GLOBALS['baseurl'])) ? $GLOBALS['baseurl'] : $siteUrl.'/',
            'lang'      => $GLOBALS['lang'],
            'caching'   => $GLOBALS['caching'],
            'templater' => $GLOBALS['templater'],# PHP, Smarty 
            'nocache'   => $GLOBALS['nocache'] ?: false,
            'date-time-formats'=> $dateTimeFormats,
        ],
        'templates' => [
            'dir' => $GLOBALS['templatesdir'] ?: $siteDir.'/templates',
            'url' => $GLOBALS['templatesurl'] ?: $siteUrl.'/templates',
        ],
    ]);
    
    unset($GLOBALS['bloxversion']);
    unset($GLOBALS['dbhost']); 
    unset($GLOBALS['dbuser']); 
    unset($GLOBALS['dbpass']);
    unset($GLOBALS['dbname']);
    unset($GLOBALS['prefix']);
    unset($GLOBALS['baseurl']);
    unset($GLOBALS['bloxurl']);
    unset($GLOBALS['nocache']);
    unset($GLOBALS['caching']);
    unset($GLOBALS['cmslang']);
    unset($GLOBALS['lang']);
    unset($GLOBALS['templater']); 
    unset($GLOBALS['templatesdir']);
    unset($GLOBALS['templatesurl']);
    # Global current user settings
    if ($sessUserId = Blox::getSessUserId()) {
        # Admin automatically has the rights ['user-is-editor', 'user-is-activated']
        # All formulas ['user-is-admin', 'user-is-editor', 'user-is-activated', 'user-dont-see-edit-buttons','user-sees-block-boundaries', 'user-as-visitor'];
        $zz = Acl::getUsers(['user-id'=>$sessUserId])[0];
        unset($zz['password']);
        if (Proposition::get('user-is-admin', $sessUserId)) { 
            $info = $zz;
            $info['user-is-admin'] = true;
            $info['user-is-activated'] = true;
            $info['user-is-editor'] = true;}
        elseif (Proposition::get('user-is-activated', $sessUserId)) { 
            $info = $zz;
            $info['user-is-activated'] = true;
        }
        #
        if ($info['user-is-activated']) {
            foreach (['user-is-editor', 'bar-is-fixed', 'user-sees-block-boundaries', 'user-as-visitor'] as $formula)
                if (Proposition::get($formula, $sessUserId))
                    $info[$formula] = true;
            # groups of the user
            if (!$info['user-is-admin']) {
                foreach (Acl::getGroups(['user-id'=>$sessUserId, 'group-activated'=>true]) as $groupInfo) {
                    $info['groups'][] = $groupInfo;
                    if (!$info['user-is-editor']) { # user-is-editor via any group
                        if (Proposition::get('group-is-editor', $groupInfo['id']))
                            $info['user-is-editor'] = true;
                    }
                }
            }
            Blox::addInfo(['user'=>$info]);
        }
    }

    #Caching. Second attempt to get cache file. First one is above (#fastway)
    if (Blox::info('site','caching')) {
        if (!Blox::info('user','id')) {
            if (!isset($_GET['login']) && !isset($_GET['authenticate'])) {
                if ($zz = Cache::getFile($urlComponents['href'])) {
                    include $zz;
                    exit;
                }
            }   
        } else { # DEPRECATED since v14.0.16. REMOVE THIS
            $prefix = Blox::info('db','prefix');
            Sql::query('CREATE TABLE IF NOT EXISTS '.$prefix.'cachedpages (href varchar(332), `page-id` MEDIUMINT UNSIGNED, `file-name` varchar(332), PRIMARY KEY (`href`), INDEX(`page-id`), UNIQUE(`file-name`)) ENGINE=MyISAM DEFAULT CHARSET=utf8');
            Sql::query('CREATE TABLE IF NOT EXISTS '.$prefix.'cachedpagesblocks (`page-id` MEDIUMINT UNSIGNED, `block-id` MEDIUMINT UNSIGNED, UNIQUE(`block-id`), INDEX(`page-id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8');
            Files::makeDirIfNotExists(Blox::info('site','dir').'/cached');
        }
    }
    
    # adds session query if trans sid is prohibited
    if (Blox::info('user')) { # disabled visitors and robots, as the robots cling PHPSESSID
        if (SID && !ini_get('session.use_trans_sid'))# cookies off
            output_add_rewrite_var(session_name(), session_id()); # When submitting forms, the SID is passed via POST.
    } 

    if (Blox::info('user', 'user-is-admin')) { 
        foreach (['datafiles','temp'] as $d)                
            Files::makeDirIfNotExists(Blox::info('site','dir').'/'.$d);
    }
    
    //$siteSettings = Store::get('site-settings');
    if (is_array(Blox::info('site')) && is_array($siteSettings)) {
        if (function_exists('json_decode') && $siteSettings['emails']['transport'])
            $siteSettings['emails']['transport'] = json_decode($siteSettings['emails']['transport'], true);
        Blox::addInfo(['site'=>$siteSettings]);
    }
    # Convert to human-urls?        
    if (isset($_GET['page-info']) || isset($_GET['site-settings'])) {
        Blox::addInfo(['site'=>['human-urls'=>['convert'=>true]]]);        
    } elseif (Blox::info('user','id') && !Blox::info('user', 'user-as-visitor')) {
        if (isset($_GET['update']) || isset($_GET['update-sitemap-links']))
            if (Blox::info('site','human-urls','on') && !Blox::info('site','human-urls','disabled'))
                Blox::addInfo(['site'=>['human-urls'=>['convert'=>true]]]);
    } elseif (Blox::info('site','human-urls','on') && !Blox::info('site','human-urls','disabled'))
        Blox::addInfo(['site'=>['human-urls'=>['convert'=>true]]]);

    if ($GLOBALS['transliterate'])
        Blox::addInfo(['site'=>['transliterate'=>true]]);

    if ($GLOBALS['log-repeated-sql-queries']) {
        Blox::addInfo(['site'=>['log-repeated-sql-queries'=>true]]);
        unset($GLOBALS['log-repeated-sql-queries']); 
    }
    
    if (Proposition::get('site-is-down')) 
        Blox::addInfo(['site'=>['site-is-down'=>true]]);
    if (Proposition::get('editing-denied')) 
        Blox::addInfo(['site'=>['editing-denied'=>true]]);

    ##### Edit Permissions #####
	if (
        Blox::info('user', 'user-as-visitor') ||
        (!Blox::info('user', 'user-is-admin') && Blox::info('site','editing-denied')) ||
        isset($_GET['change']) #|| Blox::info('user','user-dont-see-edit-buttons')
        
    ) {
        Permission::add('record', ['', ''], ['edit'=>false, 'create'=>false]);
    }
    elseif (Blox::info('user','id')) 
    {
        if (Blox::info('user', 'user-is-admin')) {
            Permission::add('record', ['', ''], ['edit'=>true, 'create'=>true]);
            
        } elseif (Blox::info('user', 'user-is-activated')) {
            # user-is-editor
            if (Blox::info('user', 'user-is-editor')) {
                Permission::add('record', ['', ''], ['edit'=>true, 'create'=>true]);
            }
            # group-is-editor
            if ($groupsOfUser = Blox::info('user','groups')) {
                foreach ($groupsOfUser as $groupInfo) {
                    if ($groupInfo['activated'] && $groupInfo['group-is-editor']) {
                        Permission::add('record', ['', ''], ['edit'=>true, 'create'=>true]);
                    }
                }
            }
        }
    }
    ##### /Edit Permissions #####
    Blox::execute($urlComponents['href']);
})();






/**
 * Debug function
 */
function qq($obj=null)
{
    if (!Blox::info('site','blox-errors','on'))
        return;
    if ($GLOBALS['Blox']['qq-time'])
        $elapsedTime = microtime(true) - $GLOBALS['Blox']['qq-time'];
    # Determine parameter's varible name 
    $arr = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1)[0];#DEBUG_BACKTRACE_PROVIDE_OBJECT
    if (isset($arr['file'])) {
        $lines = file($arr['file']);
        $line = $lines[$arr['line']-1];
        preg_match_all('~\W*qq\s*\((.*?)\)\s*;~', $line, $matches); 
        if ($matches[1][1]) { # There are two qq-functions in one code line
            if ($GLOBALS['Blox']['qq-count']) {
                $argName = $matches[1][$GLOBALS['Blox']['qq-count']];
                $GLOBALS['Blox']['qq-count']++;
                if (!$matches[1][$GLOBALS['Blox']['qq-count']])
                    $GLOBALS['Blox']['qq-count']=0;
            }
            else {
                $argName = $matches[1][0];
                $GLOBALS['Blox']['qq-count']=1;
            }
        } else {
            $argName = $matches[1][0];
        }
    }
    
    if (!$GLOBALS['Blox']['qq-time'])
        $s .= "\n·························\n•••••••••••••••••••• ".date("Y-m-d H:i:s");
    $s .= 
        "\n························· "
        .($elapsedTime ? '+'.sprintf('%f', $elapsedTime) : '+0')
        .' sec ····· '
        .$arr['file'].':'.$arr['line']
    ;
    
    $s .= "\n";
    # Do not output the name of argument when ...
    if ($argName && !($argName[0] == '"' || $argName[0] == "'" || Str::isInteger($argName, ['zero','negative'])))
        $s .= $argName.' = ';
    $s .= print_r($obj, TRUE);
    # Delete log if big
    if (!$_SESSION['Blox']['---qq']) {
        $_SESSION['Blox']['---qq'] = true;
        if (file_exists('---qq.log')) {
            if (filesize('---qq.log') > 10485760) { # 10MB
                if (!unlink('---qq.log')) {
                    Blox::prompt('Failed deleting the file: ---qq.log', true);
                }
            }
        }
    }
    file_put_contents('---qq.log', Blox::encodeToUtf8($s), FILE_APPEND);
    $GLOBALS['Blox']['qq-time'] = microtime(true); # Start
}

/**
 * @deprecated since v14.0.14
 * Absolute positioning of code
 * @example echo pp($dat['edit'], -22);
 */
function pp($htm, $left=0, $top=0, $zIndex=null)
{
    if ($htm) {
        if ($zIndex) $zIndex = '; z-index:'.$zIndex;
        return '<div style="position: absolute; left:'.$left.'px; top:'.$top.'px'.$zIndex.'">'.$htm.'</div>';
    }
}



/** 
 * This funcrion considers 0 as not an empty value
 */
function isEmpty($val)
{
    if ($val==='' || is_null($val) || $val===false)
        return true;
    else
        return false;
}
