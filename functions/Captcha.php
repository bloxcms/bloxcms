<?php
/** 
 * @todo Captcha::createImage using libraries\SimpleImage $img->text('Your Text', 'font.ttf', 32, '#FFFFFF', 'top', 0, 20);
 * @todo Sessions are quite secret for captcha, but consider storing data in DB (sessid, inputname, secretstring) and attempts-counter do using Auth::checkAttempts()
 */
class Captcha
{
    /** 
     * Get URL of the secret image.
     *
     * @param string  $inputName Value of the attribute "name" of the input element for captcha.
     * @param array $options = [
     *    'bg-color' => 'ff9' // hexadecimal background color (transparent by default)
     *    'color' => 'f00' // hexadecimal color of characters (000 by default)
     *    'font-file' => '' // path to the font file (Blox::info('cms','dir').'/assets/Verdana.ttf' by default).
     *    'font-size' => '16' // font size (12 by default)
     *    'num-of-chars' => '' // number of characters
     *    ];
     * @return string
     */
    public static function getImageUrl($inputName, $options=[])
    {
        $captchaDir = Blox::info('site','dir').'/temp/captcha';
        $counterFile = $captchaDir.'/_counter.txt';
        $maxNumOfAttempts = $_SESSION['Blox']['captcha'][$inputName]['max-num-of-attempts'] ?: 20;
        $options += [
            'num-of-chars'=>3,
            'color'=>'ff0000',
            'bg-color'=>'',
            'font-file'=>Blox::info('cms','dir').'/assets/Verdana.ttf',
            'font-size'=>14,# points
        ];
        # Delete old captcha images
        $imageFiles = glob("$captchaDir/*.png");
        foreach ($imageFiles as $imageFile)
        {
            $captchaImageTime = filemtime($imageFile);
            $currTime = time();
            if ($captchaImageTime + 3600 < $currTime )//Unix timestamp //an hour
                if (!unlink($imageFile))
                    Blox::error("Cannot remove file: $imageFile");
        }

        # Create a folder for the images
        Files::makeDirIfNotExists($captchaDir, 0777);
        
        #  Read counter
        $numOfFiles = file_get_contents($counterFile);
        if (empty($numOfFiles))
            $numOfFiles = 0;
        elseif ($numOfFiles > 99999)
            $numOfFiles = 0;

        if ($_SESSION['Blox']['captcha'][$inputName]['attempts-counter'] > $maxNumOfAttempts) {
            $secretString .= '';
            for ($i=0; $i<$options['num-of-chars']; $i++)
                $secretString .= ' ';} # Empty chars
        else {
            $secretString = Str::genRandomString($options['num-of-chars'], true);
            # Session is saved in the same keys as the input name, for example name='dat[2]' will be converted to $_SESSION['Blox']['captcha']['dat'][2]
            $_SESSION['Blox']['captcha'][$inputName]['secret-string'] = $secretString;
        }
        $imgFileName = strtoupper(base_convert($numOfFiles, 10, 36)).".png";# The file name is made on the base 36 numeral system (0-Z)
        $numOfFiles++;
        file_put_contents($counterFile, $numOfFiles);
        self::createImage($secretString, $captchaDir.'/'.$imgFileName, $options);
        return Blox::info('site','url').'/temp/captcha/'.$imgFileName;
    }



    /** 
     * Check the entered secret chars
     *
     * @param string $inputName Value of the attribute "name" of the input element for captcha.
     * @param string $inputValue Value of the captcha that user entered in the form field.
     * @return bool
     */
    public static function check($inputName, $inputValue)
    {
        if (!$inputValue)
            return false;
        $inputValue = trim($inputValue);
        $inputValue = preg_replace("/\s\s+/u", " ", $inputValue);
        $inputValue = strtoupper($inputValue);  
        if ($inputValue == $_SESSION['Blox']['captcha'][$inputName]['secret-string']) {
            $_SESSION['Blox']['captcha'][$inputName]['attempts-counter'] = 0;
            unset($_SESSION['Blox']['captcha'][$inputName]['secret-string']);
            return true; 
        }
        $_SESSION['Blox']['captcha'][$inputName]['attempts-counter']++;
        unset($_SESSION['Blox']['captcha'][$inputName]['secret-string']);
        return false;
    }
    

    

