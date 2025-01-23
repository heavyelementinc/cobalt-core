<section id="login-form-container" class="login-form-container">
    <section class="login-hero-sidebar" style="background-image: url('{{app.login-hero-sidebar}}')"></section>
    <h1>Welcome</h1>
    {{!user.name.tag()}}
    <a href="/login/?reset">Not you?</a>
    <chip-nav>
        <nav>
            <a href="#email">Email</a>
            <a href="#login-form">Password</a>
        </nav>
        <form-request id="email" method="POST" action="/api/v1/login/" @csrf_attribute();>
            <fieldset>
                <input type="hidden" name="stay_logged_in" value="false">
                <label><input-switch name="stay_logged_in" tiny></input-switch> Stay logged in</label>
                <input type="hidden" name="email" value="true">
                <span class="error"></span>
                <button type="submit">Send Email</button>
            </fieldset>
        </form-request>
        <form-request id="login-form" action="/api/v1/login/" method="POST" complete="refresh" @csrf_attribute();>
            <fieldset>
                <input type="password" name="password" placeholder="Password">
    
                <input type="hidden" name="stay_logged_in" value="false">
                <label><input-switch name="stay_logged_in" tiny></input-switch> Stay logged in</label>
                <span class="error"></span>
                <button type="submit">Sign In</button>
                {{!create_account}}
            </fieldset>
        </form-request>
    </chip-nav>
</section>
