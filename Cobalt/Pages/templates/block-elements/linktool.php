<a class="blockeditor--content blockeditor--link-preview" href="{{block.data.link}}" target="_blank">
    <?php
    if($block->data?->meta?->image?->url) {
        $src = $block->data->meta->image->url;
        $height = $block->data->meta->image->height;
        $width = $block->data->meta->image->width;
        echo <<<HTML
        <img class="blockeditor--link-thumbnail" src="$src"
            height="$height"
            width="$width"
        >
        HTML;
    }
    ?>
    <div class="vbox">
        <h1 class="blockeditor--link-title">{{!block.data.meta.title}}</h1>
        <p class="blockeditor--link-description">{{!block.data.meta.description}}</p>
        <cite class="blockeditor--site-name">{{block.data.meta.site_name}}</cite>
    </div>
</a>