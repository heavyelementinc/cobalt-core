<?php
use Cobalt\Auth\Users\Controllers\Login;
?>
<div id="login-form-container" class="vbox">
    <img src="<?= __APP_SETTINGS__['logo']['media']['filename'] ?>" height="<?= min(300, __APP_SETTINGS__['logo']['media']['meta']['height']) ?>" width="300" class="logo">
    <form id="login-form" method="POST" action="<?= Login::LOGIN_PATH ?><?php
    if($_GET[Login::CONTINUE_KEY]) {
        echo "?".http_build_query([Login::CONTINUE_KEY => $_GET[Login::CONTINUE_KEY]]);
    }
    ?>">
        <label>Username/email</label>
        <input name="uname" placeholder="Username or email address">
        <label>Password</label>
        <input type="password" name="pword" placeholder="Password">
        <button>Login</button>
        <div style="height:1lh"><em><?= $message ?></em></div>
    </form>
</div>

<style>
    #login-form-container {
        justify-content: center;
        padding: 1lh 0;
        .logo {
            object-fit: contain;
        }
    }
    #login-form {
        label {
            display: block;
            margin-top: 1lh;
        }
        button {
            margin-top: 1lh;
            width: 100%;
        }
    }
</style>