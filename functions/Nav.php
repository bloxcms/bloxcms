<?php
/**
 * @todo numOfItems mode overloads server. Increase time or return to # Reload method of NumOfItems
 * @todo Optimize moment to evaluate of NumOfItems, Do not this every time
 * @todo The edit button to do this manually (note that the only editable entry) or to emulate $params['span-edit-buttons']. While this option should be put in tdd.
 * @todo Now to build "numOfItems" you must reload page a few times, i.e. invoke Nav::init() with new $tab, then Nav::getNodes(). Do this via DB or via ajax reloads of the nav block.
 * @todo getTree need?
 */
class Nav
{
    private static $info=[];
    
    /**
     * @param array $blockInfo  This is an array with two required elements ['src-block-id'=>..., 'tpl'=>...]
     * @param array $tab Standard template variable with multi-record data
     * @param array $fields array of matching template fields to this method fields
     * @param array $options {
     *     ...
     *     @var string $itemsPick    Additional pick-request to a target block for numOfItems counting
     * }    
     * @return bool
     */
    public static function init($blockInfo, $tab, $fields=[], $options=[]) 
    {   
        $srcBlockId = $blockInfo['src-block-id'];
        if (null === self::get([$srcBlockId, 'active-nav-id'])) # not initialized yet
        { 
            self::add([$srcBlockId,'block-info'], $blockInfo);
            self::add([$srcBlockId,'fields'], $fields);
            $activeNavId = Request::get($options['target-block-id'],'pick', $options['target-field'], 'eq') ?: 0;
            self::add([$srcBlockId,'active-nav-id'], $activeNavId);
            $targetPageId = $options['target-page-id'] ?: $targetPageId = ($options['target-block-id']) ? Blox::getBlockPageId($options['target-block-id']) : Blox::getPageId();
            self::add([$srcBlockId,'target-page-id'], $targetPageId);
            self::add([$srcBlockId,'options'], $options);
            #Create an array of "children", convert the array tab to an array with the key = rec-id
            $children = []; # `rec-id` => [childRecId1, childRecId2, ...]
            $records = []; # `rec-id` => $dat
            # Start page
            $records[0] = [
                'href' => Router::convert('?page='.$targetPageId),
                'name' => Router::getPageInfoById($targetPageId)['name'],
                'altHeadline' => $options['initial-headline'],
            ];
            $numOfItemsField = self::get([$srcBlockId,'fields','num-of-items']);
            foreach ($tab as $dat) {
                if ($numOfItemsField && $dat[$numOfItemsField]==0 && !$dat['edit'] && !$options['show-empty-branches']) # Do not show for visitors categories without items
                    continue;
                $record = [];
                if ($dat['rec']) {
                    foreach ($fields as $purpose => $field) {
                        if ($field)
                            $record[$purpose] = $dat[$field];
                    }
                    $record['href'] = Router::convert(
                        '?page='.$targetPageId.'&block='.$options['target-block-id'].'&p['.$options['target-field'].']='.$dat['rec'], 
                        [
                            'key'=> $srcBlockId.'-'.$dat['rec'],
                            'parent-key'=> ($record['parent-id']) ? $srcBlockId.'-'.$record['parent-id'] : '', # If there is no parent record, then there shouldn't be a parent block either?
                            'name'=>$record['name']
                        ]
                    );
                    foreach ($dat as $field => $val) # just additionally 
                        $record[$field] = $val;
                    $records[$dat['rec']] = $record;
                    if ($record['parent-id'] != 0) 
                        $children[$record['parent-id']][] = $dat['rec'];
                    else 
                        $children[0][] = $dat['rec'];
                } else {
                    $newRec = $dat; # Data for newrec button
                }
            }
            self::add([$srcBlockId,'records'], $records);
            self::add([$srcBlockId,'children'], $children);
            #Building a chain. The chain can be created only by climbing from the bottom up the parents.
            $ancestors = [];
            $navId2 = $activeNavId;
            while ($navId2 != 0) {
                $navId2 = $records[$navId2]['parent-id'];
                #if ($navId2 != 0) # Do I need to remove the zero level (introductory page)?
                    $ancestors[] = $navId2;
            }
            self::add([$srcBlockId,'ancestors'], $ancestors);
            if (isset($newRec))
                self::add([$srcBlockId,'new-rec'], $newRec);
        }

        if (Blox::info('user','user-is-admin') || Blox::info('user','user-is-editor')) {
            if ($numOfItemsField) {
                $numOfItemsUpdateTimestamp = Store::get('bloxNav'.$srcBlockId)['num-of-items-update-timestamp'];
                if (!$numOfItemsUpdateTimestamp) {
                    self::updateNumOfItems($srcBlockId); # Reliable variant to control updates of numOfItems based on using Nav::resetNumOfItems()
                } elseif (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') { # Safety variant using UPDATE_TIME of a table. It does not work on Windows!
                    # Get update time of the target block. 
                    if (!$options['target-tpl'])
                        $options['target-tpl'] = Blox::getBlockInfo($options['target-block-id'], 'tpl');
                    $sql = 'SELECT UPDATE_TIME FROM   information_schema.tables WHERE  TABLE_SCHEMA = ? AND TABLE_NAME = ?';
                    if ($result = Sql::query($sql, [Blox::info('db','name'), Blox::getTbl($options['target-tpl'])])) {
                        if ($row = $result->fetch_assoc())
                            $targetBlockUpdateTimestamp = strtotime($row['UPDATE_TIME']);
                        $result->free();
                    }
                    if ($targetBlockUpdateTimestamp > $numOfItemsUpdateTimestamp || !$targetBlockUpdateTimestamp)
                        self::updateNumOfItems($srcBlockId);
                }
            }
        }
        return true;
    }

     
    /**
     * Get a html-code of a simple multilevel list.
     * @param array $nodes Array obtained using the method Nav::getNodes().
     * @return string Html-code 
     */ 
    public static function getList($nodes)
    {
        if ($nodes) {
            $htm = '<ul>';
            foreach ($nodes as $node) {
                $htm.= '
                <li'.($node['active'] ? ' class="active"' : '').'>
                    <a href="'.$node['href'].'">'.$node['name'].'</a>
                    '.self::getList($node['children']).'
                </li>';
            }
            $htm.= '</ul>';
        }
        return $htm;
        
    }
    
         
    /**
     * Get all the information about the tree initialized by self::init().
     * @param array 1 Sequential array of keys, where the initial element corresponds to the navigation block Id. See details about $keys from description of method Arr::getByKeys().
     * @return array
     * @example 
     *  self::get() - Get information about all navigation blocks.
     *  self::get([$blockInfo['src-block-id']]) - Get information about the current navigation block.
     *  self::get(99, 'children', 0]) - Get recIds of first level items of bloc 99.
     */ 
    public static function get($keys)
    {   
        return Arr::getByKeys(self::$info, $keys);
    }
    
    
    /** 
     * @param array $keys The same as in the method self::get()
     * @param mixed $value
     * @example
     *    self::add([99,'active-nav-id'], 22);
     */
    public static function add($keys, $value=null)
    {   
        if (isset($value)) # Create array by sequential array of keys
            self::$info = Arr::addByKeys(self::$info, $keys, $value);
        else
            self::$info = Arr::mergeByKey(self::$info, $keys); # What for?
    }


