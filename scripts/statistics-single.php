<?php

/**
 * @todo Use prepared statements after "foreach ($dates ...)"
 */

if (!(Blox::info('user','user-is-admin') || Blox::info('user','user-is-editor')))
    Blox::execute('?error-document&code=403');

if (isset($_GET['by-months'])) { # Back returning 
    $obj = $_SESSION['Blox']['stat']['obj'];
    $add = $_SESSION['Blox']['add'];
    $mark =  $_SESSION['Blox']['stat']['mark'];
} else {
    $obj = $_GET['obj'];        
    $_SESSION['Blox']['stat']['obj'] = $obj;
    $add = $_GET['add'];        
    $_SESSION['Blox']['add'] = $add;
    $mark = $_GET['mark'];        
    $_SESSION['Blox']['stat']['mark'] = $mark;
}


# ByMonthsToggle
if ('year'== $_SESSION['Blox']['stat']['time-interval'] or 'custom'== $_SESSION['Blox']['stat']['time-interval']){        
    if ($_GET['by-months']) 
        $byMonths = $_GET['by-months'];
    else
        $byMonths = 1;
    $template->assign('byMonths', $byMonths);
    $template->assign('showByMonthsToggle', 1);             
} else
    $byMonths = -1;

$statDat = getSingleStatistics($_SESSION['Blox']['stat']['subject'], $_SESSION['Blox']['stat']['from'], $_SESSION['Blox']['stat']['till'], $obj, $maxValue, $byMonths);
$terms['bar-title'] = $_SESSION['Blox']['stat']['subject-title'];

if ($mark)
    $obj = "<span style='color:#900'>$obj</span>";
$pageHeading = "{$_SESSION['Blox']['stat']['object-type']}: <b>$obj</b>";
if ($add)
    $pageHeading .= " ($add)";
$pageHeading .= "<br />{$_SESSION['Blox']['stat']['time-interval-title']}: {$_SESSION['Blox']['stat']['from']} ... {$_SESSION['Blox']['stat']['till']}";

$template->assign('pageHeading', $pageHeading);
$template->assign('statDat', $statDat); 
$template->assign('maxValue', $maxValue);    
$template->assign('obj', $obj);
$template->assign('terms', $terms);

include Blox::info('cms','dir')."/includes/button-cancel.php";
include Blox::info('cms','dir')."/includes/display.php";





function getSingleStatistics($statisticsSubject, $from, $till, $obj, &$maxValue, $byMonths)            
{
    # Safe SQL string    
    $statisticsSubject = Sql::sanitizeName($statisticsSubject);
    switch ($statisticsSubject) {
        case 'pages':   $obj = Sql::sanitizeInteger($obj); break;
        case 'updates': $obj = Sql::sanitizeInteger($obj); break;
        //default:        $obj = $obj;
    }

    $maxValue = 1;   
    if (1 == $byMonths)  # sum by months
    {
        $dates = genMonths($from, $till); 
        if ($_SESSION['Blox']['stat']['time-interval']=='custom') {
            $customTimeIntervalSql = " AND `date`>=? AND `date`<=?";
             $customTimeIntervalValues = [$_SESSION['Blox']['stat']['from'], $_SESSION['Blox']['stat']['till']];
        } else 
            $customTimeIntervalSql = '';

        $sqlValues[] = $obj;
        foreach ($dates as $date2)
        {
            $sql = "SELECT SUM(counter) FROM ".Blox::info('db','prefix')."count{$statisticsSubject} WHERE obj=? AND `date`>=? AND `date`<=?";// date obj counter              
            $sqlValues = [
                $obj, 
                $date2['year'].'-'.$date2['month'].'-01', 
                $date2['year'].'-'.$date2['month'].'-31'
            ];
            
            if ($customTimeIntervalValues) {
                $sql .= $customTimeIntervalSql;
                $sqlValues = array_merge($customTimeIntervalValues, $sqlValues);
            }
            
            if ($result = Sql::query($sql, $sqlValues)) {
                $row = $result->fetch_row();
                $result->free();
                $value = $row[0]; 
            }
            # Events 
            $event = '';              
            $sql = "SELECT description FROM ".Blox::info('db','prefix')."countevents WHERE `date` >=? AND `date`<=?";
            if ($result = Sql::query($sql, [$date2['year'].'-'.$date2['month'].'-01', $date2['year'].'-'.$date2['month'].'-31'])) { #
                while ($row = $result->fetch_assoc())
                    $event .= "{$row['description']}. ";
                $result->free();
            }

            $statDat[] = [
                'year'=>$date2['year'],
                'month'=>$date2['month'],
                'is-new-year'=>$date2['is-new-year'],
                'value'=>$value,
                'event'=>$event                
            ];

            if ($value > $maxValue)   
                $maxValue = $value;
        }   
    }
    else # by days
    {
        $dates = genDates($from, $till);   
        foreach ($dates as $date2) {
            # Data from counter
            $sql = "SELECT counter FROM ".Blox::info('db','prefix')."count{$statisticsSubject} WHERE obj=? AND `date`=? LIMIT 1";// `date` obj counter                      
            $result = Sql::query($sql, [$obj, $date2['year'].'-'.$date2['month'].'-'.$date2['day']]);
            $row = $result->fetch_assoc();
            $result->free();
            $value = $row['counter'];        
            
            # Events 
            $event = '';              
            $sql = 'SELECT description FROM '.Blox::info('db','prefix').'countevents WHERE `date`=?';
            $result = Sql::query($sql, [$date2['year'].'-'.$date2['month'].'-'.$date2['day']]);
            while ($row = $result->fetch_assoc())
                $event .= "{$row['description']}. ";
            $result->free();

            $statDat[] = [
                'year'=>$date2['year'],
                'month'=>$date2['month'],
                'day'=>$date2['day'],
                'is-new-year'=>$date2['is-new-year'],
                'is-new-month'=>$date2['is-new-month'],
                'is-sunday'=>$date2['is-sunday'],
                'value'=>$value,
                'event'=>$event                
            ];

            if ($value > $maxValue)   
                $maxValue = $value;
        } 
    }
    return $statDat;
}      



    
    
