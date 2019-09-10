<?php
/**
 * Authorized mode means "if (Blox::info('user')) ..."
 */
class Auth
{
    /**
     * Authenticate by login and password and return user info
     * @param array $factors {
     *     @var string $login
     *     @var string $password
     *     @var bool save-password
     * }
     * @param array $errors {
     *     @var bool login-limit-exceeded
     *     @var bool wrong-login
     * }
     * @return array user info
     */
    public static function get($factors=[], &$errors=[])
    {   
        $z = Blox::info('user');
        if ($z['id'])
            return $z;
        #
        if (!$factors['login'])
            return false;
        #
        if ($_SERVER['HTTP_REFERER'] && false === mb_strpos($_SERVER['HTTP_REFERER'], Blox::info('site','url')))
            return false;
        #
        $ip = self::getIp();
        if (
            self::checkAttempts('login', $factors['login']) && 
            self::checkAttempts('ip', $ip, ['timeout'=>7200, 'limit'=>40])
        ) {
            $sql = "SELECT * FROM ".Blox::info('db','prefix')."users WHERE login=?";
            if ($result = Sql::query($sql, [$factors['login']])) {
                if ($userInfo = $result->fetch_assoc()) {
                    $result->free();
                    if (password_verify($factors['password'], $userInfo['password'])) {
                        session_regenerate_id();
                        Blox::setSessUserId($userInfo['id']);
                        # Save the password in the cookie
                        if ($factors['save-password']) {
                            if (setcookie('blox', Url::encode(serialize([$factors['login'], $factors['password']])), time() + 432000))
                                ;// +5 days
                        } elseif (setcookie('blox', '', time() + 10))
                            ;
                        self::deleteAttempts('login', $factors['login']);
                        self::deleteAttempts('ip', $ip);
                        return $userInfo;
                    } else
                        $errors['wrong-login'] = Blox::getTerms('wrong-login');
                } else
                    $errors['login-error-1'] = Blox::getTerms('wrong-login');
            } else
                $errors['login-error-2'] = Blox::getTerms('login-error-2');
        } else # Exceeded the limit of login attempts 
            $errors['login-limit-exceeded'] = Blox::getTerms('login-limit-exceeded');
        return false;
    }


    /**
     * Used for brute force protection
     * @param $type 'login'|'ip'
     * @param string $value
     * @param array $options {
     *   @var int $timeout Default is an hour
     *   @var int $limit Default is 20 attempts
     * }
     */
    private static function checkAttempts($type, $value, $options=[])
    {
        # DEPRECATED since v14.1.1. Remove this in v.15
        $t = Blox::info('db', 'prefix').'authattempts';
        if (!Sql::tableExists($t)) {
            Sql::query('CREATE TABLE IF NOT EXISTS '.$t.' (
                type VARCHAR(64),
                value VARCHAR(64),
                time INT(11) UNSIGNED NOT NULL DEFAULT 0,
                counter SMALLINT(11) UNSIGNED NOT NULL DEFAULT 0,
                UNIQUE (type, value), INDEX (time)
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8');
            Sql::query($sql);
        }
        #/
        $options += ['timeout'=>3600, 'limit'=>20];# Defaults
        if (false === self::actualizeAttempts($type, time() - $options['timeout']))
            ;
        else {
            $counter = self::getAttempts($type, $value);
            if (false !== $counter) {
                if ($counter < $options['limit']) {
                    self::incrementAttempts($type, $value);
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * @param $type 'login'|'ip'|''. Empty string is to delete all types
     * @param int $before timestamp
     */
    private static function actualizeAttempts($type, $before)
    {
        $where = ' time < ?';
        $sqlValues[] = $before;
        if ($type==='') {
            $where = ' AND type = ?';
            $sqlValues[] = $type;
        }
        $result = Sql::query('DELETE FROM '.Blox::info('db','prefix').'authattempts WHERE'.$where, $sqlValues);
        if (false === $result)
            Blox::error('Error when cleaning the table authattempts');
        return $result;
    }
    
    
    /**
     * @param $type 'login'|'ip'
     * @param string $value
     */
    private static function deleteAttempts($type, $value)
    {
        $result = Sql::query('DELETE FROM '.Blox::info('db','prefix').'authattempts WHERE type=? AND value=?', [$type, $value]);
        if (false === $result)
            Blox::error('Error deleting from the table authattempts');
        return $result;
    }
    
     
    /**
     * @param $type 'login'|'ip'
     * @param string $value
     */
    private static function incrementAttempts($type, $value)
    {
        $now = time();
        $sql = 'INSERT '.Blox::info('db','prefix').'authattempts VALUES (?,?,?,1) ON DUPLICATE KEY UPDATE time=?, counter=counter+1';
        $result = Sql::query($sql, [$type, $value, $now, $now]);
        if (false === $result)
            Blox::error('Error inserting/updating the table authattempts');
        return $result;
    }


    /**
     * @param $type 'login'|'ip'
     * @param string $value
     */
    private static function getAttempts($type, $value)
    {
        $result = Sql::query('SELECT counter FROM '.Blox::info('db','prefix').'authattempts WHERE type=? AND value=?', [$type, $value]);
        if ($result === false) {
            Blox::error('Error selecting from the table authattempts');
            return false;
        } else {
            if ($result->num_rows) {
                $row = $result->fetch_assoc();
                $result->free();
                return $row['counter'];
            } else
                return 0;
        }
    }
    

    /*
     * @todo Try this https://stackoverflow.com/questions/13646690/how-to-get-real-ip-from-visitor
     */
    private static function getIp()
    {
        $varnames = [
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR',
        ];
        $ip = '';
        foreach ($varnames as $v)
            if ($ip = getenv($v))
                break;
        return $ip;
    }


    
    
}