    /**
     * @param int $srcBlockId
     * @param int $start-nav-id   - ID of the category from which the output starts
     * @param int $start-level - the number of the level from which the output starts. First level number = 1. You can get rid of this parameter. The only place you may need it is to mark the level in css classes class= " level3"
     * @return array Multidimensional tree array
     * @example of return array
     * [
     *     [// First menu element of the first level
     *         'href' => 'power-tools/', // Base relative URL of menu item
     *         'name' => 'Power tools', // Name of menu item
     *         'active' => true, // Item is active
     *         'children' => [ // Children of menu item (submenu)
     *             [
     *                 'href' => 'drills/',
     *                 'name' => 'Drills',
     *                 'active' => true,
     *                 'children' => [...]
     *             ],
     *             ...
     *         ]
     *     ],
     *     ...
     * ]
     */
    public static function getNodes($srcBlockId, $options=[])
    {
        if ($options)
            Arr::formatOptions($options);
        $options += ['start-nav-id'=>0, 'start-level'=>1, 'no-edit-buttons' => false];
        $activeNavId = self::get([$srcBlockId,'active-nav-id']);
        #if (self::get([$srcBlockId,'options','mark-active-ancestors']) && in_array($activeNavId, self::get([$srcBlockId,'ancestors'])))
            #$markAsActive = true;
        $childrenInfo = self::get([$srcBlockId,'children']);
        $activeNavFound = false;
        $prevId = $nextId = 0;
        if ($childrenInfo[$options['start-nav-id']]) 
        { # There are children
            foreach ($childrenInfo[$options['start-nav-id']] as $navId)
            {
                #$isActive = $markAsActive = false;
                $isActive = false;
                if ($navId == $activeNavId) {
                    $isActive = true;
                    #$markAsActive = true;
                }
                $recordInfo = self::get([$srcBlockId,'records',$navId]);
                $name = Text::stripTags($recordInfo['name']);
                if ($recordInfo['edit'] && !$options['no-edit-buttons'])
                    $name = $recordInfo['edit'].$name;
                # Sublevel
                $showChildren = false;
                $children = self::getNodes(
                    $srcBlockId, 
                    [
                        'start-nav-id'=>$navId, 
                        'start-level'=>$options['start-level']+1, 
                        'no-edit-buttons'=>$options['no-edit-buttons']
                    ]
                );

                #Check whether the level "children" or enabled edit mode to display the new record button
                if ($childrenInfo[$navId] || ($recordInfo['edit'] && !$options['no-edit-buttons'])) {
                    if ($isActive || !self::get([$srcBlockId,'options','hide-non-active']))
                        $showChildren = true;
                }
                if (!$showChildren)
                    $children = [];
                $href = $recordInfo['href'];
                $nodes[] = [
                    'id'            =>$navId, 
                    'href'          =>$href, 
                    'name'          =>$name, 
                    'active'        =>$isActive, 
                    'num-of-items'    =>$recordInfo['num-of-items'], 
                    'children'      =>$children,
                    'edit-href'      =>$recordInfo['edit-href'],
                    'delete-href'    =>$recordInfo['delete-href'],
                    'edit'          =>$recordInfo['edit'],
                    'delete'        =>$recordInfo['delete'],
                ];
                //'active'=>$markAsActive,
                
        
        
            
                ########### adjacents ###########
                # This can be transferred to self:: init () and caught there by parent, but here it is easier as the children of one parent move
                if (!$childrenInfo[$navId] && in_array($activeNavId, $childrenInfo[$options['start-nav-id']])) { # There is no sublevel && this level is the current reference
                    if ($activeNavId == $navId) { # Current article found
                        $activeNavFound = true;
                        if ($prevId)
                            self::add([$srcBlockId,'adjacents','prev'], $prevId);;
                    } else {
                        if ($activeNavFound) { # The current article has already been found
                            if (!$nextId) {
                                $nextId = $navId;
                                self::add([$srcBlockId,'adjacents','next'], $nextId);
                            }
                        } else # The current article has not yet been found
                            $prevId = $navId;
                    }
                }
            }
        } 

        # Output the button to create a new record for the level
        # TODO remake from a button ...['new-rec']['edit']
        if (!$options['no-edit-buttons'] && $newRecHref = self::get([$srcBlockId,'new-rec'])['edit-href'])
            $nodes[] = ['href'=>'', 'name'=>'<!--noindex--><span class="blox-edit-button blox-maintain-scroll" style="margin-right:3px" href="'.$newRecHref.'&defaults[2]='.($options['start-nav-id'] ?: '0').'" rel="nofollow"><img src="'.Blox::info('cms','url').'/assets/edit-button-new-rec.png" alt="+"/></span><!--/noindex-->&nbsp;'];

        return $nodes;
    }
    

