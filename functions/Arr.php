<?php

class Arr
{   
    /** 
     * Returns a multidimensional array obtained by deleting from array $array1, all elements whose keys match keys of array $arr2.
     * Multidimensional analog of array_diff_key().
     * 
     * @param array $arr1 Multidimensional array
     * @param array $arr2 Multidimensional array
     * @return array   
     */
    public static function diffByKey($arr1, $arr2)
    {
        $arr = [];
        foreach($arr1 as $k => $v) {
            if (isset($arr2[$k])) {
                if (is_array($v) && $arr2[$k]) 
                    $arr[$k] = self::diffByKey($v, $arr2[$k]);
            } else {
                $arr[$k] = $v;
            }
        }
        $arr = Arr::remove($arr, [null]);
        return $arr;
    }
    
   

    /** 
     * Returns a multidimensional array composed of elements of $arr1 with keys that match the keys in $arr2.
     * Multidimensional analog of array_intersect_key().
     * 
     * @param array $arr1 Multidimensional array
     * @param array $arr2 Multidimensional array
     * @return array   
     */
    public static function intersectByKey($arr1, $arr2)
    {
        $arr1 = array_intersect_key($arr1, $arr2);
        foreach ($arr1 as $k => &$v)
            //if (is_array($v)) 2017-11-27 12:02
            if (is_array($v) && is_array($arr2[$k]))
                $v = self::intersectByKey($v, $arr2[$k]);
        return $arr1;
    }




    /**
     * Walk through a multidimensional array and handle its values without affecting the keys.
     * 
     * @param array $arr The processed array. The result will appear in the same variable.
     * @param string $funcName The name of the handle function. The function returns new value of the array element. First param of the function is an element value of the array $arr. The remaining params of the function are elements of the array $funcParams.
     * @param array $funcParams Second, third, ... params of the function $funcName
     * 
     * @example Arr::walk($arr, 'substr', ['1','3']); // alter $array to get from second up to fourth character from each value 
     * @todo Remake like Url::arrayToQuery
     */ 
    public static function walk(&$arr, $funcName, $funcParams=[]) 
    { 
        $func2 = function(&$value, $key, $userdata) 
        { 
            $funcParams = array_merge([$value], $userdata[1]); 
            $value = call_user_func_array($userdata[0], $funcParams); 
        }; 
        array_walk_recursive($arr, $func2, [$funcName, $funcParams]); 
    } 
    /*
    TODO: walk array
    1.
    array-walk-recursive()
    array-walk-recursive() processes only the values of the array, without affecting the keys http://ru2.php.net/manual/ru/function.array-walk-recursive.php
    2.
    For full control of the array, use http://ru2.php.net/manual/en/class.arrayiterator.php
        http://stackoverflow.com/questions/8587580/pass-by-reference-not-working-with-additional-parameters-for-array-walk-recursiv
            $flattenedArray = [];
            foreach (new RecursiveIteratorIterator(
                         new RecursiveArrayIterator($baseArray),
                         RecursiveIteratorIterator::LEAVES_ONLY
                     ) as $value) {
                $flattenedArray[] = $value;
            }
    3. Specific function, not callback function
    function utf8enc($array) {
        if (!is_array($array)) return;
        $helper = [];
        foreach ($array as $key => $value) 
            $helper[utf8_encode($key)] = is_array($value) ? utf8enc($value) : utf8_encode($value);
        return $helper;
    }
    $enc_array = utf8enc($your_array);
    */




    /**
     * Sorts a multidimensional array by keys
     * @param array $arr
     */
    public static function orderByKey(&$arr=[]) 
    {
        if ($arr) {
            ksort($arr, SORT_NATURAL);
            foreach($arr as &$v)
                if (is_array($v))
                    self::orderByKey($v);
        }
    }






    /**
     * Merge two or more arrays into one recursively.
     * If each array has an element with the same key, the latter will overwrite the former one, that differs from array_merge_recursive().    
     * 
     * @param array $a1 array to be merged to
     * @param array $a2 array to be merged from. You can specify additional arrays via third, fourth param etc.
     * @return array
     */
    public static function mergeByKey($a1,$a2) # $a3, $a3, ...
	{
		$args=func_get_args();
		$result=array_shift($args);
		while($args) {
			$nex=array_shift($args);
			foreach($nex as $k => $v) {
				if (is_array($v) && isset($result[$k]) && is_array($result[$k]))
					$result[$k]=self::mergeByKey($result[$k],$v);
				else
					$result[$k]=$v;
            }
		}
		return $result;
	}


    
    
