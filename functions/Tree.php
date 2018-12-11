<?php

    
class Tree
{
     private static $treeBlocks = [];
     
     
    /**
     * Get the tree of references (blocks, pages), starting from $blockId. Delegating blocks are not included to the tree 
     * 
     * @param int $blockId Block from which to start building the tree
     * @param array $refTypes Array of types to retrieve. Available reference types: ['block','page'].
     * @param array $search Array of pare "type"=>"value" to search. Available types: 'block','page'. If this param is set, the method returns not tree, but the "address" of the found data, i.e. array: ['block-id'=>...,'tpl'=>...,'rec-id'=>...,'field'=>...].
     * @return array Tree array
     *
     * @examples
     *    $refTypes = ['block'];                    Walks through all included blocks of the $blockId.
     *    $refTypes = ['block', 'page'];            Walks through all included blocks and follow all pages to build the tree.
     *    $refTypes = ['page'];                     Returns params of pages of the block, do not follow pages and included blocks.
     *    $search['page'] = 123;
     *    $search['block'] = 99;
     */
    public static function get($blockId, $refTypes, $search=null)
    {
        if (empty($refTypes))
            Blox::error(Blox::getTerms('no-ref-types'));
        
        $blockInfo = Blox::getBlockInfo($blockId);
        if ($blockInfo['tpl']) {
            $tpl = $blockInfo['tpl'];
            $tdd = Tdd::get($blockInfo);
            # List of fields to retrieve data
            
            if ($typesDetails = Tdd::getTypesDetails($tdd['types'], $refTypes, 'only-name')) {
                $fieldNamesList = '';
                foreach ($typesDetails as $field => $aa)
                    $fieldNamesList .= ', dat'.$field;
            }

            if ($fieldNamesList)
            {
                $fieldNamesList = '`rec-id`'.$fieldNamesList;
                $tbl = Blox::getTbl($tpl);
                $sql = 'SELECT '.$fieldNamesList.' FROM '.$tbl.' WHERE `block-id`=? ORDER BY sort';
                if ($result = Sql::query($sql, [$blockId])) {
                    # TODO: The field numbers are already known. It is better to extract all fields and not make $fieldNamesList, and do not make $fieldName->$field (dat3->3)
                    while ($row = $result->fetch_assoc())
                    {
                        $recId = array_shift($row); # Remove first element for search.
                        foreach ($row as $fieldName => $datum)
                        {
                            $field = substr($fieldName, 3);  # remove initial 'dat'
                            $type = $typesDetails[$field]['name'];
                            if (isset($search) && $search[$type] == $datum) {
                                return ['block-id'=>$blockId, 'tpl'=>$tpl, 'rec-id'=>$recId, 'field'=>$field];
                            }
                             
                            if ('block' == $type) {
                                    if ($aa = self::get($datum, $refTypes, $search)) {
                                        if (isset($search))
                                            return $aa;
                                        else
                                            $refData['blocks'][] = $aa;
                                    }
                            } elseif ('page'  == $type) {
                                if ($pageInfo = Router::getPageInfoById($datum)) {
                                    if (in_array('block', $refTypes)) {
                                        if ($aa = self::get($pageInfo['outer-block-id'], $refTypes, $search)) {
                                            if (isset($search))
                                                return $aa;
                                            else
                                                $pageInfo['ref-data']['blocks'][0] = $aa;
                                        }
                                    }
                                    if (!isset($search))
                                        $refData['pages'][] = $pageInfo;
                                }
                            }
                        }
                    }
                    $result->free();
                }
            }
            if (!isset($search))
                $blockInfo['ref-data'] = $refData;
        }
        
        if (isset($search)) {
            return null; # Search element is not found
        } else {
            return $blockInfo;
        }
    }



