<?php
        
class Group
{
    /** 
     * @example 
     *    Is there a group with $includes = ['name'=>..., 'id'=>...,];
     *    Excluding groups with $excludes = ['name'=>..., 'id'=>...,];
     *    if (Group::exist($includes, $excludes)
     */
    public static function exist($includes=[], $excludes=[])
    {
        $sql = '';
        $sqlValues = [];
        # includes
        if ($name = Sql::sanitizeName($includes['name'])) {
            $sql .= "AND name=?";            
            $sqlValues[] = $name;
        }
        if ($id = Sql::sanitizeInteger($includes['id'])) {
            $sql .= "AND id=?";            
            $sqlValues[] = $id;
        }
        # excludes
        if ($name = Sql::sanitizeName($excludes['name'])) {
            $sql .= "AND name<>?";
            $sqlValues[] = $name;
        }
        if ($id = Sql::sanitizeInteger($excludes['id'])) {
            $sql .= "AND id<>?";    
            $sqlValues[] = $id;
        }
        

        if ($sql = substr($sql, 3)) {
            $sql = 'SELECT name FROM '.Blox::info('db','prefix').'groups WHERE '.$sql.' LIMIT 1';
            if ($result = Sql::query($sql, $sqlValues)) {
                if ($result->fetch_row()) {
                    $result->free();
                    return TRUE;
                }
                $result->free();
            }
        }
    }


    /**
     * Create a group and return its Id
     * 
     * @param array $info
     * @param string $error returns one of: 'infoIsNotCorrect','nameIsNotAvailable','nameIsEmpty'
     * @return int group ID
     *      
     * @example $info = ['name' => '','activated'=> true,'description'=> '','regdate'=> date('Y-m-d'),];
     */
    public static function create($info=[], &$error='')
    {
        
        $info += [ # defaults
            #'name' => '',
            'activated'=> true,
            #'description'=> '',
            'regdate'=> date('Y-m-d'),
        ];
        unset($info['id']);
        $error = '';
        if ($info['name']) {
            if (!self::exist(['name'=>$info['name']])) {                    
                $sql = "INSERT ".Blox::info('db','prefix')."groups () VALUES ()"; 
                $num = Sql::query($sql);
                if ($num > 0) {
                    $groupId = Sql::getDb()->insert_id;
                    if (self::updateInfo($groupId, $info))
                        return $groupId;
                    else
                        $error = 'infoIsNotCorrect';
                } else 
                	Blox::error('GroupId was not generated'); 
            } else
                $error = 'nameIsNotAvailable';
        } else
            $error = 'nameIsEmpty';
    }
        
    //public static function getNewGroupId($info)



    /**
     * Update info of group.
     * 
     * @param int $id Id of the group
     * @param array $info
     * @return bool
     *      
     * @example $info = ['name' => '','activated'=> true,'description'=> '','regdate'=> date('Y-m-d')];
     */
    public static function updateInfo($id, $info)
    {
        if ($id = Sql::sanitizeInteger($id)) {
            $sql = '';
            unset($info['id']);
            if ($info) {
                foreach ($info as $k=>$v) {  
                    if ($k == 'regdate') {
                        if ($v) {
                            $sqlValues[] = $v;
                            Sql::query("SET @@session.sql_mode = 'ALLOW_INVALID_DATES'");
                        } else
                            $sqlValues[] = date('Y-m-d');
                    } elseif ($k == 'activated') {
                        $sqlValues[] = ($v) ? 1 : 0 ;
                    } else
                        $sqlValues[] = $v;
                    $sql .= ", $k=?";
                }
            }
            if ($sql = substr($sql, 2)) {
                $sql = 'UPDATE '.Blox::info('db','prefix').'groups SET '.$sql.' WHERE id=?'; 
                $sqlValues[] = $id;
                if (!isEmpty(Sql::query($sql, $sqlValues)))
                    return true;
            }
        }     
    }
    
    
  
}