    /**
     * The array $arr2 is inserted into the array $arr1 above the element with the key $key1.
     *
     * @param array $arr1 Original array
     * @param array $arr2 The array to be inserted
     * @param string $key1 The key of the original array
     * @param bool $after Insert below the element with the key $key1.
     * @return array   
     */
    public static function wedge($arr1, $arr2, $key1=null, $after=false)
    {
        $length = count($arr1);    
        if (($offset = array_search($key1, array_keys($arr1))) === false) // if the key doesn't exist        
            $offset = $length;
        if ($after)
            $offset++;        
        return 
            array_slice($arr1, 0, $offset, true)
            + (array)$arr2
            + array_slice($arr1, $offset, $length, true)
        ;        
    }

    


    
    /** 
     * Get branch of array defined by a chain of keys represented as a string without the quotes (like an array in url or in form)
     * @deprecated Use Arr::getByKeys()
     * @param array $arr
     * @param string $chainOfKeys The chain of keys as a string that identifies the branch of the array. It is available to put any letters in front of $chainOfKeys that has no effect, example: 'xx[a][b][1]'.
     * @return array
     * @example
     *    $arr['a']['b'][1][2] = 'zz';
     *    print_r(Arr::getByChainOfKeys($arr['a'], '[b][1]');
     *    //returns array: [[2]=>zz]
     */
    public static function getByChainOfKeys($arr, $chainOfKeys)
    {
        preg_match_all('~\[([^\]]*)\]~', $chainOfKeys, $keys);
        $arr2 = $arr;
        foreach ($keys[1] as $k)
            $arr2 = $arr2[$k];
        return $arr2;
    }







    /**
     * Get the value of array element by sequential array of keys (non-associative array)
     * @param array $arr Initial array 
     * @param array $keys Sequential array of keys, where the initial element corresponds to the oldest key in initial array 
     * @return array Element of the array
     * @example 
     *  $arr['a']['b'][1][2] = 'zz';
     *  print_r(Arr::getByKeys($arr['a'], ['b', 1]);
     *  //returns: [[2]=>zz]
     */ 
    public static function getByKeys($arr, $keys)
    {
        foreach ($keys as $k)
            $arr = $arr[$k];
        return $arr;
    }
    
    /**
     * Remove the element of array by sequential array of keys (non-associative array)
     * @param array $arr Initial array 
     * @param array $keys Sequential array of keys, where the initial element corresponds to the oldest key in initial array 
     * @return array 
     */ 
    public static function removeByKeys($arr, $keys)
    {
        $fakeValue = 'O0O0O0O0O0O0';
        $aa = self::addByKeys($arr, $keys, $fakeValue);
        return Arr::remove($aa, $fakeValue);
    }
    
    
    
    /**
     * Add element to the array by sequential array of keys (non-associative array)
     * @param array $arr Initial array 
     * @param array $keys Sequential array of keys, where the initial element corresponds to the oldest key in initial array 
     * @param mixed $value
     * @return array
     * @example 
     *  $arr = [];
     *  print_r(Arr::addByKeys($arr, ['a', 1], 'zz');
     *  //returns: ['a' => [1 => 'zz']]
     */
    public static function addByKeys($arr, $keys, $value)
    {
        $buffer = $value;
        foreach (array_reverse($keys) as $keys2) {
            $result = [];
            $result[$keys2] = $buffer;
            $buffer = $result;
            
        }
        return Arr::mergeByKey($arr, $result);
    }



    
    
    
    

