<section id="login-form-container" class="login-form-container">
    <section class="login-hero-sidebar" style="background-image: url('{{app.login-hero-sidebar}}')"></section>
    <h1>Sign in to {{app.app_short_name}}</h1>
    <span class="error">{{!message}}</span>
    <form-request id="login-form" action="/api/v1/login/" method="POST" complete="refresh" @csrf_attribute(); autosave="enter">
        <label>Username/Email Address</label>
        <input type="username" name="username" placeholder="example@{{app.domain_name}}" autofocus>
        <div class="hbox">
            <help-span value="That's not a problem. You can use your email address to log in, instead!">Forgot username?</help-span>
        </div>
        <button type="submit" class="button primary">Sign In</button>
        {{!create_account}}
    </form-request>
</section>
