<?php
 
/**
 * Permission for current user (not group)
 *
 * @todo
 *     type        keys                directives              status
 *     ----        -----               ----------              ----
 *     record:     src-block-id, rec-id  {edit, create}          done
 *     block:      `block-id`             {view, edit, assign}
 *     field:      src-block-id,field    {hide, edit}  
 *     page:       pageId              {hide}
 *     site:       -                   {hide} 
 *     bar         -            
 *     editbar     -
 *     users       user-id
 *     group       group-id
 */
class Permission
{
    /**
	 * Storage of functional permissions
     * @example $fpermissions['record'][22][''][3]  = function();
     */
    private static $fpermissions=[];
    
    /**
	 * Storage of static permissions
	 * @example $spermissions['record'][33]['']   = ['edit'=>true, ...]
     */    
    private static $spermissions=[];

    /**
	 * Storage of keys dimensions
	 * @example $keysSizes['record'] = 2;
     */
    private static $keysSizes=[];
    
    /**
	 * Stores last unique key for fpermissions[]
     */
    private static $lastKey = 0;
    
    private static $blockPermitsAreAdded = [];
    



    /**
     * Add permissions. Permission is usually added in tdd file. Each type of permission has its own set of directives.
     */
    public static function add($type, $keys, $permits)
    {
        # format $permits
        if (is_array($permits)) { # array
            foreach ($permits as $k=>$v) {
                if (is_int($k)) # Format from ['edit'] to ['edit'=>...]
                    $spermits2[$v] = true;
                elseif ($v === true || $v === false) # assoc
                    $spermits2[$k] = $v; # For case: ['edit'=false]
                else
                    $errors[] = Blox::getTerms('incorrect-permit');
            }
        } elseif (is_callable($permits)) { # function
            $fpermits2 = $permits;
        } else { # scalar
            $spermits2[$permits] = true;
        }

        # keys
        if (isset($keys) && !is_array($keys))
            $keys = [$keys];
        $keys2 = array_reverse($keys); # Begin loop from low-order keys
        
        # Do not break this loop because $i is being used
        foreach ($keys2 as $i=>$keyVal) 
        {
            if (!$i) { # This is the least significant key. Save here value
                if ($fpermits2) {
                    $permissions2[$keyVal][self::getNewKey()] = $fpermits2; 
                } elseif ($spermits2) {
                    $permissions2[$keyVal] = $spermits2;
                }
            } else { # elder keys
                $aa = $permissions2;
                unset($permissions2);
                $permissions2[$keyVal] = $aa;
            }
            # Error: Not empty key goes after empty key. Consider reverse order of the keys
            if ($keyVal)
                $notEmptyKeyAppeared = true;
            elseif ($notEmptyKeyAppeared)
                $errors[] = Blox::getTerms('epmty-after-empty-key');
        }
        
        $keysSize = $i + 1;
        if (self::$keysSizes[$type]) {
            if (self::$keysSizes[$type] != $keysSize)
                $errors[] = Blox::getTerms('invalid-num-of-keys');
        } else {
            self::$keysSizes[$type] = $keysSize;
        }
        
        if ($fpermits2) 
            self::$fpermissions[$type] = Arr::mergeByKey(self::$fpermissions[$type], $permissions2);
        elseif ($spermits2)
            self::$spermissions[$type] = Arr::mergeByKey(self::$spermissions[$type], $permissions2);
        
        ##### Error report #####
        if ($errors) {
            self::addToErrorsPrompt('add', $errors, $type, $keys, $permits);
            return false;
        }
    }

    


    /**
     * Get permissions
     *
     * @param string $type 
     * @param mixed $keys Requested keys. It may me not full set of keys, unlike $keys in Permission::add() method
     * @param mixed $data
     * @return array of permissions
     * 
     * @example Permission::get('record', [2, 55], $data)['edit'];
     */
    public static function get($type, $keys=null, $data=[])
    {
        if (isset($keys) && !is_array($keys))
            $keys = [$keys];
        $permissions = [];

        # Functional permits. Evaluate functional permits before static permits!
        if ($fpermissions = self::getReducedArr(self::$fpermissions[$type], $keys, $errors)) {
            $stepsToBottom = self::$keysSizes[$type] - count($keys);
            # $fpermissions - with reduced dimensionality and with a higher cumulative key
            foreach ($fpermissions as $arr) { # higher key are cumulative - loop through them
                # $permissions is accumulated in the loop
                self::refillFunctionalPermission($permissions, $arr, $stepsToBottom, $keys, $data);
            }
        }

        # Static permits 
        # $spermissions: more keys - less dimension
        if ($spermissions = self::getReducedArr(self::$spermissions[$type], $keys, $errors)) {
            foreach ($spermissions as $arr) { # higher key are cumulative - loop through them
                $permissions = Arr::mergeByKey($permissions, $arr); # $permissions - given level permissions
            }
        }
        
        # Error report 
        if ($errors) {
            self::addToErrorsPrompt('get', $errors, $type, $keys, $data);
            return false;
        }   
        return $permissions;
    }