    /**
     * Get number of first not empty elements of the array.
     * For empty array returns 0.   
     * If after an empty element (null, ") follows not empty element, returns false.
     * It is used to process the result of the function func_get_args().
     *
     * @param array $arr Nonassociative array
     * @return int Number of first not empty elements of the array
     */
    public static function getUnbrokenSize($arr) # args: regularId, filter, x1, x2
    {
        if (!is_array($arr))
            return false;        
        if ($arr) {
            $size = 0;
            foreach ($arr as $value) {                 
                ++$size;
                if (is_null($value)) {
                    if (isEmpty($size2))
                        $size2 = $size - 1;}
                elseif ($value==='')
                    return false;
                elseif (!isEmpty($size2))
                    return false;                
            }            
            return (isEmpty($size2)) ? $size : $size2;
        }
        else
            return 0;
    }
    

    
    /** 
     * Removes from array all elements with values given in parameter $values.
     * Removes empty arrays too.
     *
     * @param array $arr Ititial array.
     * @param mixed $values Consider the data type. If this parameter is omitted, elements with empty values will be removed.
     * @return array.
     *
     * @example Arr::remove($arr) - removes element with empty values. Same as Arr::remove($arr, [false, '', 0, null])
     * @example Arr::remove($arr, false) - removes element with false values.
     * @example Arr::remove($arr, 0) - removes element with 0 values.
     * @example Arr::remove($arr, ['green', 'red']) - removes element with values 'green' and 'red'.
     */
    public static function remove($arr=[], $values)
    {
        # Remove elements with empty values
        if (is_null($values)) {
            /*
            # Var.1
            $values = [false, '', 0, '0', null];
            */
            # Var.2            
            foreach ($arr as $k => $v) {
                if (is_array($v))
                    $aa = self::remove($v);
                if (empty($aa))
                    unset($arr[$k]);
            }
        } else { # With $values array
            if (!is_array($values))
                $values = [$values];

            foreach ($arr as $k => $v) {
                if (is_array($v)) {
                    if ($v) {
                        if ($aa = self::remove($v, $values))
                            $arr[$k] = $aa;
                        else
                            unset($arr[$k]);
                    } else # empty array
                        unset($arr[$k]);
                    
                } elseif (in_array($v, $values, true))
                    unset($arr[$k]);
            }            
        }
        return $arr;
    }
    
    
    /** 
     * Transform a string or array of strings to associative array with the values "true".
     * Associative elements of original array will be not affected.
     * This method is typically used inside functions and methods to format parameters as an array.
     *
     * @param mixed $options (array or string)
     *
     * @example $options='exit' will be formatted to ['exit'=>true]
     * @example $options=['exit', 'mode'=>'edit'] will be formatted to ['exit'=>true, 'mode'=>'edit']
     */
    public static function formatOptions(&$options=[])
    {
        if ($options) {
            if (is_array($options)) {
                foreach ($options as $k=>$v) {
                    if (is_int($k)) {
                        $options[$v] = true;
                        unset($options[$k]);
                    }
                }
            } elseif (is_string($options)) {
                $aa = $options;
                $options = [];
                $options[$aa] = true;
            } 
        }
    }
    
    
    /** RESERVED
    public static function removeEmpty($arr)
    {
        foreach ($arr as $k => $v) {
            if (is_array($v))
                $aa = self::removeEmpty($v);
            if (empty(aa))
                unset($arr[$k]);
        }
        return $arr;
    }
    */
    
    
    
    /** NOTUSED
    public static function getNeighbors($arr, $key)
    {
        # http://php.net/manual/en/function.next.php
        krsort($arr);
        $keys = array_keys($arr);
        $keyIndexes = array_flip($keys);
        
        $neighbors = [];
        if (isset($keys[$keyIndexes[$key]-1]))
            $neighbors['next'] = $keys[$keyIndexes[$key]-1];
        if (isset($keys[$keyIndexes[$key]+1]))
            $neighbors['prev'] = $keys[$keyIndexes[$key]+1];

        return $neighbors;
    }
    */
    
    
    /** NOTUSED
    Assign a value to one element of the array by the key chain specified as a string    
    Keys to set, starting with the most senior key
    It is used when the keys are not known in advance.
    USAGE:
        $arr = [];
        Arr::setValueByStringOfKeys($arr, '[2][key3][1]', 'zz');
        This is equivalent to
            $arr[2]['key3'][1] = 'zz';
    
    public static function setValueByStringOfKeys(&$arr, $stringOfKeys, $value)
    {
        preg_match_all('~\[([^\]]*)\]~', $stringOfKeys, $keys);        
        $aa = array_reverse($keys[1]);
        $topKey = array_pop($aa); # Last element
        foreach ($aa as $k) {
            $arr2 = [];
            $arr2[$k] = $value;
            $value = $arr2;            
        }
        $arr[$topKey] = $value;
    }
    */
    
    
    /* 2014-10-12 18:43
    function setArrayPointer(&$arr,$key)
    {
        if (empty($arr)) return;
        reset($arr);
        while (key($arr) !== $key)
            if (!next($arr))
                break;
    }
    */

}