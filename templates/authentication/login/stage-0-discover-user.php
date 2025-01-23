<section id="login-form-container" class="login-form-container">
    <section class="login-hero-sidebar" style="background-image: url('{{app.login-hero-sidebar}}')"></section>
    <form-request id="login-form" action="/api/v1/login/" method="POST" complete="refresh" @csrf_attribute(); autosave="enter">
        <fieldset>
            <h1>Sign in to {{app.app_short_name}}</h1>
            <input type="username" name="username" placeholder="Username/Email">

            <span class="error">{{!error}}</span>
            <button type="submit">Sign In</button>
            {{!create_account}}
        </fieldset>
    </form-request>
</section>
