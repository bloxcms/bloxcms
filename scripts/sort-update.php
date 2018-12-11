<?php
# NOT FOR EXTRADATA!
Request::set(); # Since requests are also passed independently by the GET method, $pagehref will not work. See. $sortQuery in Blox::getBlockHtm.php 
# Works independently from ?sort, that is, you can make a request to sort and save directly.
if (!Blox::info('user','id')) 
    Blox::execute('?error-document&code=403');
$pagehref = Blox::getPageHref();
Url::redirect($pagehref);
# If default sorting is set in tdd, it should be added to the global command
if (Request::get($regularId,'sort') === null && $tdd['params']['sort'])
    foreach ($tdd['params']['sort'] as $k=>$v)
        if (Str::isInteger($k))              
            Request::add([$regularId=>['sort'=>[$k=>$v]]]);
$regularId = Sql::sanitizeInteger($_GET['block']);
if (Request::get($regularId,'sort') !== null || Request::get($regularId,'backward') !== null) {        
    $blockInfo = Blox::getBlockInfo($regularId);
    $tpl = $blockInfo['tpl'];
    $tbl = Blox::getTbl($tpl);
    $srcBlockId = $blockInfo['src-block-id'];
    unset($_SESSION['Blox']['Request'][$srcBlockId]['part']['autoincrement']);
    $tdd = Tdd::get($blockInfo);
    if ($_POST['ignore-picks']){
        # KLUDGE: you could reset all Request:: get ($regularId, 'pick'), but so far only from the list
        if ($tdd['params']['pick']['key-fields'])
            foreach ($tdd['params']['pick']['key-fields'] as $f)
                Request::remove($regularId, 'pick', $f);
    } else
        $pickKeyFields = $tdd['params']['pick']['key-fields']; # Sort only this section of the catalog
    # If defined sorting $tdd['params'] do not affect !?
    if (Request::get($regularId,'sort')) {
        if ($tdd['params']['backward']) {
            foreach (Request::get($regularId,'sort') as $field => $order){
                if ($order == 'asc')
                    Request::add([$regularId=>['sort'=>[$field=>'desc']]]);
                elseif ($order == 'desc')
                    Request::add([$regularId=>['sort'=>[$field=>'asc']]]);
                else
                    Request::remove($regularId, 'sort', $field);
            }
        }
    }
    $tdd['params']['pick']['key-fields'] = $pickKeyFields;
    if (!Admin::checkSortByColumnsFilters($regularId, $tdd['params']['pick']['key-fields']))
        return;

    # Create a query to retrieve rows
    $whereSqls = [];
    $xSql = "AND $tbl.`block-id`=$srcBlockId";
    $isTab = true;
    $selectDataParams = Request::getSelectDataParams($tdd['types'], $regularId, $srcBlockId);
    $selectFromSqls = Request::getSelectFromSqls($tbl, $tdd['types'], $selectDataParams, $isTab);
    $selectFromSqls2['count'] = $selectFromSqls['count']; #Without  $selectFromSqls['columns'] to extract only conditions (WHERE ... ), although you can put here an empty array
    $filtersSql = Request::getTableSql($regularId, $tbl, $whereSqls, $xSql, $isTab, $tdd['params'], $selectFromSqls2, $selectDataParams);
    # Delete everything after ORDER BY. Although in the simple case it works with it, however, if sorting is performed by a field of type select, the query should have a JOIN.
    $retrieveSql = "SELECT $tbl.`rec-id`, $tbl.sort FROM ".Str::getStringAfterMark($selectFromSqls['count'], 'FROM ')." ".$filtersSql; # "SELECT COUNT($tbl.`rec-id`) FROM $tbl $joinList" there may be more FROM;
    # Extract all records to find out the values of the sort column
    $recIds = [];
    $sortNums = [];
    if ($result = Sql::query($retrieveSql)) {
        while ($row = $result->fetch_assoc()) {
            $recIds[] = $row['rec-id'];
            $sortNums[] = $row['sort'];
        }
        $result->free();
    }
    sort($sortNums, SORT_NATURAL);
    # Replace the values of the sort column
    foreach ($recIds as $i=>$recId){
        if ($recId !== '') {
            $sql = 'UPDATE '.$tbl.' SET sort='.$sortNums[$i].' WHERE `rec-id`=? AND `block-id`=?';
            Sql::query($sql, [$recId, $srcBlockId]);                
        }
    }
}