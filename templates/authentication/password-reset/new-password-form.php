<section id="login-form-container" class="login-form-container">
    <section class="login-hero-sidebar" style="background-image: url('{{app.login-hero-sidebar}}')"></section>
    <h1>Password Reset</h1>
    <span class="error">{{!message}}</span>
    <form-request method="PUT" action="/api/v1/password-reset/<?= (string)$this->vars['user']->get_token('password-reset') ?>">
        <label>New Password</label>
        <input-password name="password"></input-password>
        <small>Provide a new password to log in.</small>
        <button type="submit" class="button primary">Submit</button>
    </form-request>
</section>