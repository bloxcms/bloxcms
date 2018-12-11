<?php
/*
Made from scripts/update-sitemap-xml.php 
The script is run from this link http://trakdetal.ru/?update-sitemap-links 
After that, the list of links is taken here: http://trakdetal.ru/sitemap.htm
*/
$pagehref = Blox::getPageHref();
if (!Blox::info('user','id'))
    Url::redirect($pagehref,'exit');
else
    Url::redirect($pagehref);    
#
$getUrlElement = function($pageInfo) {
    return '<a href="'.Blox::info('site','url').'/'.$pageInfo['href'].'">'.$pageInfo['name'].'</a><br />'.PHP_EOL;
};
$str  = '<!DOCTYPE html>' . PHP_EOL;
$str .= '<html>'.PHP_EOL;
$str .= '<head>'.PHP_EOL;
$str .= '<title>Site map</title>'.PHP_EOL;
$str .= '<meta http-equiv="X-UA-Compatible" content="IE=8,IE=9,IE=10">'.PHP_EOL;
$str .= '<meta charset="utf-8" />'.PHP_EOL;
$str .= '</head>'.PHP_EOL;

################# Pages #######################    
$sql = 'SELECT * FROM '.Blox::info('db','prefix').'pages';
$hiddenPhrefsList = '';
if ($result = Sql::query($sql)) {
    while ($row = $result->fetch_assoc()) {
        if ($row['page-is-hidden']) {
            $hiddenPhrefsList .= '|'.$row['id'];
            continue;
        }
        
        $row['href'] = '?page='.$row['id'];
        if (Blox::info('site','human-urls','convert'))                
            $row['href'] = Router::convert($row['href']);           
        $str .= $getUrlElement($row);          
    }
    $result->free();
}

################# Pseudopages #######################
$sqlValues = [];
if ($hiddenPhrefsList) {
    $hiddenPhrefsList = substr($hiddenPhrefsList, 1);
    $where = " WHERE phref NOT REGEXP '\\\?page=($hiddenPhrefsList).*'";
    $sqlValues = ['\\\?page=($hiddenPhrefsList)'];
}

$sql = 'SELECT * FROM '.Blox::info('db','prefix').'pseudopages'.$where;
if ($result = Sql::query($sql, $sqlValues)) {       
    while ($row = $result->fetch_assoc()) {   
        if (Blox::info('site','human-urls','convert'))                
            $row['href'] = Router::convert($row['phref']);            
        else
            $row['href'] = htmlentities($row['phref'], ENT_QUOTES, 'utf-8'); # & to &amp;
        $str .= $getUrlElement($row);            
    }
}
$result->free();

$str .= '</body>'.PHP_EOL;
$str .= '</html>';

if (file_put_contents('sitemap.htm', $str))
    Blox::prompt($terms['success']);
else
    Blox::prompt($terms['error'],  true);

@chmod('sitemap.htm', 0644);