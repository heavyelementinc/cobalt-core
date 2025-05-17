<!doctype html>
<html lang="en-US" class="{{context.html_class}} {{app.html_tag_classes}}">
<script>
    // Some user agents don't support (or don't enable) JavaScript. Therefore we
    // should keep track of any content that would be hidden because of JS and
    // style around that issue.
    document.getElementsByTagName("html")[0].classList.add("js");
    if(matchMedia("prefers-reduced-motion").matches == false) {
        document.getElementsByTagName("html")[0].classList.add("_parallax");
    }
</script>
<head>
    <meta charset="utf-8">
    <title data-suffix=" | {{app.app_name}}">{{title}} | {{app.app_name}}</title>
    <meta name="description" content="{{app.opengraph_description}}">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="version" content='{{versionHash}}'>
    <meta name="theme-color" content="{{app.color_branding}}">
    <meta name="mitigation" content="@csrf_get_token();">
    <meta name="engine" content="<?= sprintf(BODY_CONTENT_ENGINE_CREDIT) ?>" href="https://heavyelement.com/">
    <meta name="cobalt-base-path" content="<?= to_base_url("/") ?>">
    {{!ai_scraping}}

    @maybe_with("$og_template");
    {{!webmention}}
    @fonts_tag();
    <link href='<?=to_base_url("/core-content/css/material-design/css/material.min.css")?>?{{app.verion}}' rel="stylesheet">
    <?php
        use Cobalt\EventListings\Models\Event;

        if(__APP_SETTINGS__['Posts_enable_rss_feed']) {
            $server_name = server_name();
            // This is a bit of a hack so that we can include the link to our rss feed
            // We need wait for the environment to fully load before we call nullable_route
            // That's why we're embedding the call in the template.
            $rt = "@nullable_route(\"\\\Cobalt\\\Pages\\\Controllers\\\Posts@rss_feed\");";
            echo ($rt) ? "<link href=\"$server_name$rt\" type=\"application/atom+xml\" rel=\"alternate\" title=\"{{app.Posts_rss_feed_name}}\" />" : '';
        }
    ?>
    <!-- <script src="https://unpkg.com/ionicons@5.4.0/dist/ionicons.js"></script> -->
    @style_meta@

    @app_settings@

    @app_meta@
    {{!html_head_binding}}
    @router_table@
    <link rel="apple-touch-icon" href="{{app.logo.media.filename}}?{{versionHash}}">
    <script>
        window.__ = JSON.parse(atob('@get_exportables_as_json(true);'));
    </script>
    <?= (__APP_SETTINGS__['CobaltEvents_enabled']) ? "<script id=\"cobalt-events\" type=\"application/json\">" . json_encode((new Event())->getPublicListing()) . "</script>" : "<script id=\"cobalt-events\" type=\"application/json\">null</script>" ?>
</head>

<body id="{{body_id}}" class="{{body_class}}">
    <a id="sr-skip-to-content" href="#{{main_id}}" class="sr-only">Skip to content</a>
    <div id="sr-announce" class="sr-only" aria-live="assertive"></div>
    <noscript>
        <?= sprintf(BODY_CONTENT_NO_SCRIPT_PROMPT) ?>
        {{!noscript_binding_after}}
    </noscript>
    <button id="nav-menu-spawn" aria-pressed="false"
        aria-controls="app-header" aria-expanded="false"
    >
        <span class="visually-hidden">Menu</span>
        <i name="menu"></i>
    </button>
    @user_menu@
    <header id="app-header">
        {{!header_binding_before}}
        @header_content@
        {{!header_binding_middle}}
        @session_panel@
        {{!header_binding_after}}
    </header>
    @post_header@
    @auth_panel@
    <main id="{{main_id}}">
        {{!main_content_binding_before}}
        @main_content@
        {{!main_content_binding_after}}
    </main>
    {{!post_main_content}}
    <footer>
        {{!footer_binding_before}}
        @footer_content@
        {{!footer_binding_after}}
        @footer_credits@
    </footer>
    @notify_panel@
    @cookie_consent@
    <script>
        window.asyncScripts = [];
    </script>
    <script onload="window.asyncScripts.push(new Promise(resolve=>resolve(this)))" defer src="https://cdn.jsdelivr.net/npm/@editorjs/editorjs@2.30.2/dist/editorjs.umd.min.js"></script>
    <script onload="window.asyncScripts.push(new Promise(resolve=>resolve(this)))" defer src="https://cdn.jsdelivr.net/npm/@editorjs/header@latest"></script>
    <script onload="window.asyncScripts.push(new Promise(resolve=>resolve(this)))" defer src="https://cdn.jsdelivr.net/npm/@editorjs/link@latest"></script>
    <script onload="window.asyncScripts.push(new Promise(resolve=>resolve(this)))" defer src="https://cdn.jsdelivr.net/npm/@editorjs/quote@2.6.0/dist/quote.umd.min.js"></script>
    <script onload="window.asyncScripts.push(new Promise(resolve=>resolve(this)))" defer src="https://cdn.jsdelivr.net/npm/@editorjs/raw@latest"></script>
    <script onload="window.asyncScripts.push(new Promise(resolve=>resolve(this)))" defer src="https://cdn.jsdelivr.net/npm/@editorjs/image@latest"></script>
    <script onload="window.asyncScripts.push(new Promise(resolve=>resolve(this)))" defer src="https://cdn.jsdelivr.net/npm/@editorjs/nested-list@latest"></script>
    <script onload="window.asyncScripts.push(new Promise(resolve=>resolve(this)))" defer src="https://cdn.jsdelivr.net/npm/@editorjs/embed@latest"></script>
    <script onload="window.asyncScripts.push(new Promise(resolve=>resolve(this)))" defer src="https://cdn.jsdelivr.net/npm/@editorjs/inline-code@latest"></script>
    <script onload="window.asyncScripts.push(new Promise(resolve=>resolve(this)))" defer src="https://cdn.jsdelivr.net/npm/@editorjs/table@latest"></script>
    <script onload="window.asyncScripts.push(new Promise(resolve=>resolve(this)))" defer src="https://cdn.jsdelivr.net/npm/@editorjs/marker@latest"></script>
    <script onload="window.asyncScripts.push(new Promise(resolve=>resolve(this)))" defer src="https://cdn.jsdelivr.net/npm/@editorjs/code@latest"></script>
    <script onload="window.asyncScripts.push(new Promise(resolve=>resolve(this)))" defer src='@to_base_url("/core-content/js/editorjs/simpleimage.js");'></script>
    <!-- <script onload="window.asyncScripts.push(new Promise(resolve=>resolve(this)))" src=""></script> -->

    @script_content@
    <!-- <script src="/core-content/js/moduleshim.js?v={{versionHash}}" type="module"></script> -->
</body>
</html>
