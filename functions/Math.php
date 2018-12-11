<?php

class Math
{
    /**
     * Divide integers
     *
     * @param int $dividend
     * @param int $divisor
     * @param int $remainder
     * @param bool $complete Complete quotient
     */
    public static function divideIntegers($dividend, $divisor, &$remainder=null, $complete=null)
    {
        if (empty($divisor))
            return;
        
        # If one is negative
        if ($dividend < 0 && $divisor < 0)
            ;
        elseif ($dividend < 0 ) {
            $dividend = -$dividend;
            $isNegative = true;
        } elseif ($divisor < 0) {
            $divisor = -$divisor;
            $isNegative = true;
        }
        $remainder = $dividend % $divisor;
        $dividend -= $remainder;
        for($quotient = 0; $dividend != 0; $quotient++) {
            $dividend -= $divisor;
        }
        if ($complete && $remainder)
            $quotient++;
        if ($isNegative)
            $quotient = -$quotient;
        return $quotient;
    }

}