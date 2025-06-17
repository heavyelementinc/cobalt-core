<section id="login-form-container" class="login-form-container">
    <section class="login-hero-sidebar" style="background-image: url('{{app.login-hero-sidebar}}')"></section>
    <h1>Two-Factor Authentication</h1>
    <div class="username" __custom-input="true">
        {{!user.name.tag()}}
    </div>
    <div class="hbox">
        <a href="/login/?reset">Not you?</a>
    </div>
    <p>It appears you haven't enabled Two-factor Authentication (2FA) for your 
        account. Enabling 2FA will make your account far more secure.</p>
    <p>Use the button below to enable 2FA.</p>
    <a href="/me#security" class="button" primary style="margin-top: var(--margin-m);">Enable 2FA</a>
    <a href="{{resume}}" style="margin: var(--margin-m) 0 var(--margin-xl);
    --_anchor-element-color: rgba(0 0 0 / .5);
    font-size: small;">No, thanks.</a>
</section>