    public static function getBlocksHtm($blocksTree)
    {
        $pagehrefQuery = '&pagehref='.Blox::getPageHref(true);        
        $htm .= '<ul>';
        foreach ($blocksTree as $blockInfo) {
            if ($blockInfo['delegated-id'])
                $aa = '<span style="color:#900">'.$blockInfo['id'].'</span>';
            else
                $aa = $blockInfo['id'];

            if ($blockInfo['tpl'])
                $tdd = Tdd::get($blockInfo);
            
            # Do not move this inside "if ($blockInfo['tpl'])"
            $htm .= '<li>';
                $htm .= '<span class="list-item">&nbsp;</span>';
                if (Blox::info('user','user-is-admin') || (Blox::info('user','user-is-editor') && !$tdd['params']['no-edit-buttons']))
                    $htm .= Admin::getEditButton($blockInfo['id'], ['block-info'=>$blockInfo, 'tdd'=>$tdd, 'pagehref-query'=>$pagehrefQuery]).' ';
                $htm .= '<b>'.$aa.'</b>';
                
                if ($blockInfo['tpl'])
                    $htm .= ' ('.$blockInfo['tpl'].')';
                if ($blockInfo['ref-data']['blocks']) # Sublist
                    $htm .= self::getBlocksHtm($blockInfo['ref-data']['blocks']);
            $htm .= '</li>';
        }
        $htm .= '</ul>';
        return $htm;
    }
    


    public static function getBlocks($blocksTree)
    {
        self::$treeBlocks = [];
        self::getBlocks2($blocksTree);
        return self::$treeBlocks;
    }
    private static function getBlocks2($blocksTree)
    {
        if (empty($blocksTree)) 
            return;

        foreach ($blocksTree as $blockInfo) {
            if ($blockInfo['id'])
                self::$treeBlocks[] = $blockInfo['id'];
            if ($blockInfo['ref-data']['blocks'])
                self::getBlocks2($blockInfo['ref-data']['blocks']);
        }
    }



    public static function renewParentsIds()
    {
        $refTypes = ['block', 'page'];
        $blockTreeParams = self::get(1, $refTypes);
        self::renewParentsIds2($blockTreeParams, 1);
    }
    private static function renewParentsIds2($blockTreeParams, $parentPageId)
    {
        if ($blockTreeParams['ref-data'])
        {
            foreach ($blockTreeParams['ref-data'] as $type => $aa)
            {
                if ($aa) {
                    foreach ($aa as $refDatParams) {
                        if ($type == 'blocks') {
                            if ($blockTreeParams['id'] && $refDatParams['id']) {
                                if ($blockTreeParams['id'] != $refDatParams['parent-block-id']) { # Update parent's id
                                    $sql = 'UPDATE '.Blox::info('db','prefix').'blocks SET `parent-block-id`=? WHERE id=?';
                                    Sql::query($sql, [$blockTreeParams['id'], $refDatParams['id']]);
                                    Blox::error(sprintf(Blox::getTerms('incorrect-parent-block-id'), $refDatParams['id']));
                                }
                            }
                            # Go deeper
                            self::renewParentsIds2($refDatParams, $parentPageId);
                        }
                        elseif ($type == 'pages') {
                            if ($refDatParams['id']) {                                
                                if ($parentPageId != $refDatParams['parent-page-id']) {# # Update parent's id
                                    if ($refDatParams['parent-page-is-adopted']) # NOTTESTED
                                        Blox::error(sprintf(Blox::getTerms('parent-page-id-not-renewed'), $refDatParams['id'], $parentPageId));
                                    else {
                                        $sql = 'UPDATE '.Blox::info('db','prefix').'pages SET `parent-page-id`=? WHERE id=?';
                                        Sql::query($sql, [$parentPageId], $refDatParams['id']);
                                        Blox::error(sprintf(Blox::getTerms('parent-page-id-is-renewed'), $refDatParams['id']));
                                    }
                                }
                            }
                            # Go deeper
                            self::renewParentsIds2($refDatParams['ref-data']['blocks'][0], $refDatParams['id']);
                        }
                    }
                }
            }
        }
    }

}