    /**
     * Get strict permissions. If perm is false or undefined - no perm
     *
     * @param string $type 
     * @param mixed $keys Requested keys. It may me not full set of keys, unlike $keys in Permission::add() method
     * @param mixed $data
     * @return array of permissions
     * 
     * @example Permission::get('record', [2, 55], $data)['edit'];
     */
    public static function ask($type, $keys=[], $data=[])
    {
        $perms = self::get($type, $keys, $data);
        if (!$perms)
            return;
            
        # permTypeArr
        # Build array of permissions with another structure - by type of perm: $arr['edit'][$truth] = $perms where $truth: true|false|undefined.
        self::ask_build($perms, [], $permTypeArr);
        
        # mergedArr
        foreach ($permTypeArr as $permtype => $ar) {
            foreach ($ar as $truth => $perms2) {
                $arr2 = [];
                self::ask_merge($perms2, [], $arr2);
                $mergedArr[$permtype][$truth] = $arr2;
            }
        }
        # cleanedArr
        $resultArr = [];
        foreach ($mergedArr as $permtype => $ar) {
            $trueArr = $ar[true];
            if ($ar[false])
                self::ask_remove($ar[false], [], $trueArr);
            if ($ar['undefined'])
                self::ask_remove($ar['undefined'], [], $trueArr);
            if ($trueArr) {
                if ($resultArr)
                    $resultArr = Arr::mergeByKey($resultArr, $trueArr);
                else
                    $resultArr = $trueArr;
            }
        }
        return $resultArr;
    }
    
    /**
     * Function for Permission::ask()
     * Build array of permissions with another structure. 
     * First key - type of perm, second key - false or true. Other minor keys are not changed
     * @return void
     */
    private static function ask_build($perms, $nkeys, &$arr=[])
    {
        foreach ($perms as $k => $v) {
            if (is_int($k) || $k==='') { # This is key of the object
                self::ask_build($v, array_merge($nkeys, [$k]), $arr);
            } elseif (is_string($k)) # This is permission type 'edit'
                $arr = Arr::addByKeys($arr, array_merge([$k,$v], $nkeys), $perms); # $v: false, true, undefined
        }
    }
    
    /**
     * Function for Permission::ask()
     * Merge array - remove elements with integer keys if there is element with empty key
     */
    private static function ask_merge($perms, $nkeys, &$arr=[])
    {
        if ($perms[''])
            self::ask_merge($perms[''], array_merge($nkeys, ['']), $arr);
        else {
            foreach ($perms as $k => $v) {
                if (is_int($k)) {
                    self::ask_merge($v, array_merge($nkeys, [$k]), $arr);
                } elseif (is_string($k)) {
                    if ($nkeys)
                        $arr = Arr::addByKeys($arr, $nkeys, $perms); // $v===false
                    else
                        $arr = Arr::mergeByKey($arr, $perms);
                }
            }
        }
    }
    
    /**
     * Remove from the array $trueArr permissions by the array $falseArr
     */
    private static function ask_remove($falseArr, $nkeys, &$trueArr=[])
    {
        foreach ($falseArr as $k => $v) {
            if (is_int($k) || $k==='') { # This is key of the object
                $nkeys2 = array_merge($nkeys, [$k]);
                if (Arr::getByKeys($trueArr, $nkeys2)) # If key in "true" array is the same as in the "false" array
                    self::ask_remove($v, $nkeys2, $trueArr); # Go deeper
                else {
                    # if there's a partial restriction, restrict all the children
                    $aa = $nkeys;
                    array_pop($aa); # parent element
                    if ($aa)
                        $trueArr = Arr::removeByKeys($trueArr, $aa);
                    else
                        $trueArr = [];
                }
            }
        }
    }



    
    

