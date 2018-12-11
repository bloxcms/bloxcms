<?php

    $dataDirs= ['assigned', 'datafiles', 'temp'];
    $pubDirs = ['cached', 'compiled', 'datafiles', 'xfiles', Blox::info('cms','dir').'/style'];
    $template->assign('notCreatedDirs', getNotCreatedDirs($dataDirs));

    if (Blox::info('user','user-is-admin'))
    {
        if (isset($_GET['del-files'])) {
            delFiles($dataDirs);
            $template->assign('filesDeleted', 1);
            if ($tables = getTables())
                $template->assign('tableNames', $tables);
            ##unset($_SESSION['Blox']['delete-resource']);
        } elseif (isset($_GET['del-tables'])) {
            deleteOldTables();
            $template->assign('tables-deleted', 1);
            createTables();
            $template->assign('tablesCreated', 1);
            $template->assign('notPubDirs', getNotPubDirs($pubDirs));
            ##unset($_SESSION['Blox']['delete-resource']);
            unset($_SESSION);
        } elseif ($outputTree = outputTree($dataDirs)) {
            $template->assign('outputTree', $outputTree );
        }
    	elseif ($tables = getTables()) {
            $template->assign('tableNames', $tables);
        } else {
            createTables();
            $template->assign('notPubDirs', getNotPubDirs($pubDirs));
        }
    }
    # visitor
    else {
        ##unset($_SESSION['Blox']['delete-resource']);
    	if ($outputTree = outputTree($dataDirs)) {
            $template->assign('outputTree', $outputTree );
        } elseif ($tables = getTables()) {
            $prefix = Blox::info('db','prefix');
            $keyTablesExist = true;
            $keyTables = ['blocks', 'pages', 'users'];
            foreach ($keyTables as $keyTable) {
                if (!in_array($prefix.$keyTable, $tables)) {
                    $keyTablesExist = false;
                    break;
                }
            }

            if ($keyTablesExist)
                $template->assign('tableNames', $tables);
            else {
                deleteOldTables();
                createTables();
        		$template->assign('notPubDirs', getNotPubDirs($pubDirs));
            }
        } else {
            createTables();
    		$template->assign('notPubDirs', getNotPubDirs($pubDirs));
    	}

    }

    include Blox::info('cms','dir').'/includes/buttons-submit.php';
    include Blox::info('cms','dir').'/includes/display.php';







    function outputTree($dataDirs)
    {
        $output = '';
        foreach ($dataDirs as $dataDir) {
            $node['files'] = getNodesSort($dataDir);
            $node['name'] = $dataDir;
            $output .= outputNode($node);
        }
        if ($output)
            ;##$_SESSION['Blox']['delete-resource'] = 'del-files';
        return $output;
    }




    function getNodesSort($srcDir)
    {
    	$resource = opendir($srcDir);
    	while (false !== ($fname = readdir($resource))) {
        	if ($fname != '.' && $fname != '..') {
            	if (is_file($srcDir.'/'.$fname))
            	    $fnames[] = $fname;
            	else
            	    $dnames[] = $fname;
    	    }
    	}
        $i = 0;
    	if (isset($dnames)) {
        	natcasesort($dnames);
        	foreach($dnames as $dname) {
                $nodes[$i]['name'] = $dname;
            	$nodes[$i]['files'] = getNodesSort($srcDir.'/'.$dname);
                $i++;
            }
    	}

    	if (isset($fnames)) {
        	natcasesort($fnames);
        	foreach ($fnames as $fname) {
                $nodes[$i]['name'] = $fname;
                $i++;
            }
    	}
    	closedir($resource);
        if ($nodes)
            return $nodes;
    }




    function outputNode($node)
    {
        if (array_key_exists('files', $node)) {
            if ($node['files'] && is_array($node['files'])) {
                $bb = '';
                foreach ($node['files'] as $n) {
                    if ($aa = outputNode($n))
                        $bb .= '<li>'.$aa.'</li>'; }
                if ($bb)
                    $output .= $node['name'].'/<ul style="list-style-type:none;margin:0px 0px 0px 20px">'.$bb.'</ul>'; 
            }
        }
        elseif ($node['name'] != 'Thumbs.db')
                $output .= $node['name'];

        if ($output)
            return $output;
    }








    function getNotPubDirs($pubDirs)
    {
        foreach ($pubDirs as $pubDir) {
            if (file_exists($pubDir) && is_dir($pubDir)) {
                # TODO verifyFileMode($fl, $newMode)
                $aa = fileperms($pubDir);
                $perm = mb_substr(sprintf('%o', $aa), -4);
                $perm = (int) $perm;
                if ($perm < 755) 
                    if (!chmod($pubDir, 0755))
                    	$chDirs[] = $pubDir;
            }
        }
        return $chDirs;
    }






    function getNotCreatedDirs($dataDirs)
    {
        # If there are no folders, then create
        foreach ($dataDirs as $dataDir) {   
            if (!Files::makeDirIfNotExists($dataDir))
       			$aa[] = $dataDir;
        }
        return $aa;
    }




    function deleteOldTables()
    {
        if (empty($_SESSION['Blox']['tables-to-del']))
            return;
        foreach ($_SESSION['Blox']['tables-to-del'] as $t) {
            $sql = 'DROP TABLE IF EXISTS `'.$t.'`';
            Sql::query($sql);
        }
        unset($_SESSION['Blox']['tables-to-del']);
    }



    function createTables()
    {
        $prefix = Blox::info('db','prefix');
        $refTypes = ['block', 'page', 'file', 'rec-id'];
        foreach ($refTypes as $refType)
            $sqlTypes[$refType] = Admin::reduceToSqlType($refType);
        $sql = 'CREATE TABLE '.$prefix.'pages (
            id '.$sqlTypes['page'].' NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `parent-page-id` '.$sqlTypes['page'].' UNSIGNED NOT NULL default 0,
            `parent-page-is-adopted` TINYINT(1) UNSIGNED NOT NULL default 0,
            `outer-block-id` '.$sqlTypes['block'].',
            `main-block-id` '.$sqlTypes['block'].',
            `page-is-hidden` TINYINT(1) UNSIGNED NOT NULL default 0,
            alias varchar(332) NOT NULL default \'\',
            name varchar(332) NOT NULL default \'\',
            title varchar(332) NOT NULL default \'\',
            keywords text,            
            description text,
            lastmod DATETIME,
            changefreq ENUM(\'always\',\'hourly\',\'daily\',\'weekly\',\'monthly\',\'yearly\',\'never\'),
            priority DECIMAL(2,1) UNSIGNED,
            `pseudo-pages-title-prefix` varchar(332) NOT NULL default \'\',
            INDEX (alias)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8';
        Sql::query($sql);
        Sql::query('INSERT '.$prefix.'pages (id, `outer-block-id`) VALUES (1, 1)');
        
        $sql = 'CREATE TABLE '.$prefix.'pseudopages (
            `key` VARCHAR(99),
            `parent-key` VARCHAR(99),
            `parent-page-is-adopted` TINYINT(1) UNSIGNED NOT NULL default 0,
            phref varchar(332),            
            alias varchar(332) NOT NULL default \'\',
            name varchar(332) NOT NULL default \'\',
            title varchar(332) NOT NULL default \'\',
            keywords text,
            description text,
            lastmod DATETIME,
            changefreq ENUM(\'always\',\'hourly\',\'daily\',\'weekly\',\'monthly\',\'yearly\',\'never\'),
            priority DECIMAL(2,1) UNSIGNED,           
            UNIQUE INDEX (`key`), INDEX (`parent-key`), UNIQUE INDEX (phref), INDEX(alias)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8';
        Sql::query($sql);

        $sql = 'CREATE TABLE '.$prefix.'blocks (
            id '.$sqlTypes['block'].' NOT NULL AUTO_INCREMENT PRIMARY KEY,
            tpl varchar(332) NOT NULL default \'\',
            `delegated-id` '.$sqlTypes['block'].' NOT NULL default 0,
            `parent-block-id` '.$sqlTypes['block'].' NOT NULL default 0,
            `parent-rec-id` '.$sqlTypes['rec-id'].',
            `parent-field` TINYINT UNSIGNED NOT NULL default 0,
            `is-xdat` TINYINT(1) UNSIGNED NOT NULL default 0,
            `settings` BLOB
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8';
        Sql::query($sql);
        Sql::query('INSERT '.$prefix.'blocks (id) VALUES (1)');
        Sql::query('CREATE TABLE '.$prefix.'users (
            id int(6) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            login VARCHAR(24),
            password VARCHAR(255) NOT NULL default \'\',
            email VARCHAR(99) NOT NULL default \'\',
            personalname VARCHAR(99) NOT NULL default \'\',
            familyname VARCHAR(99) NOT NULL default \'\',
            ip VARBINARY(16),
            regdate	date,
            visitdate date,
            notes VARCHAR(24) NOT NULL default \'\'
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8');
        Sql::query('INSERT '.$prefix.'users (id, login, password) VALUES (1, \'admin\', \''.password_hash('admin', PASSWORD_DEFAULT).'\')');
        Sql::query('CREATE TABLE '.$prefix.'authattempts (
            type VARCHAR(64),
            value VARCHAR(64),
            time INT(11) UNSIGNED NOT NULL DEFAULT 0,
            counter SMALLINT(11) UNSIGNED NOT NULL DEFAULT 0,
            UNIQUE (type, value), INDEX (time)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8');
        # DEPRECATED since v14.1.1. Remove this in v.15
        Sql::query('CREATE TABLE '.$prefix.'usersloginattempts (
            login VARCHAR(24),
            time INT(11) UNSIGNED NOT NULL DEFAULT 0,
            counter SMALLINT(11) UNSIGNED NOT NULL DEFAULT 0,
            UNIQUE INDEX (login), INDEX (time)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8');
        
        $sql = 'CREATE TABLE '.$prefix.'lastdelegated (
            tpl varchar(332) UNIQUE NOT NULL,
            `block-id` '.$sqlTypes['block'].' UNIQUE NOT NULL
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8';
        Sql::query($sql);

        $sql = 'CREATE TABLE '.$prefix.'selectlistblocks (
            `edit-block-id` '.$sqlTypes['block'].' NOT NULL default 0,
            `edit-field` TINYINT UNSIGNED NOT NULL DEFAULT 0,
            `select-list-block-id` '.$sqlTypes['block'].' NOT NULL default 0,
            PRIMARY KEY (`edit-block-id`,`edit-field`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8';
        Sql::query($sql);

        $sql = 'CREATE TABLE '.$prefix.'dependentselectlistblocks (
            `parent-list-block-id` '.$sqlTypes['block'].',
            `parent-list-rec-id` '.$sqlTypes['rec-id'].',
            `select-list-block-id` '.$sqlTypes['block'].',
            PRIMARY KEY (`parent-list-block-id`,`parent-list-rec-id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8';
        Sql::query($sql);
        # Cache        
        Sql::query('CREATE TABLE IF NOT EXISTS '.$prefix.'cachedpages (href varchar(332), `page-id` MEDIUMINT UNSIGNED, `file-name` varchar(332), PRIMARY KEY (`href`), INDEX(`page-id`), UNIQUE(`file-name`)) ENGINE=MyISAM DEFAULT CHARSET=utf8');
        Sql::query('CREATE TABLE IF NOT EXISTS '.$prefix.'cachedpagesblocks (`page-id` MEDIUMINT UNSIGNED, `block-id` MEDIUMINT UNSIGNED, UNIQUE(`block-id`), INDEX(`page-id`)) ENGINE=MyISAM DEFAULT CHARSET=utf8');
        Files::makeDirIfNotExists(Blox::info('site','dir').'/cached');
        
        # propositions
        $sql = 'CREATE TABLE IF NOT EXISTS '.$prefix.'propositions (
            `formula` VARCHAR(128) NOT NULL default \'\',
            `subject-id` INT(4) UNSIGNED NOT NULL DEFAULT 0,
            `object-id`  INT(4) UNSIGNED NOT NULL DEFAULT 0,
            PRIMARY KEY (`formula`,`subject-id`,`object-id`),
            INDEX (`formula`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8';
        Sql::query($sql);

        # Groups
        Sql::query('CREATE TABLE '.$prefix.'groups (
            id int(6) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name varchar(332),
            activated tinyint(1) unsigned not null  default 1,
            description TEXT,
            regdate	date
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8');
        
    
        # Statistics
        foreach (['block', 'page', 'file'] as $refType)
            $sqlTypes[$refType] = Admin::reduceToSqlType($refType);
        $statSubjects = [
    		'pages'=>        $sqlTypes['page'],
            'updates'=>      $sqlTypes['block'],
            'downloads'=>    $sqlTypes['file'],
    		'remotehosts'=>  'varchar(40) not null default \'\'',#  for IPv6. Better: VARBINARY(16)
    		'referers'=>     'varchar(332) not null default \'\'' # 333 wrong
        ];
        foreach ($statSubjects as $subj => $dataType) {
            $sql = 'CREATE TABLE IF NOT EXISTS '.$prefix.'count'.$subj.' (`date` DATE, obj '.$dataType.', counter MEDIUMINT UNSIGNED,  PRIMARY KEY (`date`, obj)) ENGINE=MyISAM DEFAULT CHARSET=utf8';
            Sql::query($sql);
        }
        Sql::query('CREATE TABLE IF NOT EXISTS '.$prefix.'countevents (id SMALLINT NOT NULL AUTO_INCREMENT PRIMARY KEY, `date` DATE, description varchar(332) NOT NULL default \'\', KEY(`date`)) ENGINE=MyISAM DEFAULT CHARSET=utf8');
        #
        Store::set('blox-version', Blox::getVersion());
        Store::set('site-settings', ['blox-errors'=>['on'=>true]]);
        Proposition::set('user-is-admin', 1, null, true);
    }




    function delFiles($dirs)
    {
        foreach ($dirs as $d)
            removeFile($d);
    }


    function removeFile($d)
    {
        if ($handle = opendir($d)) {
            while (false !== ($item = readdir($handle))) {
                if ($item != '.' && $item != '..') {
                    $f = $d.'/'.$item;
                    if (is_dir($f)) {
                        removeFile($f);
                        rmdir($f);
                    } else
                        unlink($f);
                }
            }
            closedir($handle);
        }
    }


    function getTables()
    {
        $prefix = Blox::info('db','prefix');
        # Old tables to delete
        if ($result = Sql::query("SHOW TABLES LIKE '{$prefix}%'")) {
            while ($row = $result->fetch_row()) {
                if (preg_match('~^'.$prefix.'~', $row[0]))
                    $tables[] = $row[0];
            }
            $result->free();
        }

        if ($tables) {
            ##$_SESSION['Blox']['delete-resource'] = 'del-tables';
            $_SESSION['Blox']['tables-to-del'] = $tables; //?
            return $tables;
        }
    }