    /** # Reload method of NumOfItems
     * numOfItems Of Lowest Nav
    private static function getNumOfItemsOfLowestNav($srcBlockId, $navId) 
    {
        # calculate targetBlockInfo
        if (!self::get([$srcBlockId,'target-tpl'])) {
            $targetBlockInfo = Blox::getBlockInfo(self::get([$srcBlockId,'options','target-block-id']));
            $numOfItemsInfo['target-tpl'] = $targetBlockInfo['tpl'];
            $numOfItemsInfo['target-src-block-id'] = $targetBlockInfo['src-block-id'];
            #itemsPick
            if ($itemsPick = self::get([$srcBlockId,'options','items-pick'])) {
                if ($pick = Url::queryToArray($itemsPick)['pick']) {
                    $signs = ['lt'=>'<', 'le'=>'<=', 'eq'=>'=', 'ge'=>'>=', 'gt'=>'>', 'ne'=>'!='];
                    foreach ($pick as $field => $aa) {
                        foreach ($aa as $k=>$val) {
                            if ($k && $signs[$k]) {
                                $xsql.= ' AND dat'.(int)$field.' '.$signs[$k].' ?';
                                $xvalues[] = $val;
                            }
                        }
                    }
                    if ($xsql) {
                        $numOfItemsInfo['xsql'] = $xsql;
                        $numOfItemsInfo['xvalues'] = $xvalues;
                    }
                }
            }
        }
        
        if (!$numOfItemsInfo)
            $numOfItemsInfo = self::get([$srcBlockId,'numOfItemsInfo']);
        #
        $sql = 'SELECT COUNT(*) FROM '.Blox::getTbl($numOfItemsInfo['target-tpl']).' WHERE `block-id`=? AND `dat'.self::get([$srcBlockId,'options','target-field']).'`=?';
        $sqlValues = [$numOfItemsInfo['target-src-block-id'], $navId];
        if ($numOfItemsInfo['xsql']) {
            $sql.= $numOfItemsInfo['xsql'];
            foreach ($numOfItemsInfo['xvalues'] as $v)
                $sqlValues[] = $v;
        }
        $result = Sql::query($sql, $sqlValues);
        $numOfItems = $result->fetch_row()[0];
        $result->free();
        #
        Dat::update(
            self::get([$srcBlockId, 'block-info']),
            [self::get([$srcBlockId,'fields','num-of-items'])=>$numOfItems], 
            ['rec'=>$navId]
        );
        if (!isEmpty($numOfItems))
            return $numOfItems;
    }
     */    
    

    
    /**
     * This method must be called each time you change the number of records in the target block (not navigation block).
     * This method may work without invoking Nav::init
     * Use one of params.
     *
     * @param int $srcBlockId of navigation block
     * @param string $tpl Use template name if there is no $srcBlockId
     *
     * @todo Addition, subtraction, emptying of one lower category. Nav::updateNumOfItems($srcBlockId, $tpl, $navId, $increment); $increment=-1;//subtraction;  $increment=0;//emptying; 
     */
    private static function updateNumOfItems($srcBlockId)
    {
        if (!$srcBlockId) {
            Blox::prompt('Not enough data in Nav::updateNumOfItems()', true);
            return;
        }

        if ($nav = self::get([$srcBlockId]))
        {
            if ($nav['fields']['num-of-items']) {
                    
                # Increase max_execution_time
                ini_set(
                    'max_execution_time', 
                    ini_get('max_execution_time')*10
                );
                //qq(ini_get('max_execution_time'));//test

                # Reset all #numOfItems
                Sql::query(
                    'UPDATE `'.Blox::getTbl($nav['block-info']['tpl']).'` SET `dat'.$nav['fields']['num-of-items'].'`=0 WHERE `block-id`=?',
                    [$srcBlockId]
                );
                #
                $targetBlockInfo = Blox::getBlockInfo($nav['options']['target-block-id']);
                $targetCol = '`dat'.$nav['options']['target-field'].'`';
                #
                $sql = 'SELECT '.$targetCol.', COUNT(*) FROM '.Blox::getTbl($targetBlockInfo['tpl']).' WHERE `block-id`=?';
                $sqlValues = [$targetBlockInfo['src-block-id']];
                #itemsPick
                if ($itemsPick = $nav['options']['items-pick']) {
                    if ($pick = Url::queryToArray($itemsPick)['pick']) {
                        $signs = ['lt'=>'<', 'le'=>'<=', 'eq'=>'=', 'ge'=>'>=', 'gt'=>'>', 'ne'=>'!='];
                        foreach ($pick as $field => $aa) {
                            foreach ($aa as $k=>$val) {
                                if ($k && $signs[$k]) {
                                    $sql.= ' AND dat'.(int)$field.' '.$signs[$k].' ?';
                                    $sqlValues[] = $val;
                                }
                            }
                        }
                    }
                } else { # hideRecord
                    if ($targetHidingField = Tdd::get($targetBlockInfo)['params']['hiding-field'])
                        $sql .= ' AND `dat'.$targetHidingField.'`<>1';
                }
                $sql.= ' GROUP BY '.$targetCol;

                if ($result = Sql::query($sql, $sqlValues)) {
                    while ($row = $result->fetch_assoc()) {
                        Dat::update(
                            $nav['block-info'],
                            [$nav['fields']['num-of-items']=>$row['COUNT(*)']], 
                            ['rec'=>$row['dat2']]
                        );
                        $loopIds = [];
                        self::updateParentNumOfItems($srcBlockId, $row['dat2'], $row['COUNT(*)'], $loopIds);
                    }
                    $result->free();
                }
                
                
                Store::set('bloxNav'.$srcBlockId,['num-of-items-update-timestamp'=>time()]);
                /** # Causes infinite loop sometimes
                if (!Blox::ajaxRequested())
                    ;Url::redirect(Blox::getPageHref(),'exit'); 
                */
            } else {
                Blox::prompt('Not specified numOfItems field in Nav::init()', true);
            }
                
        } else {
            Blox::prompt('Not envoked Nav::init()', true);
        }
    }    
    

