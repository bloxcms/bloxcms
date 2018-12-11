<?php
    
    session_set_save_handler('openSess', 'closeSess', 'readSess', 'writeSess', 'destroySess', 'gcSess');      
        
    function openSess($aa, $bb)  // Parameters are used only during writing sessions to the file
    {
        return TRUE;
    }

    function readSess($sesId)
    {
        $sql = 'SELECT * FROM '.Blox::info('db','prefix').'sessions WHERE id=?';
        if ($result = Sql::query($sql, [$sesId])) {
            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $result->free();
                $sesData = base64_decode($row['data']);
                return 
                    $sesData;
            } else
                return '';
        } else
            return '';
    }

    
    function writeSess($sesId, $data)
    {
        $data2 = base64_encode($data);
        $sql = 'SELECT * FROM '.Blox::info('db','prefix').'sessions WHERE id=?';
        if ($result = Sql::query($sql, [$sesId])) {            
            $num = $result->num_rows;
            $result->free();
        }
        if ($num > 0) { # write into old record
             $timee = time();
             $sql = 'UPDATE '.Blox::info('db','prefix').'sessions SET time=?, data=? WHERE id=?';
             if (!isEmpty(Sql::query($sql, [$timee, $data2, $sesId])))
                return true;
        } else { # create a new record
            $timee = time();
            $sql = 'INSERT INTO '.Blox::info('db','prefix').'sessions SET id=?, time=?, start=?, data=?';
            $num = Sql::query($sql, [$sesId, $timee, $timee, $data2]);
            if ($num > 0) 
                return true;
        }

    }
    

    function closeSess()
    {
        $sesLife = strtotime('-1 day');
        gcSess($sesLife);
        return TRUE;
    }

    function destroySess($sesId)
    {
        $sql = 'DELETE FROM '.Blox::info('db','prefix').'sessions WHERE id=?';
        $result = Sql::query($sql, [$sesId]); //;
        if (empty($result))  
            return false;
        else 
            return true;
    }

    function gcSess($sesLife)
    {
        if (empty($sesLife))
            $sesLife = strtotime('-1 day');
        $sql = 'DELETE FROM '.Blox::info('db','prefix').'sessions WHERE time < ?';
        $result = Sql::query($sql, [$sesLife]); //;
        if (empty($result)) 
            return false;
        else 
            return true;
    }