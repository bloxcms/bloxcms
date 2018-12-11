<?php
    
if ($settings['disable-multi-rec-filters']) {
    /**
     * Show all records in the block
     * You can  make a correct request (without filtering options) in the multirec edit button, but then you will have to detrmine settings for each block in Blox::getBlockHtm()
     * Show all records of the block that is you should remove requests except 'backward', 'sort'.
     */
    $template->assign('disableMultiRecFilters_show', true);
    Request::remove($regularId);
    foreach (['backward', 'sort'] as $filter) {
        if ($_GET[$filter]) {
            $query[$regularId][$filter] = $_GET[$filter];
            Request::set($query);
        }
    }
} elseif (isset($_GET['edit']) || $_GET['script']=='edit') {
    /**
     * Hide the checkbox 'disable-multi-rec-filters' if it is simple request and the checkbox is not hidden manually
     * The checkbox can be controlled only in the edit window
     */ 
    foreach (['limit', 'part', 'pick', 'search', 'single'] as $filter) {
        if ($_GET[$filter]){
            $template->assign('disableMultiRecFilters_show', true);
            break;
        }
    }
}
