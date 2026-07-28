<?php

namespace Cobalt\Auth\Users\MultiFactorSchemes;

use chillerlan\QRCode\QRCode;
use Cobalt\Auth\Users\Models\User;
use Exception;
use Exceptions\HTTP\Unauthorized;
use RobThree\Auth\TwoFactorAuth;
use SensitiveParameter;

class TOTPManager {
    const TOTP_MIN_BACKUPS = 4;
    function get_totp_multifactor_enrollment(User $user) {

        if(!app("TwoFactorAuthentication_enabled")) return $this->get_totp_not_supported_stub();
        if($user->tfa->enabled->value) return $this->get_totp_already_enrolled_stub();

        $secret = null;
        if(isset($user->__dataset['tfa']['totp']['secret'])) $secret = $user->__dataset['tfa']['totp']['secret'];

        $tfa = new TwoFactorAuth();
        if(!$secret) {
            $secret = $tfa->createSecret();
            $crud = $user->updateOne(
                ['_id' => $user['_id']],
                ['$set' => [
                    'tfa.totp' => [
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

    function get_totp_already_enrolled_stub() {
        $backup_warning = "";
        $backup_count = count(session()->__dataset['tfa']['totp']['backups']);
        $diff = self::TOTP_MIN_BACKUPS - $backup_count;
        if($backup_count < self::TOTP_MIN_BACKUPS) {
            $backup_warning = "<p style='background: var(--issue-color-1);color:var(--issue-color-1-fg);max-width:45ch;display: block; margin-bottom: var(--margin-m);padding: var(--margin-m);font-size: small'>".sprintf(AUTH_TOTP_CODE_CONSUMED_WARNING, $diff, plural($diff))."</p>";
        }
        return "<fieldset id='enrollment-pane'><legend>Two-Factor Authentication</legend><p>You're enrolled in TOTP 2FA!</p><async-button link method='DELETE' action='/api/v1/me/totp/unenroll'>Remove TOTP</async-button>$backup_warning</fieldset>";
    }

    function get_totp_not_supported_stub() {
        return "<fieldset id='enrollment-pane'><legend>Two-Factor Authentication</legend><p>This Cobalt app has Two-Factor Authentication disabled. Please contact your system administrator to enable TOTP support</p></fieldset>";
    }

    function totp_enroll_user(User $user, #[SensitiveParameter] string $passwd) {
        if(!$this->totp_verify_otp($user, $passwd)) throw new Unauthorized("OTP verification failed","There was an error validating the provided one-time password");
        $crud = $user;
        $backups = $this->totp_generate_backup_codes();
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

    function totp_verify_otp(User $user, string $passwd) {
        $tfa = new TwoFactorAuth();
        return $tfa->verifyCode($user->__dataset['tfa']['totp']['secret'], $passwd);
    }

    function totp_verify_backup_code(User $user, string $backup) {
        foreach($user->__dataset['tfa']['totp']['backups'] as $index => $hash) {
            if(password_verify($backup, $hash)) {
                $crud = $user;
                $crud->updateOne(['_id' => $user->_id], ['$pull' => ['tfa.backups' => $hash]]);
                if(count($user->__dataset['tfa']['totp']['backups']) === 1) {
                    $this->totp_unenroll_user($user);
                    redirect("/login/?reset&message=backups_exhausted");
                    return false;
                }
                return true;
            }
        }
        return false;
    }

    function totp_unenroll_user(User $user) {
        $crud = $user;

        $result = $crud->updateOne(['_id' => $user->_id],[
            '$set' => [
                'tfa.enabled' => false,
                'tfa.backups' => []
            ]
        ]);
        
        return $result->getModifiedCount();
    }

    function totp_generate_backup_codes() {
        $codes = [];
        for($i = 1; $i <= self::TOTP_MIN_BACKUPS; $i++) {
            $codes[] = $this->totp_generate_backup_code();
        }
        return $codes;
    }
    
    function totp_generate_backup_code() {
        return random_string(8, "0123456789ABCDEFGHJKLMNPRSTUVWXYZ");
    }
}
