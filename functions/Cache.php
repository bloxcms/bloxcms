<?php
/**
 * Class for caching pages.
 * There code related to the caching: go.php #fastway
 * @todo If href is parametric while human url is set then do not caching
 */

class Cache
{
    /** 
     * Get cache file of the href. By default cache file is got by $getCacheFile() in go.php. This method is for rare but possible cases (when file name got not unique)
     * @param string $href
     * @return string   
     */
    public static function getFile($href)
    {
        # DEPRECATED since v14.0.16. REMOVE THIS
        if (!(Sql::tableExists('%cachedpages') && Sql::tableExists('%cachedpagesblocks') && file_exists(Blox::info('site','dir').'/cached')))
            return;
        
        $prefix = Blox::info('db','prefix');
        $sql = 'SELECT * FROM '.$prefix.'cachedpages WHERE href=?';
        if ($result = Sql::query($sql,[$href])) {
            if ($row = $result->fetch_assoc()) {
                $result->free();
                if (file_exists(Blox::info('site','dir').'/cached/'.$row['file-name'].'.html')) {
                    //self::$cacheFileExists = true;
                	return Blox::info('site','dir').'/cached/'.$row['file-name'].'.html';
                } elseif ($row['page-id']) { # if file was removed manually 
                    Sql::query(# Delete info about all cached pages 
                        'DELETE FROM '.$prefix.'cachedpages WHERE `page-id`=?', 
                        [$row['page-id']]
                    );
                    Sql::query(# Delete info about all cached pages 
                        'DELETE FROM '.$prefix.'cachedpagesblocks WHERE `page-id`=?', 
                        [$row['page-id']]
                    );
                }
            }
        }
    }


    /** 
     * Create cache file of the page
     * @param string $href Home page relative URL of the page
     * @param int $pageId
     * @param string $html HTML code of the page
     * @return bool   
     */
    public static function createFile($href, $pageId, $html)
    {

        # DEPRECATED since v14.0.16. REMOVE THIS
        if (!(Sql::tableExists('%cachedpages') && Sql::tableExists('%cachedpagesblocks') && file_exists(Blox::info('site','dir').'/cached')))
            return;

        if ($href === '') {
            if ($pageId == 1)
                $name = 'page-1';
            else {
                Blox::error('Error creating cache file: This is not home page but href is empty');
                return false;
            }
        } else {
            //$name = Str::sanitizeAlias($href);//, true
            # See also #cacheAlias
            $str = mb_strtolower($href);
            $str = rtrim($str, '/');
            $str = preg_replace('~[^\\pL0-9_-]+~u', '-', $str); # Replace nonletters and nondigits by "-"
            $str = preg_replace("~[-]+(?!$)~u", "-", $str); # Remove double "-" not in the end (because of not unique aliases of human urls)
            $str = ltrim($str, '-'); # Trim "-"
            if (!$str)
                return;
            $name = $str;
        }
        #
        $d = Blox::info('site','dir').'/cached';
        $cacheFile = $d.'/'.$name.'.html';
        if (file_exists($cacheFile)) {
            # Change file name of the another page too because of #fastway
            if ($name2 = Files::uniquizeFilename($d, $name, 'html')) {
                $cacheFile2 = $d.'/'.$name2.'.html';
                if (rename($cacheFile, $cacheFile2)) # another page
                    if (false !== Sql::query('UPDATE '.Blox::info('db','prefix').'cachedpages SET `file-name`=? WHERE `file-name`=?', [$name2, $name])) { # another page
                        if ($name = Files::uniquizeFilename($d, $name2, 'html'))
                            $cacheFile3 = $d.'/'.$name.'.html'; # current page
                }
            }
            #
            if ($cacheFile3)
                $cacheFile = $cacheFile3;
            else {
                Blox::error('Error creating cache file "'.$cacheFile.'" for the URL "'.$href.'"');
                return false;
            }
        }
        
        # Create cache file
        if ($fhandle=fopen($cacheFile,"a+")) {
            flock($fhandle,LOCK_EX);
            ftruncate($fhandle,0);// If block was updated the file should be deleted
            $html = preg_replace(['~^\s+~um', '~\s+$~um', '~\s\s+~um'], ['', '', ' '], $html);# Indent left and remove empty lines. Do not remove line breaks because of js-codes with "//"-comments and without trailing ";"
            if (fwrite($fhandle, $html) === FALSE)
                return;
            $sql = 'INSERT '.Blox::info('db','prefix').'cachedpages VALUES (?,?,?) ON DUPLICATE KEY UPDATE `page-id`=?, `file-name`=?';//$sql = 'REPLACE '.Blox::info('db','prefix').'cachedpages VALUES(?,?,?)';
            Sql::query($sql, [$href, $pageId, $name, $pageId, $name]);
            fflush($fhandle);
            flock($fhandle,LOCK_UN);
            fclose($fhandle);
        }
        return true;
    }