    /*
     * @param int $loopIds For infinite loop protection
     * @return void
     */
    private static function updateParentNumOfItems($srcBlockId, $navId, $numOfItems, &$loopIds=[])
    {
        $loopIds[$navId] = true;
        $nav = self::get([$srcBlockId]);
        if ($parentId = Dat::get($nav['block-info'], ['rec'=>$navId])[$nav['fields']['parent-id']]) {
            if ($loopIds[$parentId]) {
                Blox::prompt('There is infinite loop in Nav::updateParentNumOfItems()', true);
                return false;
            } else
                $loopIds[$parentId] = true;
            $numOfItemsCol = 'dat'.$nav['fields']['num-of-items'];
            $sql = 'UPDATE `'.Blox::getTbl($nav['block-info']['tpl']).'` SET '.$numOfItemsCol.'='.$numOfItemsCol.'+? WHERE `block-id`=? AND `rec-id`=?';
            Sql::query($sql, [$numOfItems, $srcBlockId, $parentId]);
            self::updateParentNumOfItems($srcBlockId, $parentId, $numOfItems, $loopIds);
        }
    }




    /** 
     * Simplest method to reset NumOfItems
     * This method must be called each time you change the number of records in the target block (not navigation block).
     * @param array $navBlockInfo Just one element of the two ['src-block-id'=>..., 'tpl'=>...]
     */
    public static function resetNumOfItems($navBlockInfo)
    {
        if ($navBlockInfo['src-block-id'])
            $srcBlockIds[] = $navBlockInfo['src-block-id'];
        elseif ($navBlockInfo['tpl'])
            $srcBlockIds = Blox::getInstancesOfTpl($navBlockInfo['tpl']);
        
        if (!$srcBlockIds) {
            Blox::prompt('Not enough data in Nav::resetNumOfItems()', true);
            return;
        }
            
        foreach ($srcBlockIds as $srcBlockId)
            if ($srcBlockId)
                Store::delete('bloxNav'.$srcBlockId);
                //Store::set('bloxNav'.$srcBlockId,['num-of-items-update-timestamp'=>0]);
    }
          
        
}