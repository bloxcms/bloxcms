<?php


# NOT USED. NOT DEBUGGED

#1
// Проблемы в разделе 'restore'==$action
// Не принимает чужие дампы с sql-комметариями
// Пробовал применить removeSqlComments($str),
// но строки поступают рубленные на куски по 4096 байт //fread($fh, 4096)
// из за чего часть коментария уходит в другой кусок
// То есть комметарии убирать нельзя.
// но данный скрипт не работает с комментариями

#2
// Пробовал удалять старые таблицы сначала dropOldTables(), но это опасно
// Сделать мягкое удаление. То есть сначала заменить с помощью уже имеющихся запросов DROP в sql, а незамененные удалить.
// позже сделать автоматическую генерацию DROP если их нет в sql


/* Алгоритм

Если префикса нет то
    проверить если таблицы с префиксом
    если нет то
        удалить все
    иначе
        определить все префиксы
        удалить таблицы все без префикса
Иначе
    Удалить таблицы с префиксом

Не выполнять запросы кроме
    DROP TABLE IF EXISTS `snooker_
    CREATE TABLE `snooker_
    INSERT INTO `snooker_

После удачной установки
    удалить dump
*/
    # !! Временно заблокировал восстановление
    //if ('restore'==$_GET['action']) return;










    if (!Blox::info('user','id')) 
        Blox::execute('?error-document&code=403');
    if (!Blox::info('user','user-is-admin')) 
        Blox::execute('?error-document&code=403');
    //dump($tplFile, $template, $terms);



    //$siteDir = dirname($_SERVER['SCRIPT_FILENAME']);
    $GLOBALS['Blox']['backup-dir'] = Blox::info('site','dir').'/temp';

    $backupBaseName = Blox::info('db','prefix')."dump";
    $backupFileExtensions = [sql,gz,bz2];

    $is_safe_mode = ini_get('safe_mode') == '1' ? 1 : 0;
    if (!$is_safe_mode && function_exists('set_time_limit')) set_time_limit(600);

	// Версия MySQL вида 40101
	preg_match("/^(\d+)\.(\d+)\.(\d+)/", mysqli_get_server_info(Sql::getDb()), $m);
    $mysqlVersion = sprintf("%d%02d%02d", $m[1], $m[2], $m[3]);


    // Кодировка соединения с MySQL
    // auto - автоматический выбор (устанавливается кодировка таблицы), cp1251 - windows-1251, и т.п.
    $dbCharset = 'auto';

    // Кодировка соединения с MySQL при восстановлении
    // На случай переноса со старых версий MySQL (до 4.1), у которых не указана кодировка таблиц в дампе
    // При добавлении 'forced->', к примеру 'forced->cp1251', кодировка таблиц при восстановлении будет принудительно заменена на cp1251
    // Можно также указывать сравнение нужное к примеру 'cp1251_ukrainian_ci' или 'forced->cp1251_ukrainian_ci'
    $restoreCharset = 'utf-8';
	if (preg_match("/^(forced->)?(([a-z0-9]+)(\_\w+)?)$/", $restoreCharset, $matches))
    {
		$forcedCharset  = $matches[1] == 'forced->';
		$restoreCharset = $matches[3];
		$restoreCollate = $matches[4] ? ' COLLATE ' . $matches[2] : '';
	}
    else
    {
        $restoreCharset = '';
    	$forcedCharset  = false;
        $restoreCollate = '';
    }

    $report = '';


    $action = isset($_GET['action']) ? $_GET['action'] : '';


















    if ('before-backup'==$action)
    {
        include Blox::info('cms','dir')."/includes/buttons-submit.php";
        $backupFilesInfo = readFilesInfoSorted($GLOBALS['Blox']['backup-dir'], $backupFileExtensions);
        $template->assign('backupFilesInfo', $backupFilesInfo);
        include Blox::info('cms','dir')."/includes/buttons-submit.php";
    }

    elseif ('backup'==$action)//Создается резервная копия БД
    {
        # команда на удаление некоторых копий
        if ($_POST['files-to-del'])
            foreach ($_POST['files-to-del'] as $fileToDel)
                unlink($GLOBALS['Blox']['backup-dir'].'/'.$fileToDel);

        // убрать ниже
        //if (!file_exists($GLOBALS['Blox']['backup-dir']) && !$is_safe_mode) {mkdir($GLOBALS['Blox']['backup-dir'], 0777) || trigger_error('Failed to create backup directory', E_USER_ERROR);}
        if (!file_exists($GLOBALS['Blox']['backup-dir']) && !$is_safe_mode)
        {
            mkdir($GLOBALS['Blox']['backup-dir'], 0755) || trigger_error('Failed to create backup directory', E_USER_ERROR);
            chmod($GLOBALS['Blox']['backup-dir'], 0755);
        }

		$tables = [];

        $result = Sql::query("SHOW TABLES LIKE '".Blox::info('db','prefix')."%'");
        while ($row = $result->fetch_row()) {
            if (preg_match("/^".Blox::info('db','prefix')."/", $row[0]))
                $tables[] = $row[0];
        }
        $result->free();
        //$result = Sql::query("SHOW TABLES");
        //while($row = mysql_fetch_array($result)) {$tables[] = $row[0];}

		$numOfTables = count($tables);
		// Определение размеров таблиц
		$result = Sql::query("SHOW TABLE STATUS");
		$rowsNum = []; $tabCharset = []; $tabType = [];

		while($item = $result->fetch_assoc())
        {
			if (in_array($item['Name'], $tables))
            {
				$item['Rows'] = empty($item['Rows']) ? 0 : $item['Rows'];
				$rowsNum[$item['Name']] = $item['Rows'];
				$size += $item['Data_length'];
                $dataLimit = 1; // Ограничение размера данных на одно обращение к БД (MB)
				$tabSize[$item['Name']] = 1 + round($dataLimit * 1048576 / ($item['Avg_row_length'] + 1));


				if ($item['Collation'] && preg_match("/^([a-z0-9]+)_/i", $item['Collation'], $m)) {
					$tabCharset[$item['Name']] = $m[1];
				}
				$tabType[$item['Name']] = isset($item['Engine']) ? $item['Engine'] : $item['Type'];
			}
		}
        $result->free();

        if (empty($_POST['compress-method'])) $compressMethod = 'sql';
        else $compressMethod = $_POST['compress-method'];

        if ('gz' ==$compressMethod)     $suffix = 'sql.gz';
        elseif ('sql'==$compressMethod) $suffix = 'sql';
        elseif ('bz2'==$compressMethod) $suffix = 'sql.bz2';

        $backupFileName = $backupBaseName.".".$suffix;
        $fh = fileOpen($backupFileName, "w");

		$result = Sql::query("SET SQL_QUOTE_SHOW_CREATE = 1");
		// Кодировка соединения по умолчанию
		if ($mysqlVersion > 40101 && $dbCharset != 'auto') {
			//Sql::query("SET NAMES '" . $dbCharset . "'") or trigger_error ($terms['failed-conn-charset']."<br />" . mysqli_error(Sql::getDb()), E_USER_ERROR);
            Sql::query("SET NAMES '" . $dbCharset . "'") or trigger_error (Sql::getDb()->error), E_USER_ERROR);
			$lastCharset = $dbCharset;
		}
		else
			$lastCharset = '';

        $t=0;
        foreach ($tables AS $table)
        {
			// Выставляем кодировку соединения соответствующую кодировке таблицы
			if ($mysqlVersion > 40101 && $tabCharset[$table] != $lastCharset)
            {
				if ($dbCharset == 'auto') {
					//Sql::query("SET NAMES '" . $tabCharset[$table] . "'") or trigger_error ($terms['failed-conn-charset']."<br />" . mysqli_error(Sql::getDb()), E_USER_ERROR);
                    Sql::query("SET NAMES '" . $tabCharset[$table] . "'") or trigger_error (Sql::getDb()->error), E_USER_ERROR);
			        $report .= "{$terms['connection-charset']}: <b>{$tabCharset[$table]}</b><br />";
					$lastCharset = $tabCharset[$table];
				}
				else
					$report .= "{$terms['not-coincided']}: <b>{$tabCharset[$table]}</b> ({$table}) / <b>{$dbCharset}</b><br />";

			}

        	// Создание таблицы
			$result = Sql::query("SHOW CREATE TABLE `{$table}`");
        	$tab = mysqli_fetch_array($result);
            $result->free();
			$tab = preg_replace("/(default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP|DEFAULT CHARSET=\w+|COLLATE=\w+|character set \w+|collate \w+)/iu", '/*!40101 \\1 */', $tab);
        	fileWrite($fh, "DROP TABLE IF EXISTS `{$table}`;\n{$tab[1]};\n\n");
            //fileWrite($fh, "{$tab[1]};\n\n");


        	// Проверяем нужно ли дампить данные
            $onlyCreate = ['MRG_MyISAM','MERGE','HEAP','MEMORY'];// Типы таблиц у которых сохраняется только структура
        	if (in_array($tabType[$table], $onlyCreate)) {continue;}
        	// Опредеделяем типы столбцов
            $NumericColumn = [];
            $result = Sql::query("SHOW COLUMNS FROM `{$table}`");
            $field = 0;
            while($col = $result->fetch_row()) {
            	$NumericColumn[$field++] = preg_match("/^(\w*int|year)/", $col[1]) ? 1 : 0;
            }
            $result->free();
			$fields = $field;
            $from = 0;
			$limit = $tabSize[$table];

			if ($rowsNum[$table] > 0)
            {
    			$i = 0;
    			fileWrite($fh, "INSERT INTO `{$table}` VALUES");
                while(($result = Sql::query("SELECT * FROM `{$table}` LIMIT {$from}, {$limit}")) && ($total = $result->num_rows))
                {
                		while($row = $result->fetch_row())
                        {
                        	$i++;
        					$t++;
    						for($k = 0; $k < $fields; $k++)
                            {
                        		if ($NumericColumn[$k])
                        		    $row[$k] = isset($row[$k]) ? $row[$k] : "NULL";
                        		else
                        			$row[$k] = isset($row[$k]) ? "'" . $row[$k] . "'" : "NULL";
                        	}
        					fileWrite($fh, ($i == 1 ? "" : ",") . "\n(" . implode(", ", $row) . ")");
                   		}
    					$result->free();
    					if ($total < $limit) break;
        				$from += $limit;
                }
                $result->free();
    			fileWrite($fh, ";\n\n");
            }
		}


        //if ($numOfTables > 0)
        $numOfTables = $numOfTables-1;
        $report .= "{$terms['num-of-tables']}: <b>{$numOfTables}</b>";
        $template->assign('report', $report);

        $fsize = filesize($GLOBALS['Blox']['backup-dir']."/".$backupFileName);
        if ($fsize)
        {
            $fsize = $fsize / 1024;
            $fsize = round($fsize);
            $fsize = '('.$fsize.' KB)';
            $template->assign('fsize', $fsize);
        }



        $template->assign('backupFileName', $backupFileName);

        fileClose($fh,$backupFileName);
    }//backup

    elseif ('before-restore'==$action)
    {
        $backupFilesInfo = readFilesInfoSorted($GLOBALS['Blox']['backup-dir'], $backupFileExtensions);
        $template->assign('backupFilesInfo', $backupFilesInfo);
        include Blox::info('cms','dir')."/includes/buttons-submit.php";
    }

    elseif ('restore'==$action)//Восстановление БД из резервной копии
    {
		$backupFileName = isset($_POST['backup-file-name']) ? $_POST['backup-file-name'] : '';

        if ('upload' == $backupFileName)
        {
            $backupFileName = 'uploaded_'.$_FILES['upload-file']['name'];
            if (move_uploaded_file($_FILES['upload-file']['tmp_name'], $GLOBALS['Blox']['backup-dir'].'/'.$backupFileName))
            {
                chmod($GLOBALS['Blox']['backup-dir'].'/'.$backupFileName, 0666);
            }
            else
                exit($terms['not-uploaded']);
        }

		$fh = fileOpen($backupFileName, "r");


		$fileCache = $sql = $table = $insert = '';
        $queryLen = $execute = $t = $i = $affRows = 0;
		$numOfTables = 0;

		// Установка кодировки соединения
		if ($mysqlVersion > 40101 && ($dbCharset != 'auto' || $forcedCharset))
        { // Кодировка по умолчанию, если в дампе не указана кодировка
            Sql::query("SET NAMES '" . $restoreCharset . "'") or trigger_error (Sql::getDb()->error), E_USER_ERROR);

			$report .= "{$terms['connection-charset']}: <b>{$restoreCharset}</b><br />";
			$lastCharset = $restoreCharset;
		}
		else
			$lastCharset = '';
		$lastShowed = '';

        //dropOldTables();


		while(($str = fileReadStr($fh)) !== false)
        {

			$queryLen += mb_strlen($str);

            $pattern = "/^(INSERT INTO `?(".Blox::info('db','prefix')."[^` ]+)`? .*?VALUES)(.*)$/i";
			if (!$insert && preg_match($pattern, $str, $m))
            {
				if ($table != $m[2])
                {
				    $table = $m[2];
					$numOfTables++;
					$lastShowed = $table;
					$i = 0;
				}
        	    $insert = $m[1] . ' ';
				$sql .= $m[3];
        	}
			else
            {
        		$sql .= $str;
				if ($insert)
                {
				    $i++;
    				$t++;
				}
        	}

			if (!$insert && preg_match("/^CREATE TABLE (IF NOT EXISTS )?`?(".Blox::info('db','prefix')."[^` ]+)`?/i", $str, $m) && $table != $m[2])
            {
				$table = $m[2];
				$insert = '';
				$numOfTables++;
				$isCreate = true;
				$i = 0;
			}



			if ($sql) // CREATE TABLE and values
            {
			    if (preg_match("/;$/", $str)) {
            		$sql = rtrim($insert . $sql, ";");
					if (empty($insert)) {
						if ($mysqlVersion < 40101) {
				    		$sql = preg_replace("/ENGINE\s?=/u", "TYPE=", $sql);
						}
						elseif (preg_match("/CREATE TABLE/i", $sql))
                        {
							// Выставляем кодировку соединения
                            if (preg_match("/(CHARACTER SET|CHARSET)[=\s]+(\w+)/i", $sql, $charset))
                            {
								if (!$forcedCharset && $charset[2] != $lastCharset)
                                {
									if ($dbCharset == 'auto')
                                    {
										//Sql::query("SET NAMES '" . $charset[2] . "'") or trigger_error ($terms['failed-conn-charset']."<br />{$sql}<br />" . Sql::getDb()->error), E_USER_ERROR);
                                        Sql::query("SET NAMES '" . $charset[2] . "'") or trigger_error (Sql::getDb()->error), E_USER_ERROR);
										$report .= "{$terms['connection-charset']}: <b>{$charset[2]}</b><br />";
										$lastCharset = $charset[2];
									}
									else
                                    {
										$report .= "{$terms['not-coincided']}: <b>{$charset[2]}</b> ({$table}) / <b>{$restoreCharset}</b><br />";
									}
								}
								// Меняем кодировку если указано форсировать кодировку
								if ($forcedCharset)
                                {
									$sql = preg_replace("/(\/\*!\d+\s)?((COLLATE)[=\s]+)\w+(\s+\*\/)?/iu", '', $sql);
                                    $sql = preg_replace("/((CHARACTER SET|CHARSET)[=\s]+)\w+/iu", "\\1" . $restoreCharset . $restoreCollate, $sql);
								}
							}
							elseif ($dbCharset == 'auto')
                            { // Вставляем кодировку для таблиц, если она не указана и установлена auto кодировка
								$sql .= ' DEFAULT CHARSET='.$restoreCharset.$restoreCollate;
								if ($restoreCharset != $lastCharset)
                                {
                                    Sql::query("SET NAMES '{$restoreCharset}'") or trigger_error (Sql::getDb()->error), E_USER_ERROR);
									$report .= "{$terms['connection-charset']}: <b>{$restoreCharset}</b><br />";
									$lastCharset = $restoreCharset;
								}
							}
						}
						if ($lastShowed != $table)
                            $lastShowed = $table;
					}
					elseif ($mysqlVersion > 40101 && empty($lastCharset))
                    { // Устанавливаем кодировку на случай если отсутствует CREATE TABLE
                        Sql::query("SET $restoreCharset '" . $restoreCharset . "'") or trigger_error (Sql::getDb()->error), E_USER_ERROR);
						$report .= "{$terms['connection-charset']}: <b>{$restoreCharset}</b><br />";
						$lastCharset = $restoreCharset;
					}
            		$insert = '';
            	    $execute = 1;
            	}
            	if ($queryLen >= 65536 && preg_match("/,$/", $str)) {
            		$sql = rtrim($insert . $sql, ",");
            	    $execute = 1;
            	}
    			if ($execute)
                {
                    $num = Sql::query($sql) or trigger_error (Sql::getDb()->error), E_USER_ERROR);
					if (preg_match("/^insert/i", $sql))
            		    $affRows += $num;
            		$sql = '';
            		$queryLen = 0;
            		$execute = 0;
            	}
			}
		}


		$report .= $terms['tables-created'].': <b>'.$numOfTables.'</b><br />';
		$report .= $terms['rows-added'].': <b>'.$affRows.'</b><br />';

		fileClose($fh,$backupFileName);
	}


    $template->assign('report', $report);
    $terms['bar-title'] = $terms[$action];
    $template->assign('action', $action);
    $template->assign('terms', $terms);
    include Blox::info('cms','dir')."/includes/button-cancel.php";
    include Blox::info('cms','dir')."/includes/display.php";