    /** 
     * Check if the number of attempts to enter the secret characters is exceeded
     *
     * @param string $inputName Value of the attribute "name" of the input element for captcha.
     * @param int $maxNumOfAttempts 
     * @return bool
     */
    public static function exceeded($inputName, $maxNumOfAttempts=20)
    {
        //$maxNumOfAttempts = $maxNumOfAttempts ?: 20;
        $_SESSION['Blox']['captcha'][$inputName]['max-num-of-attempts'] = $maxNumOfAttempts;
        if ($_SESSION['Blox']['captcha'][$inputName]['attempts-counter'] > $maxNumOfAttempts)
            return true;
    }

 


    /** 
     * @param string $inputName Value of the attribute "name" of the input element for captcha.
     * @param string $imgFile 
     * @param array $options
     */
    private static function createImage($str, $imgFile, $options)
    {
        $fontSize = $options['font-size']; # points not pixels
        ########## Settings ########
        # Chars
        $charShiftX = round(0.5*$fontSize);
        $charShiftXmin = -round(0.2*$fontSize);
        $charShiftXmax =  round(0.2*$fontSize);
        $charShiftY = round(1.5*$fontSize);
        $charShiftYmin = -round(0.5*$fontSize);
        $charShiftYmax =  round(0.5*$fontSize);
        $charPeriod = $fontSize;
        #Lines
        $lineLengthMin = round(0.5*$fontSize);
        $lineLengthMax = round(1.5*$fontSize);
        # Box size
        $height = 2*$fontSize;
        $width = $charPeriod*($options['num-of-chars'] + 1);
        ########## /Settings ########

        $im = imagecreate ($width, $height);
        if (empty($options['bg-color'])){
            $bg = imagecolorallocate($im, 0, 0, 0);
            imagecolortransparent($im, $bg); # Transparent background
        }
        else
        {
            $rgb = self::hexToRgb($options['bg-color']);
            $bg = imagecolorallocate ($im, $rgb['red'], $rgb['green'], $rgb['blue']);
        }

        $rgb = self::hexToRgb($options['color']);
        $color = imagecolorallocate ($im, $rgb['red'], $rgb['green'], $rgb['blue']);

        for ($i = 0; $i < strlen($str); $i++)
        {
            # Chars
            $x = $charShiftX + $i * $charPeriod + rand($charShiftXmin, $charShiftXmax);
            $y = $charShiftY + rand($charShiftYmin, $charShiftYmax);
            imagettftext($im, $fontSize, 0, $x, $y, -$color, $options['font-file'], $str[$i]); # -$color - Minus means switch off antialiasing. Otherwise, instead of smoothing appears black.

            # Strokes
            if ($i % 2) # Only even (reduce twice)
            {
                $x1 = $x;
                $y1 = rand(0,$height);

                $lineLength = rand($lineLengthMin, $lineLengthMax) * (rand(0,1) ? 1 : -1) ; # -1 or 1
                $angle = rand(-60,60) / 180 * M_PI; # angle from abcissa, radian

                $x2 = $x1 + $lineLength * cos($angle);
                $y2 = $y1 + $lineLength * sin($angle);

                if ($x2 >= $width)
                    $x2 = $width;
                elseif ($x2 <= 0)
                    $x2 = 0;

                imageline($im, $x1, $y1, $x2, $y2, $color);
            }

            #  Noise pixels
            for ($j=0; $j < 8; $j++)
                imagesetpixel($im, rand(0,$width), rand(0,$height), $color);
        }
        imagepng($im, $imgFile);
        imagedestroy ($im);
    }




    /** 
     * Convert a hexadecimal color (3 or 6 chars) to an RGB array
     *
     * @param string $hexStr
     * @return array
     */
    private static function hexToRgb($hexStr)
    {
        $hexStr = preg_replace("/[^0-9A-Fa-f]/u", '', $hexStr);
        $rgbArr = [];
        if (strlen($hexStr) == 6){ 
            # If a proper hex code, convert using bitwise operation. No overhead... faster
            $colorVal = hexdec($hexStr);
            $rgbArr['red'] = 0xFF & ($colorVal >> 0x10);
            $rgbArr['green'] = 0xFF & ($colorVal >> 0x8);
            $rgbArr['blue'] = 0xFF & $colorVal;}
        elseif (strlen($hexStr) == 3){ 
            # if shorthand notation, need some string manipulations
            $rgbArr['red'] = hexdec(str_repeat(substr($hexStr, 0, 1), 2));
            $rgbArr['green'] = hexdec(str_repeat(substr($hexStr, 1, 1), 2));
            $rgbArr['blue'] = hexdec(str_repeat(substr($hexStr, 2, 1), 2));}
        else
            return false;
        return $rgbArr;
    }

}

