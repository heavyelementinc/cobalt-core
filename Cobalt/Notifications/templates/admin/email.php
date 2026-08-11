<?php
use MikeAlmond\Color\Color;
$link_color = Color::fromHex("#000000")->isReadable(Color::fromHex(__APP_SETTINGS__['color_branding'])) ? "#000000" : "#FFFFFF";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>You have new {{note_count}} unread notification.</title>
</head>
<style>
    body {
        background-color: <?= __APP_SETTINGS__['color_background'] ?>;
        color: <?= __APP_SETTINGS__['color_font_body'] ?>
    }
    a {
        color: <?= __APP_SETTINGS__['color_branding'] ?>;
    }
    main {
        max-width: 60ch;
        margin: 0 auto;
    }
    .logo {
        height: auto;
    }
    ul {
        list-style: none;
    }
    li {
        background-color: <?= __APP_SETTINGS__['color_branding'] ?>;
        color: <?= $link_color ?>;
        margin: 12px;
        padding: 12px;
        border-radius: .5em;
    }
    li a {
        color: <?= $link_color ?>;
        line-height: 0.5em;
        text-decoration: none;
        display: block;
    }
    li a:hover {
        text-decoration: underline;
    }
    .notification--from {
        font-size: small;
        font-weight: bold;
    }
    action-menu {
        display: none;
    }
    .notification--foot {
        font-size: smaller;
        opacity: .5;
    }
</style>
<body>
    <main>
        <a href="<?= server_name() ?>">
            <img src="<?= server_name().__APP_SETTINGS__['logo']['media']['filename'] ?>" alt="<?= __APP_SETTINGS__['app_name'] ?> logo" class="logo" width="<?= __APP_SETTINGS__['logo']['media']['width'] ?>" height="<?= __APP_SETTINGS__['logo']['media']['height'] ?>" />
        </a>
        <h1>Hi {{user.fname}}</h1>
        <p>You have <?= plural($note_count, "", "a ") ?>new notification<?= plural($note_count) ?> on <?= __APP_SETTINGS__['app_name'] ?>!</p>
        <ul>
            {{!notes}}
        </ul>
        <small>NOTE: Do not reply to this email directly. Please visit the links above!</small>
        <small>If you no longer wish to recieve these emails, <a href="<?= server_name() ?>/me">click here to change your email settings.</a>
    </main>
</body>
</html>