// end


    function readFilesInfoSorted($folder, $fileExtensions)
    {
        $filesInfo = readFilesInfo($folder, $fileExtensions);
        sortTable($filesInfo, 'time', 0);
        return $filesInfo;
    }

    function sortTable(&$tab, $sortCol, $isDesc)
    {
        if (empty($tab)) return;
        foreach ($tab as $row => $record)
            $sortVector[$row]  = $record[$sortCol];

        if (empty($isDesc))
            array_multisort($sortVector, $tab);
        else
            array_multisort($sortVector, SORT_DESC, $tab);
    }

    function readFilesInfo($folder, $fileExtensions)
    {
        if ($handle = opendir($folder))
        {
            while (false !== ($fl = readdir($handle)))
            {
                if ($fl != '.' && $fl != '..')
                {
                    if ($fileExtensions)
                    {
                        foreach ($fileExtensions as $ext)
                            if ($ext)
                                if (preg_match("/\.$ext$/i", $fl))
                                    $fileNames[] = $fl;
                    }
                    else
                    {
                        $fileNames[] = $fl;
                    }
                }
            }
            # Это внести в блок выше
            if ($fileNames)
            {
                ksort($fileNames, SORT_NATURAL);
                $i = 0;
                foreach ($fileNames as $fileName)
                {
                    $fl = $folder."/".$fileName;
                    $files[$i]['name'] = $fileName;
                    $files[$i]['size'] = round(filesize($fl)/1024);
                    $files[$i]['time'] = date('Y-m-d H:i', filemtime($fl));
                    $i++;
                }
            }
            closedir($handle);
            return $files;
        }
    }




	function fileOpen($fileName, $mode)
    {
        global $compressMethod;
        $compLevel = 9;
        if (empty($compressMethod)) $compressMethod = getFileExtension($fileName);

		if ('gz' == $compressMethod)
            return @gzopen($GLOBALS['Blox']['backup-dir']."/".$fileName, "{$mode}b{$compLevel}");
		elseif ('bz2' == $compressMethod)
            return @bzopen($GLOBALS['Blox']['backup-dir']."/".$fileName, "{$mode}b{$compLevel}");
		elseif ('sql' == $compressMethod)
    		return fopen($GLOBALS['Blox']['backup-dir']."/".$fileName, "{$mode}b");
	}



	function fileWrite($fh, $str)
    {
        global $compressMethod;
		if ('gz' == $compressMethod) gzwrite($fh, $str);
        elseif ('bz2' == $compressMethod) bzwrite($fh, $str);
		else fwrite($fh, $str);
	}


	function fileReadStr($fh)
    {
        global $fileCache;
		$string = '';
		$fileCache = ltrim($fileCache);//Strip whitespace from the beginning of a string
		$pos = mb_strpos($fileCache, "\n", 0);//Find position of first occurrence of a string // offset parameter '0' allows you to specify which character in $fileCache to start searching


		if ($pos < 1)
        {
			while (!$string && ($str = fileRead($fh)))
            {
    			$pos = mb_strpos($str, "\n", 0);
    			if ($pos === false)
    			    $fileCache .= $str;
    			else
                {
    				$string = $fileCache . substr($str, 0, $pos);
                    //$string = $fileCache . substr($str, 0, $pos) . "\n";
    				$fileCache = substr($str, $pos + 1);
    			}
    		}

			if (!$str)
            {
			    if ($fileCache)
                {
					$string = $fileCache;
					$fileCache = '';
				    return trim($string);
				}
			    return false;
			}
		}
		else
        {
  			$string = substr($fileCache, 0, $pos);
            //$string = substr($fileCache, 0, $pos) . "\n";
  			$fileCache = substr($fileCache, $pos + 1);
		}
		return trim($string);
	}



	function fileRead($fh)
    {
        global $compressMethod;
		if ('gz' == $compressMethod) return gzread($fh, 4096);
        elseif ('bz2' == $compressMethod) return bzread($fh, 4096);
		else return fread($fh, 4096);
	}



	function fileClose($fh,$backupFileName)
    {
        global $compressMethod;
		if ($compressMethod == 'gz') gzclose($fh);
        elseif ($compressMethod == 'bz2') bzclose($fh);
		else fclose($fh);
		@chmod($GLOBALS['Blox']['backup-dir']."/".$backupFileName, 0666);
	}


    function getFileExtension($fileName)
    {
        $position = mb_strrpos($fileName, '.'); //Find position of last occurrence of a char
        if ($position > 0)
            return substr($fileName, $position + 1);
    }




    /*
    function dropOldTables()
    {
        # удалить (свои) таблицы без префикса
        if (!Blox::info('db','prefix'))
        {
            # получить список чужих префиксов
            $result = Sql::query('SHOW TABLES');
            while ($row = $result->fetch_row())
            {
                if (preg_match("/^(.+?)blockdata$/i", $row[0], $aa))
                    $prefixes[] = $aa[1];
            }
            Это внести в блок выше
            # удалить таблицы без (чужих) префиксов
            if ($prefixes)
            {
                $prefixes = array_unique($prefixes);
                $result = Sql::query('SHOW TABLES');
                while ($row = $result->fetch_row())
                {
                    foreach ($prefixes as $prefix)
                    {
                        $pattern = '/^'.$prefix.'.+?$/i';
                        if (!preg_match($pattern, $row[0]))
                            Sql::query('DROP TABLE IF EXISTS '.$row[0]);
                    }
                }
            }
        }
        # удалить таблицы со своим префиксом
        else
        {
            $result = Sql::query("SHOW TABLES LIKE '".Blox::info('db','prefix')."%'");
            while ($row = $result->fetch_row())
                Sql::query('DROP TABLE IF EXISTS '.$row[0]);
        }
    }
    */


    /*
    function removeSqlComments(&$str)
    {

        //Пробовал
        //$str = preg_replace("/(--|#)?.*\n/u", "", $str);
        //$str = preg_replace("/(--|#)?.*\n/u", "\n", $str);


        $query = '';
        $sqlLines = explode("\n",$str);
        $str = '';
        foreach($sqlLines as $sqlLine)
        {
            //$sqlLine = trim($sqlLine);//?
            //$aa = '';
            if (($sqlLine != "") && (substr($sqlLine, 0, 2) != "--") && (substr($sqlLine, 0, 1) != "#"))
            {
                $str .= $sqlLine."\n";//  (") not (')
                //$aa = 'QQ';
            }
            //else $aa = 'XX';
        }
        //$str = $str2;


    }
    */



?>