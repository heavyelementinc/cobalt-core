<form method="POST" action="/api/v1/login/basic<?php
if($_GET['resume']) {
    echo http_build_query(['resume' => $_GET['resume']]);
}
?>">
    <label>Username/email</label><br>
    <input name="uname" placeholder="Username or email address"><br>
    <label>Password</label><br>
    <input type="password" name="pword" placeholder="Password"><br>
    <button>Login</button>
</form>