<?php

namespace Cobalt\Captcha\Controllers;

use Cobalt\Captcha\Classes\Captcha;

class CaptchaController {
    function get_captcha() {
        $manager = new Captcha();
        $manager->get_captcha_string();
        $captchaImage = $manager->get_captcha_image();
        header('Content-Type: image/jpeg');
        header('Cache-Control: no-store');
        imagejpeg($captchaImage);
        exit;
    }
}