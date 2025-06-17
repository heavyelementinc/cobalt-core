<?php

namespace Cobalt\Captcha\Classes;

class Captcha {
    private string $characterPool = '123456789abcdefghjklmpqrstvwxyz&';
    private string $captchaFont = __DIR__ . '/monofont.ttf';
    private string $foregroundColor = "6d87cf";
    private string $noiseColor = "6d87cf";
    private int $captchaHeight = 60;
    private int $captchaWidth = 140;
    private int $characterCount = 6;
    private int $randomDots = 50;
    private int $randomLines = 25;

    const SESSION_CAPTCHA_KEY = "___SESSION_CAPTCHA_KEY";
    const HEADER_FIELD_NAME = "X-Captcha";

    function __construct(
        $textColor = "6d87cf",
        $noiseColor = "6d87cf",
        $captchaHeight = 60,
        $captchaWidth = 140,
        $totalCharacters = 6,
        $randomDots = 50,
        $randomLines = 25
    ) {
        $this->setTextColor($textColor);
        $this->setNoiseColor($noiseColor);
        $this->setHeight($captchaHeight);
        $this->setWidth($captchaWidth);
        $this->setCharCount($totalCharacters);
        $this->setDotCount($randomDots);
        $this->setLineCount($randomLines);
    }

    public function validate($submitted_field):bool {
        $check = $submitted_field === $_SESSION[self::SESSION_CAPTCHA_KEY];
        $_SESSION[self::SESSION_CAPTCHA_KEY] = $check;
        return $check;
    }

    public function get_captcha_field():string {
        return "";
    }

    public function get_captcha_image() {
        $captchaImage = @imagecreate(
            $this->captchaWidth,
            $this->captchaHeight,
        );
        imagecolorallocate(
            $captchaImage,
            250,
            250,
            250
        );

        $this->add_noise_to_image($captchaImage);
        $this->add_text_to_image($captchaImage);

        // $datauri = "data:image/jpeg;base64,".base64_encode();
        // ;
        // imagedestroy($captchaImage);
        return $captchaImage;
    }

    public function get_captcha_string():string {
        $captcha = '';
        $character = 0;

        while($character < $this->characterCount) {
            // Pick a random character from the string of possible characters
            $index = mt_rand(0, strlen($this->characterPool) - 1);
            // Append that character to the $captcha string
            $captcha .= $this->characterPool[$index];
            $character++;
        }

        // Store it in our session
        $_SESSION[self::SESSION_CAPTCHA_KEY] = $captcha;
        
        // Return the captcha image
        return $captcha;
    }


    public function setTextColor(string $color) {
        $this->setColor($this->foregroundColor, $color);
    }

    public function setNoiseColor(string $color) {
        $this->setColor($this->noiseColor, $color);
    }

    private function setColor(&$var, string $color) {
        if($color[0] === "#") $color = substr($color, 1);
        $var = $color;
    }

    public function setHeight(int $height) {
        $this->captchaHeight = $height;
    }

    public function setWidth(int $width) {
        $this->captchaWidth = $width;
    }

    public function setCharCount(int $count) {
        $this->characterCount = $count;
    }

    public function setDotCount(int $count) {
        $this->randomDots = $count;
    }

    public function setLineCount(int $count) {
        $this->randomLines = $count;
    }

    private function add_noise_to_image(&$captchaImage):void {
        // Let's set up the noise
        $arrayNoiseColor = $this->hextorgb($this->noiseColor);
        $imageNoiseColor = imagecolorallocate(
            $captchaImage,
            $arrayNoiseColor['red'],
            $arrayNoiseColor['green'],
            $arrayNoiseColor['blue']
        );
        for($captchaDotsCount = 0; $captchaDotsCount < $this->randomDots; $captchaDotsCount++) {
            imagefilledellipse(
                $captchaImage,
                mt_rand(0,$this->captchaWidth),
                mt_rand(0,$this->captchaHeight),
                2,
                3,
                $imageNoiseColor
            );
        }
        for($captchaLinesCount = 0; $captchaLinesCount < $this->randomLines; $captchaLinesCount++) {
            imageline(
                $captchaImage,
                mt_rand(0,$this->captchaWidth),
                mt_rand(0,$this->captchaHeight),
                mt_rand(0,$this->captchaWidth),
                mt_rand(0,$this->captchaHeight),
                $imageNoiseColor
            );
        }
    }

    private function add_text_to_image(&$captchaImage):void {
        $captchaFontSize = $this->captchaHeight * 0.65;
        $arrayTextColor = $this->hextorgb($this->foregroundColor);
        $textColor = imagecolorallocate(
            $captchaImage,
            $arrayTextColor['red'],
            $arrayTextColor['green'],
            $arrayTextColor['blue']
        );

        $text_box = imagettfbbox(
            $captchaFontSize,
            0,
            $this->captchaFont,
            $_SESSION[self::SESSION_CAPTCHA_KEY]
        );
        $x = ($this->captchaWidth - $text_box[4])/2;
        $y = ($this->captchaHeight - $text_box[5])/2;
        imagettftext(
            $captchaImage,
            $captchaFontSize,
            0,
            $x,
            $y,
            $textColor,
            $this->captchaFont,
            $_SESSION[self::SESSION_CAPTCHA_KEY]
        );
    }

    private function hextorgb ($hexstring){
        $integar = hexdec($hexstring);
        return array(
            "red" => 0xFF & ($integar >> 0x10),
            "green" => 0xFF & ($integar >> 0x8),
            "blue" => 0xFF & $integar
        );
    }
}
