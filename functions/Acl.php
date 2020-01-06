<?php


/**
 *   -   So far groups, users, sites bundle via Proposition::set().
 *           Proposition::get('group-has-user'...)
 *   -   The "name" field in the table "groups" is used only as analogous to "id" for developers.
 *
 * @param array $filters = [
 *       # Users
 *       'user-login'=>
 *       'userPassword'=>
 *       'userEmail'=>
 *       'userPersonalname'=>
 *       'userFamilyname'=>
 *       'userActivated'=> in v.14
 *       # Roles
 *       'user-is-admin'=>
 *       'user-is-editor'=>
 *       'user-dont-see-edit-buttons'
 *       'user-sees-block-boundaries'
 *       'user-as-visitor'
 *       # Groups
 *       'group-id'=>
 *       'group-name'=>   
 *       'group-activated'=>
 *   ]
 * @todo Make a method which automatically enters the user to the group
 * @todo Make common $filters for all methods Acl::get*()
 */
        
class Acl
{
    /*
    private static function init()
    {
   	    if (self::$terms)
    	    return;
        else {
            self::$terms = Blox::getClassTerms();
        }
    }
    */
        
    /**
     *   Acl::getUsers($filters)
     *   $filters = [        DONE:
     *       'user-is-admin', 'user-is-editor', 'user-is-activated'
     *       'user-id', 'user-login', 'userPassword', 'userEmail', 'userPersonalname', 'userFamilyname', 
     *   ]
     *   @examples:
     *       $users    = Acl::getUsers(['user-is-editor'=>true]);     Find an editor
     *       $userInfo = Acl::getUsers(['user-id'=>1)[0];            Get information about the user
     *       $login    = Acl::getUsers(['user-id'=>1)[0]['login'];   Get user login
     */
    public static function getUsers($filters)
    {
        if ($filters) {
            $sql ='';
            $sqlParams = [];
            # Propositions
            foreach (['user-is-admin', 'user-is-editor'] as $role) { //, 'user-dont-see-edit-buttons','user-sees-block-boundaries', 'user-as-visitor'
                if ($filters[$role]) { 
                    if ($props = Proposition::get($role, 'all')) { 
                        $sqlIns = '';
                        foreach ($props as $aa) {
                            $sqlIns  .= ',?';
                            $sqlParams[] = $aa['subject-id'];
                        }
                        $sql .= ' AND id IN('.substr($sqlIns, 1).')';  # remove initial
                    }
                }
            }
            # Users columns
            foreach (['user-id'=>'id', 'user-login'=>'login', 'userEmail'=>'email', 'userPersonalname'=>'personalname', 'userFamilyname'=>'familyname'] as $k => $v) { #, 'userPassword'=>'password' 'userActivated'=>'activated', //in v.13
                if ($filters[$k]) {
                    $sql .= ' AND '.$v.'=?';
                    $sqlParams[] = $filters[$k];
                }
            }
        }

        $sql2 = 'SELECT * FROM '.Blox::info('db','prefix').'users';
        if ($sql)
            $sql2 .= ' WHERE'.substr($sql, 4); # remove initial ' AND'
        $sql2 .= ' ORDER BY login';
        return Sql::select($sql2,$sqlParams);
    }
    



