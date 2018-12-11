<?php
if (!Blox::info('user','id')) 
    Blox::execute('?error-document&code=403');
$pagehref = Blox::getPageHref();
Url::redirect($pagehref);
$regularId = Sql::sanitizeInteger($_GET['block']);
$blockInfo = Blox::getBlockInfo($regularId);
$tpl = $blockInfo['tpl'];
$tbl = Blox::getTbl($tpl);
# Process the list of numbers of sorted lines
$sortedRecIds = explode(',', $_POST['sorted-rows-list']);
if (empty($sortedRecIds))
    Blox::error("No sorted rows list in sort-manualy-update.php",1);
else {
    $tdd = Tdd::get($blockInfo);
    if ($tdd['params']['backward'])
        $sortedRecIds = array_reverse($sortedRecIds);
}
# Extract all records again to find out the values of the sort column
$placeholdersList = implode(',', array_fill(0, count($sortedRecIds), '?')); # Fill data by "?"
$sql = 'SELECT sort FROM '.$tbl.' WHERE `block-id`=? AND `rec-id` IN ('.$placeholdersList.')';
if ($result = Sql::query($sql, array_merge([$blockInfo['src-block-id']], $sortedRecIds))) {
    while ($row = $result->fetch_row())
        $sortNums[] = $row[0];
    $result->free();
}
sort($sortNums, SORT_NATURAL);
# Replace the values of the sort column
foreach ($sortedRecIds as $i=>$recId) {
    if ($recId !== '') {
        $sql = 'UPDATE '.$tbl.' SET sort=? WHERE `rec-id`=? AND `block-id`=?';
        Sql::query($sql, [$sortNums[$i], $recId, $blockInfo['src-block-id']]);
    }
}