function genDates($from, $till)
{
    # Check dates
    list($y, $m, $d) = explode("-", $from);    
    list($tillY, $tillM, $tillD) = explode("-", $till);        
    $tillDate = $tillY.$tillM.$tillD; # for the while cycle

    $y = Sql::sanitizeInteger($y);
    $m = Sql::sanitizeInteger($m);
    $d = Sql::sanitizeInteger($d);
           
    $xDate = 0;      
    while ($xDate < $tillDate) {
    	if ($m < 10)
            $mm = "0".$m;            
        else $mm = $m;            
        
    	if ($d < 10)
            $dd = '0'.$d;
        else $dd = $d;            
        $xDate = $y.$mm.$dd;   
     
        # Determine Sundays
        $aa = getdate (mktime(0, 0, 0, $m, $d, $y));   
        if ($aa['wday']==0)
            $isSunday = true;     
            
        $dates[] = ['year'=>$y, 'month'=>$mm, 'day'=>$dd, 'is-new-year'=>$isNewYear, 'is-new-month'=>$isNewMonth, 'is-sunday'=>$isSunday]; 

        $isNewYear = false;
        $isNewMonth = false;
        $isSunday = false;        
        $d++;         
        
        if ($d > 28) {
            if (!checkdate($m, $d, $y)) {
            	$m++;
                $isNewMonth = true;
            	if ($m > 12) {
            		$y++;
                    $isNewYear = true;
            		$m = 1;                    
                }
            	$d = 1;
            }
        }
    }
    return $dates;
}
    
    
    
    


function genMonths($from, $till)
{        
    list($y, $m, $d) = explode("-", $from);    
    list($tillY, $tillM, $tillD) = explode("-", $till);

    $fromDate = $y.$m; # for the while cycle
    $tillDate = $tillY.$tillM; 

    $y = Sql::sanitizeInteger($y);
    $m = Sql::sanitizeInteger($m);

    $xDate = 0;
    while ($xDate < $tillDate) {
    	if ($m < 10)
            $mm = '0'.$m;            
        else $mm = $m;            
       
        $xDate = $y.$mm;   
        $dates[] = ['year'=>$y, 'month'=>$mm, 'is-new-year'=>$isNewYear]; 
    	$m++;
        
        $isNewYear = false;
    	if ($m > 12) {
    		$y++;
            $isNewYear = true;
    		$m = 1;                    
        }
  }
  return $dates;
}