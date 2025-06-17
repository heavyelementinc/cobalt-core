<section id="login-form-container" class="login-form-container">
    <section class="login-hero-sidebar" style="background-image: url('{{app.login-hero-sidebar}}')"></section>
    <h1>Authenticate</h1>
    <span class="error">{{!message}}</span>
    <chip-nav type="row">
        <nav>
            <a href="#login-form">Password</a>
            <a href="#email">Email</a>
        </nav>
        <form-request id="login-form" action="/api/v1/login/" method="POST" complete="refresh" autosave="enter" @csrf_attribute();>
            <div __custom-input="true">
                {{!user.name.tag()}}
            </div>
            <div class="hbox">
                <a href="/login/?reset">Not you?</a>
            </div>
            <input type="username" name="username" value="{{user.uname}}" readonly style="display: none">
            <input type="password" name="password" placeholder="Password" autofocus>
            <?php
            if(__APP_SETTINGS__['Auth_allow_password_reset'] && __APP_SETTINGS__['Mail_password'] && __APP_SETTINGS__['Mail_smtp_host']) {
                echo <<<HTML
                <div class="hbox"><a href="/login/password-reset/">Reset password</a></div>
                HTML;
            }
            ?>
            <input type="hidden" name="stay_logged_in" value="false">
            <label><input-switch name="stay_logged_in" tiny></input-switch> Stay logged in <help-span value="Toggling this 'on' will remember your session. Do not toggle this on when on a public PC."></help-span></label>
            <button type="submit" class="button primary">Continue</button>
            {{!create_account}}
        </form-request>
        <form-request id="email" method="POST" action="/api/v1/login/" @csrf_attribute();>
            <div class="username" __custom-input="true">
                {{!user.name.tag()}}
            </div>
            <div class="hbox">
                <a href="/login/?reset">Not you?</a>
            </div>
            <input type="hidden" name="stay_logged_in" value="false">
            <label><input-switch name="stay_logged_in" tiny></input-switch> Stay logged in <help-span value="Toggling this 'on' will remember your session. Do not toggle this on when on a public PC."></help-span></label>
            <input type="hidden" name="email" value="true">
            <button type="submit" class="button primary">Send Email</button>
        </form-request>
    </chip-nav>
</section>
