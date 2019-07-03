<?php

    if (!(Blox::info('user','user-is-admin') || Blox::info('user','user-is-editor'))) 
        Blox::execute('?error-document&code=403');

    # Entering from statistics.tpl
    if (isset($_POST['statistics-subject'])) # or $_POST['time-interval']
    {
        $statisticsSubject = Sql::sanitizeName($_POST['statistics-subject']);
        $timeInterval = $_POST['time-interval'];
        if ($timeInterval=='custom') {
            # Dates
            list($y, $m, $d) = explode("-", $_POST['from']);
            $from = date("Y-m-d", mktime(0, 0, 0, $m, $d, $y));
            $fromDate = str_replace('-', '', $from);

            list($y, $m, $d) = explode("-", $_POST['till']);
            $till = date("Y-m-d", mktime(0, 0, 0, $m, $d, $y));
            $tillDate = str_replace('-', '', $till);

            if ($fromDate > $tillDate)
                Blox::prompt($terms['invalid-dates-order'], true);
        }
        $_SESSION['Blox']['stat']['subject'] = $statisticsSubject; # To return to main statistics
        $_SESSION['Blox']['stat']['time-interval'] = $timeInterval;
    } else { # Returned from statistics-single.tpl
        $statisticsSubject = $_SESSION['Blox']['stat']['subject'];
        $timeInterval = $_SESSION['Blox']['stat']['time-interval'];
        if ($timeInterval=='custom') {
            $from = $_SESSION['Blox']['stat']['from'];
            $till = $_SESSION['Blox']['stat']['till'];
        }
    }

    if ('custom' == $timeInterval)
        $timeIntervalTitle = $terms['custom'];
    else {
        $now = getdate();
        $year = $now['year'];
        $month = $now['mon'];
        $day = $now['mday'];
        $till = date("Y-m-d", mktime(0, 0, 0, $month, $day, $year));
        switch ($timeInterval) {
            case 'day':
                $timeIntervalTitle = $terms['day'];
                break;
            case 'week':
                $day = $day - 7;
                $timeIntervalTitle = $terms['week'];
                break;
            case 'month':
                $month--;
                $timeIntervalTitle = $terms['month'];
                break;
            case 'year':
                $year--;
                $timeIntervalTitle = $terms['year'];
                break;
        }
        $from = date("Y-m-d", mktime(0, 0, 0, $month, $day, $year));
    }

    $_SESSION['Blox']['stat']['time-interval-title'] = $timeIntervalTitle;
    $_SESSION['Blox']['stat']['from'] = $from;
    $_SESSION['Blox']['stat']['till'] = $till;

    $statDat = getTotalStatistics($statisticsSubject, $from, $till, $maxSum, $totalSum, $terms);# , &$maxSum, &$totalSum
    # it is possible to get $showTotalStatisticsTitle from statistics.tpl via POST too
    switch ($statisticsSubject) {
        case 'pages':
            $_SESSION['Blox']['stat']['subject-title'] = $terms['title-pages'];
            $_SESSION['Blox']['stat']['object-type'] = $terms['pages'];
            break;
        case 'updates':
            $_SESSION['Blox']['stat']['subject-title'] = $terms['title-updates'];
            $_SESSION['Blox']['stat']['object-type'] = $terms['updates'];
            break;
        case 'downloads':
            $_SESSION['Blox']['stat']['subject-title'] = $terms['title-downloads'];
            $_SESSION['Blox']['stat']['object-type'] = $terms['downloads'];
            break;
        case 'remotehosts':
            $_SESSION['Blox']['stat']['subject-title'] = $terms['title-remotehosts'];
            $_SESSION['Blox']['stat']['object-type'] = $terms['remotehosts'];
            break;
        case 'referers':
            $_SESSION['Blox']['stat']['subject-title'] = $terms['title-referers'];
            $_SESSION['Blox']['stat']['object-type'] = $terms['referers'];
            break;
    }

    $terms['bar-title'] = $_SESSION['Blox']['stat']['subject-title'];
    $template->assign('statisticsObjectType', $_SESSION['Blox']['stat']['object-type']);
    $pageHeading = "<b>{$_SESSION['Blox']['stat']['subject-title']}</b><br />{$timeIntervalTitle}: {$from} ... {$till}";
    if ($statisticsSubject == 'referers')
        $pageHeading .= "<div class='small' style='margin-top:5px; color:red'>{$terms['urls-without-params']}</div>";
    elseif ($statisticsSubject == 'remotehosts')
        $pageHeading .= "<div class='small' style='margin-top:5px'>{$terms['host-is-comp-adress']}</div>";

    $template->assign('pageHeading', $pageHeading);
    $template->assign('statTableObjType', $_SESSION['Blox']['stat']['object-type']);
    $template->assign('statDat', $statDat);
    $template->assign('timeInterval', $timeInterval);
    $template->assign('maxSum', $maxSum);
    $template->assign('totalSum', $totalSum);
    $template->assign('terms', $terms);

    include Blox::info('cms','dir')."/includes/button-cancel.php";
    include Blox::info('cms','dir')."/includes/display.php";






    function getTotalStatistics($statisticsSubject, $from, $till, &$maxSum, &$totalSum, $terms)
    {

        $maxSum = 1;
        $totalSum = 0;
        $hostsnamesTable = '';
        $hostsnamesField = '';


        if ('pages' == $statisticsSubject)
            ;
        elseif ('referers' == $statisticsSubject)
            $thisHostAndUri = dirname($_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']."x"); # "x" is fakefile
        elseif ('remotehosts' == $statisticsSubject) {
            $remotehostsCounter = 0;
            $maxExecutionTime = ini_get('max_execution_time');
            $startTime = time();
            Sql::query('CREATE TABLE IF NOT EXISTS '.Blox::info('db','prefix').'countremotehosts_names (ip VARBINARY(16) UNIQUE NOT NULL, `host-name`  varchar(332), INDEX (ip)) ENGINE=MyISAM DEFAULT CHARSET=utf8');
            $hostsnamesTable = 'LEFT JOIN '.Blox::info('db','prefix').'countremotehosts_names AS nt ON nt.ip = ct.obj';
            $hostsnamesField = ', nt.hostname';# AS hostname
        }

        $result = Sql::query('
            SELECT ct.obj, SUM(ct.counter) AS sum '.$hostsnamesField.'
            FROM '.Blox::info('db','prefix').'count'.$statisticsSubject.' as ct
            '.$hostsnamesTable.'
            WHERE ct.`date`>=? AND ct.`date`<=? GROUP BY ct.obj ORDER BY sum DESC
        ', [$from, $till]);
        
        if (!$result)
            return;

        while ($row = $result->fetch_assoc()) {
            if ('pages' == $statisticsSubject) {
                $pageInfo = Router::getPageInfoById($row['obj']);
                $row['note'] = $pageInfo['title'];
                $row['title'] = "<a href='?page={$row['obj']}' title='{$terms['to-page1']}' target='_blank'>{$row['obj']}</a>";
            } elseif ('updates' == $statisticsSubject) {
                $blockInfo = Blox::getBlockInfo($row['obj']);
                $row['note'] = $blockInfo['tpl'];
                $row['title'] = "<a href='?page=".Blox::getBlockPageId($row['obj'])."' title='{$terms['to-page2']}' target='_blank'>{$row['obj']}</a>";
            } elseif ('referers' == $statisticsSubject) {
                # Transitions from unknown and inner pages
                if (empty($row['obj'])) {
                    $row['title'] = $terms['unknown-pages'];
                    $row['truncate'] = true;
                } elseif ($row['obj'] == $thisHostAndUri) {
                    $row['title'] = "<a href='//{$row['obj']}' target='_blank'>".Url::punyDecode(Text::truncate(Text::stripTags($row['obj'],'strip-quotes'), 50, 'plain'))."</a> {$terms['inner-links']}";
                    $row['truncate'] = true;
                } else {
                    $row['title'] = "<a href='//{$row['obj']}' target='_blank'>".Url::punyDecode(Text::truncate(Text::stripTags($row['obj'],'strip-quotes'), 50, 'plain'))."</a>";
                    # Re-count max as unknown and inner pages do not participate in the chart
                    if ($row['sum'] > $maxSum)
                        $maxSum = $row['sum'];
                }
            } elseif ('remotehosts' == $statisticsSubject) {
                if ($remotehostsCounter >= 100) {
                    Blox::prompt($terms['most-visiting-hosts']);
                    break;
                }
                $remotehostsCounter++;
                $gethostbyaddr_startTime= time();
                
                if (empty($row['hostname'])) { # Domain name has not yet been determined
                    #DEPRECATED: since 9.3.8.  $row['obj']=$hostName. TODO the inverse transform
                    if (inet_pton($row['obj']) === false) { # Is it an IP?
                        if ($hostAddr = gethostbyname($row['obj'])) {
                            if ($hostAddr == $row['obj'])
                                $hostAddr = '—';
                            $hostName = $row['obj'];
                            $row['obj'] = $hostAddr;
                        }
                    } elseif ($hostName = gethostbyaddr($row['obj'])) { # $row['obj'] is IP
                        if ($hostName == $row['obj']) # The domain name is not detected
                            $hostName = '—';
                        Sql::query('REPLACE '.Blox::info('db','prefix').'countremotehosts_names VALUES(?,?)', [$row['obj'], $hostName]);
                        # Others will be written next time
                    }
                } else
                    $hostName = $row['hostname'];

                $row['note'] = $hostName;
                if (time() - $startTime > $maxExecutionTime + 10) { # 10 sec
                    Blox::prompt($terms['script-is-timed']);
                    break;
                }
            }

            $statDat[] = $row;
            $totalSum = $totalSum + $row['sum'];
            if ('referers' != $statisticsSubject) { # since some columns are hidden
                if ($row['sum'] > $maxSum)
                    $maxSum = $row['sum'];
            }
        }
        $result->free();
        return $statDat;
    }