    /** Acl::getGroups()
     *  
     *   $filters = [
     *       'group-id'=>
     *       'group-name'=>   
     *       'group-activated'=>
     *       
     *       'user-id'=>
     *       'user-login'=>
     *   ]
     *
     *   @examples:
     *       $groups = Acl::getGroups(['user-id'=>Blox::info('user','id')])    Information about the groups of the current user               TODO: Equivalent:  $groups = Acl::getGroups(['user-id'=>''])
     *       $groupInfo = Acl::getGroups(['group-id'=>99])[0];                    Information about a single group by its ID
     *   TODO
     *   $filters = [
     *       'permissions'=>
     *           'page'=>
     *               'see'=>
     *   ]
     */
    public static function getGroups($filters)
    {
    
        if ($filters) 
        {
            $sql ='';
            $sqlParams = [];
            #\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\\
            # Get user-id
            if ($filters['user-id'])
                $userId = $filters['user-id'];
            elseif ($filters['user-login']) { # By user login 
                if ($result = Sql::query('SELECT id FROM '.Blox::info('db','prefix').'users WHERE login=?', [$filters['user-login']])) {
                    if ($row = $result->fetch_assoc()) {
                        $userId = $row['id'];
                        $result->free();
                    }
                }
            }
            # Get groups of User
            if ($userId) {                
                $groups = Proposition::get('group-has-user', 'all', $userId); 
                if ($groups) {
                    $sqlIns = '';
                    foreach ($groups as $g) {
                        $sqlIns .= ',?';
                        $sqlParams[] = $g['object-id'];
                    }
                    $sql .= ' AND id IN('.substr($sqlIns, 1).')';  # remove initial
                }
                else {
                    Blox::error(Blox::getTerms('no-group').' '.$userId);
                    return [];
                }
            } 
            /*
            else {
                Blox::error('There's no user '.$filters['user-login']);
                return [];
            }
            */
            
            if ($filters['group-id']) {
                $sql .= ' AND id=?';
                $sqlParams[] = $filters['group-id'];
            }
            if ($filters['group-name']) {
                $sql .= ' AND name=?';
                $sqlParams[] = $filters['group-name'];
            }
            if ($filters['group-activated']) {
                $sql .= ' AND activated=?';
                $sqlParams[] = $filters['group-activated'];
            }
        }

        $sql2 = 'SELECT * FROM '.Blox::info('db','prefix').'groups';
        if ($sql)
            $sql2 .= ' WHERE'.substr($sql, 4);
        $sql2 .= ' ORDER BY name';
        return Sql::select($sql2,$sqlParams);
    }



    /**
     * When you send letters, it is desirable to specify also a "from" box
     * @param string $to email address. Two formats: john@doe.com, John Doe <john@doe.com>
     * @todo from format: John Doe <john@doe.com> in params or from users
     */
    public static function getFromEmail($to)
    {
        if (preg_match('~<(.*?)>~us', $to, $matches)) # John Doe <john@doe.com>
            $to = $matches[1];
        if (!$to)
            return false;
        # global from
        if ($from = Blox::info('site','emails','from')) {
            if (preg_match('~<(.*?)>~us', $from, $matches)) # John Doe <john@doe.com>
                $from = $matches[1];
            if ($to == $from)
                $from = '';
            else
                return $from;
        } 
        # from admin or editor
        if (!$from) {
            foreach (['user-is-admin', 'user-is-editor'] as $usertype) {
                $users = Acl::getUsers([$usertype=>true]); 
                foreach ($users as $userInfo) {
                    if ($to <> $userInfo['email']) {
                        $from = $userInfo['email'];
                        return $from;
                        //break 2;
                    }
                }
            }
        }
    }
    
    
    /*
    # $user - ID or login
    # If there's no param, then return all groups of the user
    public static function getGroups($user)
    {
        $sql = 'SELECT * FROM '.Blox::info('db','prefix').'groups';
        if (!is_null($user))
        { 
            if (Str::isInteger($user))
                $userId = $user;
            else { # by login
                $result = Sql::query('SELECT id FROM '.Blox::info('db','prefix')."users WHERE login='$user'");
                if ($row = $result->fetch_assoc()) {
                    $userId = $row['id'];
                    $result->free();
                } else
                    Blox::error("No such user");
            }
            $groupId = Proposition::get('group-has-user', $userId, 'all')[0]['object-id'];
            $sql .= ' WHERE id='.$groupId;
        }
        $sql .= ' ORDER BY name';
        return Sql::select($sql);
    }
    */



    /**
    $group - ID or name
    If there's no param, then return all user of the group
    public static function getUsers($group)
    {
    }
    */
 
    /** TODO
     *   Acl::getRoles($filters)
         $filters = [
             'user-is-admin'=>
         ]
    public static function getRoles($filters)
    {
    }
    */  
}