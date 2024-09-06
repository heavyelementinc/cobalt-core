<?php

namespace Auth;

use chillerlan\QRCode\QRCode;
use Exception;
use Exceptions\HTTP\Unauthorized;
use RobThree\Auth\TwoFactorAuth;
use SensitiveParameter;

class MultiFactorManager {

    function get_multifactor_enrollment(UserPersistance $user) {

        if(!app("TwoFactorAuthentication_enabled")) return $this->get_not_supported_stub();
        if($user->tfa->enabled) return $this->get_already_enrolled_stub();

        $secret = null;
        if(isset($user->__dataset['tfa']['secret'])) $secret = $user->__dataset['tfa']['secret'];

        $tfa = new TwoFactorAuth();
        if(!$secret) {
            $secret = $tfa->createSecret();
            $crud = (new UserCRUD())->updateOne(
                ['_id' => $user['_id']],
                ['$set' => [
                    'tfa' => [
                        'enabled' => false,
                        'secret' => $secret
                        ]
                ]]
            );
            if($crud->getModifiedCount() !== 1) throw new Exception("Could not store secret for user");
        }

        $payload = $tfa->getQRText(app("domain_name"), $secret);

        return view('/authentication/otp/enroll.html', [
            'qr' => '<img width="150" src="'.(new QRCode())->render($payload).'">',
            'secret' => $secret
        ]);
    }

    function get_already_enrolled_stub() {
        return "<fieldset id='enrollment-pane'><legend>Two-Factor Authentication</legend><p>You're already enrolled in TOTP 2FA!</p><async-button link method='DELETE' action='/api/v1/me/totp/unenroll'>Remove TOTP</async-button></fieldset>";
    }

    function get_not_supported_stub() {
        return "<fieldset id='enrollment-pane'><legend>Two-Factor Authentication</legend><p>This Cobalt app has Two-Factor Authentication disabled. Please contact your system administrator to enable TOTP support</p></fieldset>";
    }

    function enroll_user(UserPersistance $user, #[SensitiveParameter] string $passwd) {
        if(!$this->verify_otp($user, $passwd)) throw new Unauthorized("OTP verification failed","There was an error validating the provided one-time password");
        $crud = new UserCRUD();
        $backups = $this->generate_backup_codes();
        $passwords = [];
        foreach($backups as $b) {
            $passwords[] = password_hash($b, PASSWORD_BCRYPT);
        }
        
        $result = $crud->updateOne(['_id' => $user->_id],[
            '$set' => [
                'tfa.enabled' => true,
                'tfa.backups' => $passwords
            ]
        ]);

        return $backups;
    }

    function verify_otp(UserPersistance $user, string $passwd) {
        $tfa = new TwoFactorAuth();
        return $tfa->verifyCode($user->__dataset['tfa']['secret'], $passwd);
    }

    function unenroll_user(UserPersistance $user) {
        $crud = new UserCRUD();

        $result = $crud->updateOne(['_id' => $user->_id],[
            '$set' => [
                'tfa.enabled' => false,
                'tfa.backups' => []
            ]
        ]);
        
        return $result->getModifiedCount();
    }

    function generate_backup_codes() {
        return [
            random_string(8, "1234567890ABCDEF-"),
            random_string(8, "1234567890ABCDEF-"),
            random_string(8, "1234567890ABCDEF-"),
            random_string(8, "1234567890ABCDEF-"),
        ];
    }
}
