<?php
/**
 * Methods to work with strings
 */
class Str
{
  
    /** 
     * Check if a string data matches the type specified in the second argument $type.
     *
     * @param string $value
     * @param string $type Possible values: 'email', 'password', 'login', 'numeric', 'filename', 'phone'. To any of the types you can add a 'not-empty' (with a space) or to use it alone.
     * @param string $message A message about type mismatch.
     * @return boolean
     * 
     * @todo Rename to valid()
     * @todo Data validators https://habrahabr.ru/post/308298/
     */
    public static function isValid($value, $type, &$message='')
    {
        $types = explode(' ', $type);
        # if value is empty, but there was no requirement to check for emptiness
        if (empty($value) && !in_array('not-empty', $types))
             return true;
        foreach ($types as $type2) 
        {
            if ($type2 == 'email') {
                $regex="/^[a-zA-Z0-9._-]+@([a-zA-Z0-9.-]+\.)+[a-zA-Z0-9.-]{2,9}$/u";
                $msg = Blox::getTerms('not-email');
            } elseif ($type2 == 'password') {
                $regex  = '~[';
                $regex .= 'a-zA-Z0-9';  # Letters, Digits
                $regex .= '!@#$%&?*(){}<>,.:;+=-_/|';  # Symbols
                $regex .= '\\\[\]\^';  # Special characters
                $regex .= ']{4,24}~u';
                $msg = Blox::getTerms('not-password');
            } elseif ($type2 == 'login') {
                $regex="/^[a-zA-Z0-9_][a-zA-Z0-9._-]{2,23}$/u";
                $msg = Blox::getTerms('not-login');
            } elseif ($type2 == 'numeric') {
                $regex="/^(\d|-)?(\d|,)*\.?\d*$/u";
                $msg = '';
            } elseif ($type2 == 'filename') {
                $regex="/(([/]|[\\])[a-zA-Z0-9._-]+)$/u"; // slash or backslash then any number of latin letters, digits, dotes, hyphens, underlines towards the end
                $msg = '';
            } elseif ($type2 == 'not-empty') {
                $regex="/\S/";
                $msg = Blox::getTerms('not-not-empty');
            } elseif ($type2 == 'tpl') {
                $regex="~^[a-zA-Z0-9_!/.-]*$~u"; # Although when reading filenames there are no "/"
                $msg = Blox::getTerms('not-tpl');
            } elseif ($type2 == 'phone') { # Example of nonstandard check, i.e. without $regex var
                $valid = false;
                $v = preg_replace( '~[^0-9]~u', '', $value);
                if ($v > 9) { # more than 4 digits
                    $v = preg_replace( '~\W~iu', '', $value); # get only the letters
                    $v = preg_replace( '~[0-9]~u', '', $v);
                    if (strlen($v) < 4) # less than 4 letters
                        $valid = true;
                }
                if (!$valid)
                    $errorMessage = Blox::getTerms('not-phone') ?: ' ';
            } else {
                return false;
            }
            
            
            
            # Separate collecting of validity and emptiness of data
            if ($regex) {
                if ($type2=='not-empty') {                
                    if (!preg_match($regex, $value))
                        $emptyMessage = $msg ?: ' ';
                } elseif (!preg_match($regex, $value))
                    $errorMessage .= $msg ?: ' ';
            } 
        }

        if (isset($emptyMessage))
            $message = $emptyMessage;
        elseif (isset($errorMessage))
            $message = $errorMessage;
        else {
            $message = '';
            return true;
        }
        return false;
    }



    /**
     * Split a string by the mark
     *
     * @param string $str 
     * @param string $mark 
     * @param string $searchFromEnd Look for the mark from the end
     * @return array [0 => string before mark, 1 => string after mark]
     */
    public static function splitByMark($str, $mark, $searchFromEnd=null)
    {
        if (empty($str) || empty($mark))
            return;

        $markLength = mb_strlen($mark);
        if (empty($searchFromEnd))
            $position = mb_strpos($str, $mark);
        else
            $position = mb_strrpos($str, $mark);


        if ($position === false)
            return false;
        else {
            $parts[0] = mb_substr($str, 0, $position);
            $parts[1] = mb_substr($str, $position + $markLength);
            return $parts;
        }
    }



    /**
     * Get a string before the mark
     *
     * @param string $str 
     * @param string $mark 
     * @param string $searchFromEnd Look for the mark from the end
     * @return string
     */
    public static function getStringBeforeMark($str, $mark, $searchFromEnd=null)
    {
        $parts = self::splitByMark($str, $mark, $searchFromEnd);
        if ($parts === false)
            return false;
        else
            return $parts[0];
    }


