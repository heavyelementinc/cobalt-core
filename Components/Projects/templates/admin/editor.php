<?php
$is_special_entry = in_array((string)$vars['doc']->url,[PROJECT_SHOWROOM_URL,PROJECT_STUDIO_URL]);
$newDocDisabled = $vars['doc']->newDocDisabled;
$newDocAutosave = $vars['doc']->newDocAutosave;
$id = (string)$vars['doc']->_id;
$disabled = $vars['doc']->disabled;
$published = ($vars['doc']->published) ? "true" : "false";
$doc = $vars['doc'];
$tags = $doc->tags;
$tagOptions = $doc->tags->options;
?>
<hgroup style="align-items: center">
    <h1 id="name">{{title}}</h1>
    <?= ($is_special_entry) ? "" : <<<HTML
    <form-request method="POST" action="/api/v1/project/$id" $newDocAutosave $newDocDisabled
    style="margin-left:auto">
        <label><input-switch tiny name="published" checked="$published" $disabled></input-switch> Published</label>
    </form-request>
    HTML;
    ?>
    <?php if(!$newDocDisabled) {
        $action = route("\\Components\\Projects\\Controllers\\ProjectAdmin@deleteProject", [$id]);
        echo <<<HTML
        <action-menu>
            <option method="DELETE" action="$action">Delete</option>
        </action-menu>
        HTML;
    }
    ?>
</hgroup>
<div class="hbox" style="gap: 15px">
    <div>
        <form-request method="POST" action="/api/v1/project/{{doc._id}}" {{doc.newDocAutosave}}>
            <ul class='list-panel'>
                <li>
                    <label>Name</label>
                    <input name="name" value="{{doc.name}}" for="#name,#fb-headline-preview">
                </li>
                <li>
                    <label>URL pathname</label>
                    <input name="url" value="{{doc.url}}"<?= ($is_special_entry) ? " disabled='disabled'" : "" ?>>
                </li>
                <li>
                    <label>Date</label>
                    <input-datetime name="date" value="{{doc.date.attr}}"></input-datetime>
                </li>
                <li>
                    <label>Header Color</label>
                    <select name="header_color">{{!doc.header_color.options}}</select>
                </li>
                <li>
                    <label>Darken Header Image</label>
                    <select name="darken_header">{{!doc.darken_header.options}}</select>
                </li>
                <li>
                    <label>Blurb <help-span value="Used in link previews and when the page is shared."></help-span></label>
                    <markdown-area name="blurb" for="#fb-blurb-preview">{{doc.blurb}}</markdown-area>
                    <input name="url" type="hidden" value="{{doc.url}}">
                </li>
                <?= ($is_special_entry) ? "" : <<<HTML
                <li>
                    <label>Order on the <a href="">landing page</a> <help-span value="Higher numbers come first!"></help-span></label>
                    <input type="number" name="order" value="$doc->order"$newDocDisabled>
                </li>
                <li>
                    <label><input-switch tiny name="shop" checked="$doc->shop"></input-switch> Shop Item</label>
                </li>
                HTML;
                ?>
                <!-- <li>
                    <label>Project tags</label>
                    <input-array name="tags"{{!doc.newDocDisabled}}>{{!doc.tags.options}}</input-array>
                </li> -->
            </ul>
            {{!doc.newDocSubmit}}
        </form-request>
        
        <input name="primary" type="hidden" value="{{doc.primary}}" id="default-image">
        
        <form-request method="POST" action="/api/v1/project/{{doc._id}}/attach"{{doc.newDocDisabled}}>
            <ul class="list-panel">
                <li>
                    <legend>Attach images</legend>
                    <input type="file" multiple="multiple" name="attach"{{doc.newDocDisabled}}>
                    <button type="submit">Upload</button>
                </li>
            </ul>
        </form-request>
        
        <cobalt-listing sort-action="/api/v1/project/{{doc._id}}/sort"
          rename-action="/api/v1/project/{{doc._id}}/rename/{id}"
          delete-action="/api/v1/project/{{doc._id}}/attachment/{id}"
          custom-label-0="Set as default"
          custom-action-0="/api/v1/project/{{doc._id}}/default/{id}"
        >
            {{!gallery}}
        </cobalt-listing>
    </div>
    
    <div style="display:flex; flex-direction: column;">
        <h2>Link Share Preview</h2>
        @view("/Components/Projects/templates/admin/fb-preview.html");
    </div>
</div>

<style>
    form-request[disabled] {
        opacity: .3;
        pointer-events: none;
    }
    .cfs--picture-gallery img {
        height: 14em;
        width: 14em;
        object-fit: cover;
        padding: 3px;
        border: 3px solid transparent;
        box-sizing: border-box;
    }
    .cfs--picture-gallery img.default-image {
        border: 3px solid black;
    }
</style>