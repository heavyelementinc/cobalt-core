<section id="login-form-container" class="login-form-container">
    <section class="login-hero-sidebar" style="background-image: url('{{app.login-hero-sidebar}}')"></section>
    <h1>Two-factor Authentication (2FA)</h1>
    <span class="error">{{!message}}</span>
    <form-request id="login-form" action="/api/v1/login/" method="POST" complete="refresh" @csrf_attribute();>
        <div class="username" __custom-input="true">
            {{!user.name.tag()}}
        </div>
        <div class="hbox">
            <a href="/login/?reset">Not you?</a>
        </div>
        <label>Two-factor Auth Code <help-span value="Use your TOTP App (Google Authenticator, FreeOTP, etc) to get a one-time password and enter it here."></help-span></label>
        <input type="string" name="totp" autofocus>
        <div class="hbox">
            <help-span value="If you've lost access to your authenticator app, you can enter one of your backup codes in the box above.">I don't have my app!</help-span>
        </div>
        <button type="submit" class="button primary">Sign In</button>
        {{!create_account}}
    </form-request>
</section>