    /**
     * Reduce the dimension of the array and store all variants in additional elder keys
     */
    private static function getReducedArr($arr, $keys, &$errors=[])
    {
        if ($arr) { // && $keys
            /**
             * If there is a specific key, it must be added element with empty key to the result (i.e. all permits)
             * For this reason, array $arr1 involved in cycles, has the higher cumulative key.
             */
            $arr1[] = $arr;
            foreach ($keys as $k) {
                # Lower the dimension of the array of permissions            
                if ($ff = $arr1) {
                    unset($arr1); # remove array of higher dimension 
                    foreach ($ff as $arr2) { # many permissions 
                        if ($arr2['']) # Additionally, check the blank key
                            $arr1[] = $arr2[''];
                        if ($arr2[$k])
                            $arr1[] = $arr2[$k]; # normal
                    }
                }   
            }
            return $arr1;
        }
    }
    
    
    /**
     * @param $xKeys Stackable set of keys
     */
    private static function refillFunctionalPermission(&$permissions, $arr, $stepsToBottom, $xKeys, $data)
    {
        foreach ($arr as $k2 => $arr2) 
        {
            if (!is_callable($arr2)) {
                if (is_array($arr2)) {
                    $xKeys2 = $xKeys;
                    $xKeys2[] = $k2;
                    self::refillFunctionalPermission($permissions, $arr2, $stepsToBottom, $xKeys2, $data);
                } else
                    Blox::prompt(Blox::getTerms('not-array'), true);
            } else {
                # Now $xkey has a full set of keys
                $permissions2 = $arr2($xKeys, $data); # bottom level permissions
                if ($xKeys && $stepsToBottom) { # Loop starting with lower keys
                    $xKeysR = array_reverse($xKeys);
                    $counter = 0;
                    foreach ($xKeysR as $keyVal) {
                        $aa = $permissions2;
                        unset($permissions2);
                        $permissions2[$keyVal] = $aa;
                        $counter++;
                        if ($counter == $stepsToBottom)
                            break;
                    }
                }
                $permissions = Arr::mergeByKey($permissions, $permissions2); # $permissions - given level permissions
            }
        }
    }

    
    


    private static function addToErrorsPrompt($method, $errors, $type, $keys, $arr) # $arr is $permits or $data
    {   
        $keysList = '';
        if ($keys) {
            foreach ($keys as $v)
                $keysList .= ','.($v ?: '\'\'');
            $keysList = substr($keysList, 1);
        } else
            $keysList = '\'\'';

        $arrList = '';
        $arrArg = '';
        if ($arr) {
            if ($method == 'add' && is_callable($arr)) {
                $arrArg .= ', function(){...}';
            } else {
                foreach ($arr as $k=>$v) {
                    if (is_array($v)) # $method == 'get' - $data
                        $vv = '[...]';
                    else
                        $vv = (string)$v;
                    /*
                    elseif ($v)
                        $vv = 'true';
                    else
                        $vv = 'false';
                    */
                    $arrList .= ','.$k.'=>'.$vv;
                }
                $arrList = substr($arrList,1);
                $arrArg = ', ['.$arrList.']';
            }
        } 
        $errors = array_unique($errors);
        foreach ($errors as $error)
            $errorsReport .= ', '.$error;
        $errorsReport = substr($errorsReport, 2);
        Blox::prompt(Blox::getTerms('error-of-method').' Permission::'.$method.'('.$type.', ['.$keysList.']'.$arrArg.') — '.$errorsReport, true);
    }




    # Generates new unique integer key for fpermissions[]
    private static function getNewKey()
    {   
        return self::$lastKey++;
    }




    
    /**
     * Add All Permits Related To Block
     */
    public static function addBlockPermits($srcBlockId, $tdd=[])
    {
        if (self::$blockPermitsAreAdded[$srcBlockId])
            return true;
        
        if (Blox::info('user','id')) 
        {
            if ($srcBlockId && Blox::info('user', 'user-is-activated')){
                # user-is-editor-of-block
                if ($aa = Proposition::get('user-is-editor-of-block', Blox::info('user','id'), $srcBlockId)) {
                    Permission::add('record', [$srcBlockId, ''], ['edit'=>true, 'create'=>true]);
                } else {
                    if ($groupsOfUser = Blox::info('user','groups')) {# groupsOfUser
                        foreach ($groupsOfUser as $groupInfo)
                            if ($groupInfo['activated'] && Proposition::get('group-is-editor-of-block', $groupInfo['id'], $srcBlockId))
                                Permission::add('record', [$srcBlockId, ''], ['edit'=>true, 'create'=>true]);
                    }
                }
            }
            # Own records
        	if ($tdd['params']['user-id-field']) {
                $userIdField = $tdd['params']['user-id-field'];
                Permission::add('record', [$srcBlockId, ''], ['create'=>true]);
                Permission::add('record',  [$srcBlockId, ''], 
                    function($keys, $data) { # Allow to edit own records
                        if ($data['dat'][$data['tdd']['params']['user-id-field']] == Blox::info('user','id'))
                            return ['edit'=>true];
                    }
                );   
            }
        }
        self::$blockPermitsAreAdded[$srcBlockId] = true;
        return true;
    }
    
    
}