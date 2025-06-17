<?php
/** BODY TEMPLATE STRINGS */
const BODY_CONTENT_ENGINE_CREDIT = "Powered by Heavy Element's Cobalt Engine";
const BODY_CONTENT_NO_SCRIPT_PROMPT = "<div>&quot;{{app.app_name}}&quot; <strong>requires</strong> JavaScript. Basic functionality <strong>will not work</strong> without JavaScript enabled. Please enable JavaScript or upgrade your browser.</div><div>JavaScript allows us to deliver a better, more performant user experience. Don't believe us? Check out our <a href=\"https://heavyelement.com/news/cobalt-performance\">blog post on Cobalt application performance.</a></div>";

const AUTH_PROCESS_ERROR__INSECURE_LOGIN_DISALLOWED = "App configuration disallows logging in through insecure web contexts.";
const AUTH_PROCESS_ERROR__USER_NOT_FOUND = "Invalid credentials";
const AUTH_PROCESS_ERROR__PASSWORD_CANT_BE_BLANK = "Password cannot be blank";
const AUTH_PROCESS_ERROR__PASSWORD_HASH_FAIL = "Invalid credentials";
const AUTH_PROCESS_ERROR__TFA_CANNOT_BE_BLANK = "2FA code cannot be blank";
const AUTH_PROCESS_ERROR__TFA_VERIFY_FAILURE = "2FA code doesn't appear to be right";

const AUTH_TOTP_BACKUP_CODE_BACKUP_PROMPTS = <<<HTML
<p>Back up these recovery codes somewhere safe! If you lose access to your TOTP 
    app, you can use these codes as a way to recover access to your account.
</p>
<p><strong>You will <u>not</u> see these backup codes again!</strong></p>
%s
<small>Using a backup code will remove it from your account. If you use all
    your backup codes, 2FA will be automatically disabled.
</small>
HTML;

const AUTH_TOTP_CODE_CONSUMED_WARNING = "Warning: you've used %d backup key%s. When you use backup keys, they are consumed and cannot be used again.<br><br>To reset your backup keys, please disable and then re-enable 2FA!";