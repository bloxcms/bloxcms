<?php
/**
 * Miscellaneous methods for convenience
 */
class Misc
{
    /**
     * Compact pagination. 
     * Shorten the array of serial numbers (from 1 to max) so that remain only first and last elements and group of elements near the current element (spot).
     * The gap around the spot represented by elements with false value.
     * 
     * @param int $curr Current number (page)
     * @param int $numOfItems Number of items (pages)
     * @param int $spot Number of items to display in the spot. An odd number is desirable
     * @param int $threshold Minimal number of items to begin the compact pagination
     * @param int $reverse 
     */
    public static function paginate($curr=1, $numOfItems=1, $spot=5, $threshold=10, $reverse=false)
    {      
        if ($threshold > $numOfItems) 
            $spot = $numOfItems;
           
        if ($numOfItems <= $spot) {
            for ($i = 1; $i <= $numOfItems; $i++)
                $result[] = $i;
        } else {
            $firstNum = $curr - (int)floor($spot/2);
            if ($firstNum < 1) 
                $firstNum = 1;
            if (($firstNum + $spot - 1) > $numOfItems) 
                $firstNum = $numOfItems - $spot + 1;
            # First item
            if ($firstNum >= 2) {
                $result[] = 1;
                if ($firstNum > 2) 
                    $result[] = false;
            }
            # Items in the spot         
            for ($i = 0; $i <= $spot-1; $i++)
                $result[] = $i+$firstNum;
            # Last item
            if (($firstNum + $spot - 1) < $numOfItems) {
                if (($firstNum + $spot) < $numOfItems) 
                    $result[] = false;        
                $result[] = $numOfItems;
            }                
        }
        #
        if ($reverse) 
            $result = array_reverse($result);
        return $result; 
    }

   
    
}