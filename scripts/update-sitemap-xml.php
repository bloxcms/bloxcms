<?php
# There is also a script update-sitemap-links.php to generate site links

$pagehref = Blox::getPageHref();
if (!Blox::info('user','id'))
    Url::redirect($pagehref,'exit');
else
    Url::redirect($pagehref);
#
$getUrlElement = function($pageInfo) {
    if (Blox::info('site','human-urls','on')) {
        Blox::addInfo(['site'=>['human-urls'=>['convert'=>true]]]); # For edit mode
        $href = Router::convert($pageInfo['phref']);
    } else
        $href = $pageInfo['phref'];
    ;
    $href = htmlentities($href, ENT_QUOTES, 'utf-8'); # & to &amp;

    $str .= '    <url>'.PHP_EOL;
    $str .= '        <loc>'.Blox::info('site','url').'/'.$href.'</loc>'.PHP_EOL;
    if ($pageInfo['lastmod'])
        $str .= '        <lastmod>'.date('c', strtotime($pageInfo['lastmod'])).'</lastmod>'.PHP_EOL; # Or: $aa = new DateTime('2010-12-30 23:21:46'); echo $aa->format(DateTime::ATOM); // https://stackoverflow.com/questions/5322285/how-do-i-convert-datetime-to-iso-8601-in-php
    if ($pageInfo['changefreq'])
        $str .= '        <changefreq>'.$pageInfo['changefreq'].'</changefreq>'.PHP_EOL;
    if ($pageInfo['priority'])
        $str .= '        <priority>'.$pageInfo['priority'].'</priority>'.PHP_EOL;
    $str .= '    </url>'.PHP_EOL;  
    return $str;
};

$str  = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL;
$str .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'.PHP_EOL;

################# Pages #######################    
$sql = 'SELECT * FROM '.Blox::info('db','prefix').'pages';
$hiddenPhrefsList = '';
if ($result = Sql::query($sql)) {
    while ($row = $result->fetch_assoc()) {
        if ($row['page-is-hidden']) {
            $hiddenPhrefsList .= '|'.$row['id'];
            continue;
        }            
        $row['phref'] = '?page='.$row['id'];
        $str .= $getUrlElement($row);          
    }
    $result->free();
}


################# Pseudopages #######################
$sqlValues = [];
if ($hiddenPhrefsList) {
    $hiddenPhrefsList = substr($hiddenPhrefsList, 1);
    $where = " WHERE phref NOT REGEXP ?";
    $sqlValues = ['\\\?page=($hiddenPhrefsList)'];
}
//NOT REGEXP '^[:digit:]$';
//AND phref LIKE '?page=".$pageId."&%'" OR phref LIKE '?page=".$pageId."&%'"; 
//http://stackoverflow.com/questions/3732246/mysql-in-with-like

$sql = 'SELECT * FROM '.Blox::info('db','prefix').'pseudopages'.$where;
if ($result = Sql::query($sql, $sqlValues)) {       
    while ($row = $result->fetch_assoc())
        $str .= $getUrlElement($row);
    $result->free();
}

$str .= '</urlset>' . PHP_EOL;
if (file_put_contents('sitemap.xml', $str)) {
    Blox::prompt($terms['success']);
    # Add Sitemap to robots.txt
    (function() {
        $fl = 'robots.txt';
        $smStr = 'Sitemap: '.Blox::info('site','url').'/sitemap.xml';
        if (file_exists($fl)) {
            if ($txt = file_get_contents($fl)) {
                $txt = preg_replace( "~(\n|).*?Sitemap.*(\n|)~iu", '', $txt);
                $txt = preg_replace( "~((\n|\s)*User-agent)~iu", "\n".$smStr."$1", $txt, -1, $counter );
                if ($counter) {
                    $txt = preg_replace( '~(sitemap\.xml)([^\n]*?User-agent)~iu', "$1\n$2", $txt);
                    $txt = preg_replace( "~(\n|).*?Sitemap.*(\n|)~iu", '', $txt, 1);}
                else
                    $txt .= "\nUser-agent: *";
                $txt .= "\n".$smStr;
            }
        }
        if (empty($txt))
            $txt = "User-agent: *\n".$smStr;            
        file_put_contents($fl, $txt);
    })();
}
else
    Blox::prompt($terms['error'],  true);
@chmod('sitemap.xml', 0644);