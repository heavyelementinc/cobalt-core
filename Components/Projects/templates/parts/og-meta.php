<meta property="og:title" content="{{doc.name}} | {{app.app_name}}" />
<meta property="og:type" content="{{app.opengraph_type}}" />
<meta property="og:url" content="{{request.url}}" />
<meta property="og:description" content="<?= strip_tags($doc->teaser->md()) ?>" />
<meta property="og:image" content="https://{{app.domain_name}}{{doc.primary}}" />
<meta property="og:image:width" content="{{doc.image.height}}" />
<meta property="og:image:height" content="{{doc.image.height}}" />