    /**
     * Get a string after the mark
     *
     * @param string $str 
     * @param string $mark 
     * @param string $searchFromEnd Look for the mark from the end
     * @return string
     */
    public static function getStringAfterMark($str, $mark, $searchFromEnd=null)
    {
        $parts = self::splitByMark($str, $mark, $searchFromEnd);
        if ($parts === false)
            return false;
        else
            return $parts[1];
    }




    /**
     * Declination of words and phrases before and after integers
     * 
     * @param int $number
     * @param array $prewords   
     * @param array $afterwords
     * @param array $options ['lang'=>'ru', 'no-number' => false]
     * @return string Combination of declined words, number and afterword
     */
    public static function declineWords($prewords, $number, $afterwords=[], $options=[])
    {
        if (!($number && ($prewords || $afterwords))) {
            Blox::prompt(sprintf(Blox::getTerms('not-enough-args'), 'Str::declineWords('.$prewords.', '.$number.', '.$afterwords.')'), true);
            return false;
        }

        if ($options)
            Arr::formatOptions($options);
        
        # Defaults
        $options += [
        	'lang' => Blox::info('site','lang'),
            'no-number' => false,
        ];
        if (is_callable($declineWords = Blox::getTerms('decline-words'))) {
            return $declineWords($prewords, $number, $afterwords, $options);
        } else {
            Blox::prompt(sprintf(Blox::getTerms('no-decline-rules'), $options['lang']), true);
        }
    }




    /** 
     * @param int|string $x 
     * @param string|array $options
     *     'zero' — method will return true for the 0 and '0' too
     *     'negative' — method will return true for negative integers too (for -9 and '-9')
     * @return bool Returns true if $var is an integer or a string consisting of digits.
     */
    public static function isInteger($x, $options=[])
    {     
        if ($options)
            Arr::formatOptions($options);
        
        //if ($options && is_string($options))
            //$options = [$options];
        
        if (is_int($x)) {
            if ($x === 0) {
                if ($options['zero'])
                    return true;
            } elseif ($x < 0) {
                if ($options['negative'])
                    return true;
            } else
                return true;
        } elseif (is_string($x)) {
            if ($x == '0') {
                if ($options['zero'])
                    return true;
            } elseif ($x[0] == '-') {
                if ($options['negative'])
                    return ctype_digit(substr($x, 1));
            } else
                return ctype_digit($x);
        }
    }
    

    
    
    
    /**
     * Get a random string of of digits and Latin letters in upper case.
     *
     * @param int $numOfChars
     * @param bool $noO Exclude zero "0" and letter "O" from return.
     * @return string
     */ 
    public static function genRandomString($numOfChars, $noO=null)
    {
        $str = '';
        for ($i=1; $i <= $numOfChars; $i++){
            $ch = strtoupper(base_convert(rand(0,35), 10, 36));
            if ($noO and ($ch == '0' || $ch == 'O')){
                # We do not recursively, since the probability to obtain "O" a second time is very small
                $ch = strtoupper(base_convert(rand(0,35), 10, 36)); 
                if ($ch == '0' || $ch == 'O')
                    $ch = 'W';}
            $str .= $ch;
        }
        return $str;
    }




    
    
    /**
     * Convert a string to use as alias in human friendly URLs
     *
     * @param string $str
     * @param bool $transliterate 
     * @return string To transliterate to latin from current lang
     */ 
    public static function sanitizeAlias($str, $transliterate=false)
    {
        if (!$str)
            return false;
        $str = Text::stripTags($str,'strip-quotes');
        $str = mb_strtolower($str);
        $str = preg_replace('~[^\\pL0-9_]+~u', '-', $str); # Replace nonletters and nondigits by "-"
        if ($transliterate)
            $str = self::transliterate($str);

        $str = preg_replace("/[-]+/u", "-", $str); # Remove double "-"
        $str = trim($str, '-'); # Trim "-"
        if (self::isInteger($str)) {
            Blox::prompt(sprintf(Blox::getTerms('digits-are-replaced'), $str, $str.'-'),  true);
            $str .= '-';
        }      
        if (!$str)
            return false;
        return $str;
    }
    


    
    /**
     * Replace non-Latin letters from current lang to Latin letters 
     */
    public static function transliterate($str) 
    {   
        if (!$str)
            return false;
        $str = mb_strtolower($str);
        
        if ($translits = Blox::getTerms('transliterations')) {
            $str = strtr($str, $translits);
            $str = preg_replace("~[^a-z0-9`'_]~iu", '-', $str); # returns "~[^a-zA-Z0-9`'_-\.]~iu" empty
            $str = preg_replace("~-{2,}~u", '-', $str); # remove duplicated "-"
            $str = preg_replace("~^-~u", '', $str); # remove initial "-"
            if ($replacements = Blox::getTerms('replacements')) {
                foreach ($replacements as $pattern => $replacement)
                    $str = preg_replace($pattern, $replacement, $str);
            }   
            if ($str)
        	    return $str;
            else
                return false;
        } else {
            Blox::prompt(Blox::getTerms('no-translit-array'), true);
            return false;
        }
    }
    
