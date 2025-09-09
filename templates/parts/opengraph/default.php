<meta property="og:title" content="<?= $opengraph['title'] ?? $og_title ?? $title ?> | {{app.app_name}}" />
<meta property="og:type" content="<?= $opengraph['type'] ?? $og_type ?? "website" ?>" />
<meta property="og:site_name" content="<?= __APP_SETTINGS__['app_name'] ?>" />
<meta property="og:url" content="{{request.url}}" />
<meta property="og:description" content="<?= $opengraph['description'] ?? $og_description ?>" />
<meta property="og:image" content="https://{{app.domain_name}}<?= $opengraph['og_image'] ?? $opengraph['og_image_path'] ?? $og_image_path ?>" />
<meta property="og:image:width"  content="<?= $opengraph['og_image_width'] ?? $og_image_width ?>" />
<meta property="og:image:height" content="<?= $opengraph['og_image_height'] ?? $og_image_height ?>" />