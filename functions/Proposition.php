<?php
/**
 * @todo optimize "Check the number of arguments" or remove it
 */
 
class Proposition
{
    private static 
    	$table,
        $formula,
    	$objectId,
    	$subjectId
    ;

    private static function init($formula, $subjectId=null, $objectId=null)
    {
        $formula = Sql::sanitize($formula);//deprecate

        if (!is_null($subjectId) && $subjectId != 'all' && $subjectId != 'any') {
            $subjectId = Sql::sanitizeInteger($subjectId);
            if (empty($subjectId))
                $error .= " The Subject ID is empty!";
        }

        if (!is_null($objectId) && $objectId != 'all' && $objectId != 'any') {
            $objectId = Sql::sanitizeInteger($objectId);
            if (empty($objectId))
                $error .= " The object ID is empty!";
        }
        self::$table = Blox::info('db','prefix')."propositions";
        # formula
        if (empty($formula)) {
            Blox::error(Blox::getTerms('formula-is-empty'));
            return false;
        } else
            self::$formula = $formula;

        # Check the number of arguments in the old propositions
        $sql = 'SELECT * FROM '.self::$table.' WHERE `formula`=? LIMIT 1';
        if ($result = Sql::query($sql, [self::$formula])) {
            if ($row = $result->fetch_assoc()) {
                $result->free();
                #subject-id
                if ($row['subject-id'] === 0) {
                    if ($subjectId)
                        $error .= " New subject-id is not empty";//
                } elseif ($row['subject-id'] > 0) {
                    if (is_null($subjectId))
                        $error .= " New subject-id is null";
                }
                # object-id
                if ($row['object-id'] === 0){
                    if ($objectId)
                        $error .= " New object-id is not empty";
                } elseif ($row['object-id'] > 0){
                    if (is_null($objectId))
                        $error .= " New object-id is null!";}
            }
        }

        self::$subjectId = $subjectId;
        self::$objectId = $objectId;

        if ($error){
            Blox::error(sprintf(Blox::getTerms('error-in-args'), $error, self::$formula));
            self::$formula = null;# KLUDGE stop / destroy
            return false;
        }
        return true;
    }




	public static function set($formula, $subjectId=null, $objectId=null, $val)
    {   
        if (!self::init($formula, $subjectId, $objectId))
            return false;
        $sqlValues = [];
        
        # Reset
        $sql = 'DELETE FROM '.self::$table.' WHERE `formula`=?';
        $sqlValues[] = self::$formula;
        if (!is_null(self::$subjectId)) {
             $aa = (int) self::$subjectId;
             if (is_int($aa) && $aa) {  # because (int) new == 0
                 $subjectId = self::$subjectId;
                 $sql .= ' AND `subject-id`=?';
                 $sqlValues[] = self::$subjectId;
             } else {
                   Blox::error(Blox::getTerms('subj-id-is-not-int'));
                   return false;
             }
        } else
            $subjectId = 0;


        if (!is_null(self::$objectId)) {
             $aa = (int) self::$objectId;
             if (is_int($aa) && $aa) {
                 $objectId = self::$objectId;
                 $sql .= ' AND `object-id`=?';
                 $sqlValues[] = self::$objectId;
             } else {
                   Blox::error(Blox::getTerms('obj-id-is-not-int'));
                   return false;
             }
        } else
            $objectId = 0;

		# Delete a proposition
        if (isEmpty(Sql::query($sql, $sqlValues)))
            return false;

        
        # Set a proposition
        if ($val) {
            if (!Sql::query('INSERT '.self::$table.' VALUES (?, ?, ?)', [self::$formula, $subjectId, $objectId]))
                return false;
        }
        return true;
    }





	public static function get($formula, $subjectId=null, $objectId=null)
    {
        if (!self::init($formula, $subjectId, $objectId))
            return false;
        
        $sqlValues = [];
        
        $sql = 'SELECT `subject-id`, `object-id` FROM '.self::$table.' WHERE `formula`=?';
        $sqlValues[] = self::$formula;
        if (is_null(self::$subjectId) || 'all' == self::$subjectId) 
        {
            if (is_null(self::$objectId) || 'all' == self::$objectId) {
                ;
            } elseif ('any' == self::$objectId) {
                $sql .= ' GROUP BY `subject-id`';
            } elseif ((int) self::$objectId) { # because (int) new == 0
                $sql .= ' AND `object-id`=?';
                $sqlValues[] = self::$objectId;
            }
        } elseif ('any' == self::$subjectId) {
            if (is_null(self::$objectId) || 'all' == self::$objectId) {
                $sql .= ' GROUP BY `object-id`';
            } elseif ('any' == self::$objectId) {
                $sql .= ' LIMIT 1';
            } elseif ((int) self::$objectId) {
                $sql .= ' AND `object-id`=? LIMIT 1';
                $sqlValues[] = self::$objectId;
            }
        } elseif ((int) self::$subjectId) {# because (int) new == 0
            if (is_null(self::$objectId) || 'all' == self::$objectId) {
                $sql .= ' AND `subject-id`=?';
                $sqlValues[] = self::$subjectId;
            } elseif ('any' == self::$objectId) {
                $sql .= ' AND `subject-id`=? LIMIT 1';
                $sqlValues[] = self::$subjectId;
            } elseif ((int) self::$objectId) {
                $sql .= ' AND `subject-id`=? AND `object-id`=?';
                $sqlValues[] = self::$subjectId;
                $sqlValues[] = self::$objectId;
            }
        }

        if ($result = Sql::query($sql, $sqlValues)) {
            while ($row = $result->fetch_assoc())
                $propositions[] = $row;
            $result->free();
        }
        
        if ($propositions)
            return $propositions;
        else
            return false;
        /*
        subj/obj	all (null)	any	            O
        all (null)	_	        f2	            obj=O
        any	        f1	        limit=1	        obj=O  limit=1
        S       	subj=S	    subj=S  limit=1	subj=S & obj=O
        */

    }






	public static function delete($formula, $subjectId=null, $objectId=null)
    {
        if (!self::init($formula, $subjectId, $objectId))
            return false;
        $sqlValues = [];
        $sql = 'DELETE FROM '.self::$table.' WHERE `formula`=?';
        $sqlValues[] = self::$formula;
        if (!is_null(self::$subjectId)){
             $aa = (int) self::$subjectId;
             if (is_int($aa) && $aa) {
                 $sql .= ' AND `subject-id`=?';
                 $sqlValues[] = self::$subjectId;
             } elseif ('all' == self::$subjectId) {
                 ;
             } else {
                   Blox::error(Blox::getTerms('subj-id-is-not-valid'));
                   return false;
             }
        }
        if (!is_null(self::$objectId)){
             $aa = (int) self::$objectId;
             if (is_int($aa) && $aa) {
                 $sql .= ' AND `object-id`=?';
                 $sqlValues[] = self::$objectId;
             } elseif ('all' == self::$objectId) {
                 ;
             } else {
                   Blox::error(Blox::getTerms('obj-id-is-not-valid'));
                   return false;
             }
        }
        $aa = Sql::query($sql, $sqlValues); # TRUE, FALSE
        return $aa;
    }
   
}
