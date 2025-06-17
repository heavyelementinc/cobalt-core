<?php

use MikeAlmond\Color\Color;
?>
<html>
   <head>
      <style>
         body {
            background-color: <?= __APP_SETTINGS__['color_neutral'] ?>;
            color: <?= __APP_SETTINGS__['color_font_body'] ?>;
            margin: 0;
            padding: 0;
         }
         a.button {
            background-color: <?= __APP_SETTINGS__['color_primary'] ?>;
            display: block;
            padding: 1em .6em;
            margin: 0 auto;
            width: fit-content;
            text-align: center;
            color: <?php 
               $color = Color::fromHex(__APP_SETTINGS__['color_primary']);
               echo ($color->luminosityContrast($color) > 0.5) ? "white" : "black" ?>;
            border-radius: 1.5em;
            text-decoration: none;
            font-weight: bold;
            font-size: 1.4em;
         }
         .body {
            background-color: <?= __APP_SETTINGS__['color_background'] ?>;
            width: 700px;
            width: 55ch;
            margin: auto;
         }
         header {
            text-align: center;
            background-color: <?= __APP_SETTINGS__['color_branding'] ?>;
         }
         .container {
            padding: 16px;
            padding: 1em;
         }
         img.masthead {
            max-height: 150px;
            max-width: 200px;
            object-fit: contain;
         }
      </style>
   </head>
   <body>
      <header>
         <img class="masthead" src="<?= server_name() . __APP_SETTINGS__['logo']['media']['filename'] ?>" height="<?= __APP_SETTINGS__['logo']['media']['meta']['height'] ?>" width="<?= __APP_SETTINGS__['logo']['media']['meta']['width'] ?>">
      </header>
      <div class="body">
         <h1>{{app.app_name}}</h1>
         <h2>Password Reset</h2>
         <p>Hi there,</p>
         <p>Someone at {{app.domain_name}} has initiated a password reset for the account associated with this email address.</p>
         <p>If that was you, go ahead and click the button below to reset your password.</p>
         
         <p><a href="@server_name();/login/password-reset/{{token}}" class="button">Reset Password</a></p>
         
         <small>Or copy and paste this url: <code>@server_name();/login/password-reset/{{token}}</code></small>
      </div>
   </body>
</html>
