<?php

/**
 * Class for user's subscription block's "news"
 */
 
class Subscription
{
    private static $error;
    
    public static function subscribe($srcBlockId, $userId, $action)
    {
        $srcBlockId = (int)$srcBlockId;
        if (self::check($srcBlockId)) {
            # Subscription
            if ($action) {
                $sTable = Blox::info('db','prefix').'subscriptions';
                # If subscription does not exist, create it
                if (!Sql::select('SELECT `block-id` FROM '.$sTable.' WHERE `block-id`=?',[$srcBlockId])) { 
                    $sql = 'REPLACE '.$sTable.' (`block-id`) VALUES (?)';
                    Sql::query($sql, [$srcBlockId]);
                }
                # Subscribe
                if (Proposition::set('user-is-subscriber', $userId, $srcBlockId, true)) {
                    $_SESSION['Blox']['subscription']['error'][$srcBlockId] = 0;
                    return true;
                }
            } else { # Unsubscribe
                if (Proposition::set('user-is-subscriber', $userId, $srcBlockId, false)) {
                    $_SESSION['Blox']['subscription']['error'][$srcBlockId] = 0;
                    return true;
                }
            }
            $_SESSION['Blox']['subscription']['error'][$srcBlockId] = self::$error;
        }
    }
    
    
    /**
     * Called when outputed in another script
     * Only one block can be subscribed
     */
    public static function getError($srcBlockId)
    {
        if (isset($_SESSION['Blox']['subscription']['error'][$srcBlockId])) {
            $aa = $_SESSION['Blox']['subscription']['error'][$srcBlockId];
            $_SESSION['Blox']['subscription']['error'] = null;
            return $aa;
        }
    }


    private static function check($srcBlockId)
    {
        $srcBlockId = Sql::sanitizeInteger($srcBlockId);
        if (!$srcBlockId) {
            self::$error = 1; # No `block-id`
            return false;
        }
        # Check
        $sTable = Blox::info('db','prefix').'subscriptions';
        if (!Sql::tableExists($sTable)) {
            $sql = 'CREATE TABLE IF NOT EXISTS '.$sTable.' (
                `block-id` MEDIUMINT UNSIGNED NOT NULL PRIMARY KEY,
                `last-mailed-rec` INT UNSIGNED NOT NULL DEFAULT 0,
                `activated` tinyint(1) UNSIGNED NOT NULL DEFAULT 1
            ) ENGINE=MyISAM DEFAULT CHARSET=utf8';

            if (isEmpty(Sql::query($sql))) {
                self::$error = 2; # Cannot create the table "subscriptions"
                return false;
            } else
                return true;
        } else 
            return true;
    }

         
}