    /** 
     * Register block id of the cached page
     * @param int $pageId
     * @param int $regularId
     * @return void
     */
    public static function registerBlock($pageId, $regularId)
    {
        # DEPRECATED since v14.0.16. REMOVE THIS
        if (!(Sql::tableExists('%cachedpages') && Sql::tableExists('%cachedpagesblocks') && file_exists(Blox::info('site','dir').'/cached')))
            return;
        
        $sql = 'INSERT '.Blox::info('db','prefix').'cachedpagesblocks VALUES (?, ?) ON DUPLICATE KEY UPDATE `page-id`=?';
        Sql::query($sql, [$pageId, $regularId, $pageId]);
    }
    
##############################################################


    public static function delete()
    {
        # DEPRECATED since v14.0.16. REMOVE THIS
        if (!(Sql::tableExists('%cachedpages') && Sql::tableExists('%cachedpagesblocks') && file_exists(Blox::info('site','dir').'/cached')))
            return;
        
        $t1 = Blox::info('db','prefix').'cachedpagesblocks';
        if (Sql::tableExists($t1))
            Sql::query('TRUNCATE '.$t1);
        $t2 = Blox::info('db','prefix').'cachedpages';
        if (Sql::tableExists($t2))
            Sql::query('TRUNCATE '.$t2);
        # Delete all files in the folder
        if (file_exists(Blox::info('site','dir').'/cached')) {
            foreach (glob(Blox::info('site','dir').'/cached/*') as $fn)
                unlink($fn);
        }
        return true;
    }


    public static function deleteByBlock($regularId) {
        # DEPRECATED since v14.0.16. REMOVE THIS
        if (!(Sql::tableExists('%cachedpages') && Sql::tableExists('%cachedpagesblocks') && file_exists(Blox::info('site','dir').'/cached')))
            return;
        
        $sql = "SELECT * FROM ".Blox::info('db','prefix')."cachedpagesblocks WHERE `block-id`=?";
        if ($result = Sql::query($sql, [$regularId])) {
            while ($row = $result->fetch_assoc())
                $pages[] = $row['page-id'];
            $result->free();
        }
        #
        if (isset($pages)) {
            $pages = array_unique($pages);
            foreach ($pages as $pageId)
                self::deleteByPage($pageId);
        }
        $sql = "DELETE FROM ".Blox::info('db','prefix')."cachedpagesblocks WHERE `block-id`=?";
        Sql::query($sql, [$regularId]);
    }


    /*
     * Delete the cache of the regular page and caches of all pseudopages
     */
    public static function deleteByPage($pageId) {
        # DEPRECATED since v14.0.16. REMOVE THIS
        if (!(Sql::tableExists('%cachedpages') && Sql::tableExists('%cachedpagesblocks') && file_exists(Blox::info('site','dir').'/cached')))
            return;
        
        $sql = "SELECT * FROM ".Blox::info('db','prefix')."cachedpages WHERE `page-id`=?";
        if ($result = Sql::query($sql, [$pageId])) {
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $cacheFile = Blox::info('site','dir')."/cached/{$row['file-name']}.html";
                    if (file_exists($cacheFile))
                        Files::unLink($cacheFile);
                }
            }
            $result->free();
        }
        $sql = "DELETE FROM ".Blox::info('db','prefix')."cachedpages WHERE `page-id`=?";
        Sql::query($sql, [$pageId]);
        $sql = "DELETE FROM ".Blox::info('db','prefix')."cachedpagesblocks WHERE `page-id`=?";
        Sql::query($sql, [$pageId]);
    }
 
    /*
     * @param int $pageId 
     * @param array $pages Gather here all regular descendant page ids (in the indexes of array)
     */
    public static function gatherDescendantPages($pageId, &$pages=[])
    {
        $sql = 'SELECT `id` FROM '.Blox::info('db','prefix').'pages WHERE `parent-page-id`=?';
        if ($result = Sql::query($sql, [$pageId])) {
            while ($row = $result->fetch_assoc()) {
                if ($row['id']) {
                    $pages[$row['id']] = true;
                    self::gatherDescendantPages($row['id']);
                }
            }
            $result->free();
        }
    }
   
}