    /**
     * Set new decimal mark and thousands separator in number.
     *
     * @param float|int|string $number
     * @param array $format Defaults are:
     *    'decimals'=>0,   The number of digits in the decimal part
     *    'mark'=>'.',     Decimal mark
     *    'separator'=>'', Thousands separator
     * @param string oldMark The string of possible decimal marks that may occur in number before.
     * @param int $multiplier It is used to adjust prices up or down
     * @return string To transliterate to latin from current lang
     */ 
    public static function formatNumber($number, $format=[], $oldMark=",.·'", $multiplier=1) 
    {
        if ($format) 
        {   
            $number = (string)$number;
            $format += ['decimals' => 0, 'mark' => '.', 'separator' => '']; # Default format 
            # Find decimal mark among nondigits (non digits)
            if (strlen($oldMark) == 1) {
                $oldMark2 = $oldMark;
            } elseif ($nondigits = str_replace(' ', '', $number)) { # Replace spaces
                if ($nondigits = preg_replace('/^-/', '', $nondigits)) { # Remove minus
                    if ($nondigits = preg_replace('/[0-9]/', '', $nondigits)) { # Get all non digits
                        $lastNondigit = substr($nondigits, -1); # Get last char
                        $lastNondigitCount = substr_count($nondigits, $lastNondigit);
                        if ($lastNondigitCount == 1) { # Last nondigit occur once
                            if (strpos($oldMark, $lastNondigit) !== false) { # It is legitimate
                                $oldMark2 = $lastNondigit;
                            } else { # do not format
                                return $number;
                            }
                        } elseif ($oldMark && strpos($oldMark, $lastNondigit)===false && $lastNondigitCount == strlen($nondigits)) {
                            ; # Last nondigit is not legitimate and all nondigits are equal to this lastNondigit
                        } else { # do not format
                            return $number;                    
                        }
                    }
                }
            }

            if ($oldMark2) {
                $number = str_replace($oldMark2, '~', $number); # Temporary decimal mark
                $number = preg_replace('/[^0-9-~]/', '', $number); # Remove all nondigits except 'minus' and old decimal mark
                $number = str_replace('~', '.', $number); # Format to float                    
            } else
                $number = preg_replace('/[^0-9-]/', '', $number); # Remove all nondigits except 'minus'
         
            if (isEmpty($multiplier))
                $multiplier = 1;
            if ($multiplier != 1)
                $number = $number * $multiplier;
            # Convert float to new format
            $number = number_format($number, 
                $format['decimals'], 
                $format['mark'], 
                $format['separator']
            );  
        }
        return $number;    
    }




    /** RESERVED
     * @todo Do not reformat datetime - do only refine the setting $dateTimeFormat by trancating time part for example. formatDate($str, 'year,month, day')
     * 
     * Convert datetime from one format to another
     *
     * @param string $str 
     * @param string $format according to http://php.net/manual/en/datetime.formats.php
     * @return string
     
    public static function formatDate($str, $format) // , $oldformat='', $plus=null
    {
        if ($format)
            $str = date($format, strtotime($str));
        return $str;    
    }
    */ 
    
    




    /**
     * Convert shorthand byte notation to integer. Use for ini_get() directives: post_max_size and upload_max_filesize
     * @example '1K' will be converted to: 1024
     */    
    public static function normalizeShorthandBytes($val) 
    {
    	$suffix = strtoupper($val[strlen($val)-1]);
        $val2  = (int)substr($val, 0, -1);
        if ('K'==$suffix)
    		$val2 *= 1024;
        elseif ('M'==$suffix)
    		$val2 *= 1024*1024;
        elseif ('G'==$suffix)
    		$val2 *= 1024*1024*1024;
        else
            return (int)$val;
    	return $val2;
    }


    public static function getJsonError() {
        static $errors = [
            JSON_ERROR_NONE             => null,
            JSON_ERROR_DEPTH            => 'Maximum stack depth exceeded',
            JSON_ERROR_STATE_MISMATCH   => 'Underflow or the modes mismatch',
            JSON_ERROR_CTRL_CHAR        => 'Unexpected control character found',
            JSON_ERROR_SYNTAX           => 'Syntax error, malformed JSON',
            JSON_ERROR_UTF8             => 'Malformed UTF-8 characters, possibly incorrectly encoded'
        ];
        $error = json_last_error();
        return array_key_exists($error, $errors) ? $errors[$error] : "Unknown error ({$error})";
    }
    
}