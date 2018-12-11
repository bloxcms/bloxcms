<?php
   
/**
 * Class for storage of various data of any type: array, boolean, float, integer, object, string. Data is serialized and stored in a DB column of type blob.
 * 
 * @todo Keep retrieved vars in class member
 */
class Store
{
    private static $table;


    /**
     * Store a variable
     * 
     * @example Store::set('priceIsOutdated', true);
     */
    public static function set($name, $value)
    {
        self::init();
        $sql = "DELETE FROM ".self::$table." WHERE name=?";
        Sql::query($sql, [$name]);
        $sql = "INSERT ".self::$table." (name, value) VALUES (?,?)";
        Sql::query($sql, [$name, serialize($value)]);
        return true;
    }
    
    
    /**
     * Get a variable from storage
     * 
     * @example
     *    if (Store::get('priceIsOutdated'))
     *        echo 'Price is outdated';
     */
    public static function get($name)
    {
        self::init();
        $sql = "SELECT value FROM ".self::$table." WHERE name=?";
        if ($result = Sql::query($sql, [$name])) {
            if ($row = $result->fetch_assoc()) {
                $result->free();
  	            return unserialize($row['value']);
            }
        }
    }


    /**
     * Delete a variable from storage
     * 
     * @example Store::delete('priceIsOutdated');
     */
    public static function delete($name)
    {
        self::init();
        $sql = "DELETE FROM ".self::$table." WHERE name=?";
        Sql::query($sql, [$name]);
        return true;
    }


    private static function init()
    {
        # Check table
        self::$table = Blox::info('db', 'prefix').'store';
        if (!Sql::tableExists(self::$table)) {
            $sql = 'CREATE TABLE IF NOT EXISTS '.self::$table.' (name varchar(332) NOT NULL PRIMARY KEY, value blob) ENGINE=MyISAM DEFAULT CHARSET=utf8';
            Sql::query($sql);
        }
    }
}


