<?php
/**
 * @var array $vars
 * @var string $delete_option
 */
// $is_special_entry = in_array((string)$vars['doc']->url,[PROJECT_SHOWROOM_URL,PROJECT_STUDIO_URL]);
$newDocDisabled = $vars['new_doc_disabled'];
$newDocAutosave = $vars['autosave'];
$id = (string)$vars['doc']->_id;
$disabled = $vars['update_doc_disabled'];
$published = ($vars['doc']->published->value) ? "true" : "false";
$doc = $vars['doc'];
$tags = $doc->tags;
$tagOptions = $doc->tags->options;
?>
<hgroup style="align-items: center">
    <h1 id="name">{{title}}</h1>
    <?php if(!$newDocDisabled) {
        echo <<<HTML
        <action-menu>
            $delete_option
        </action-menu>
        HTML;
    }
    ?>
</hgroup>
<form-request method="{{method}}" action="{{action}}" <?= $newDocAutosave ?>>
    <tab-nav>
        <nav>
            <a href="#basic"><i name="pencil"></i> Details</a>
            <a href="#landing"><i name="grid-large"></i> Index Page</a>
            <a href="#project"><i name="post"></i> Project Page</a>
            <a href="#advanced"><i name="cog"></i> Advanced</a>
        </nav>
        <div id="basic">
            <ul class='list-panel'>
                <li class="hbox">
                    <div>
                        <div>
                            <label>Name</label><br>
                            <input name="name" value="{{doc.name}}" for="#name,#fb-headline-preview">
                        </div>
                        <small>
                            {{doc.url.getLabel()}}
                            {{doc.url.field()}}
                        </small>
                    </div>
                    <div>
                        {{doc.town.getLabel()}}
                        {{doc.town.field()}}
                        <div id="geo">{{doc.geo.display()}}</div>
                    </div>
                </li>
                <li class="hbox">
                    <div>
                        {{doc.published.getLabel()}}
                        {{doc.published.field()}}
                    </div>
                    <div>
                        {{doc.date.getLabel()}}
                        {{doc.date.field()}}
                    </div>
                </li>
                <li>
                    <label>Order on the landing page <help-span value="Higher numbers come first!"></help-span></label>
                    <input type="number" name="order" value="$doc->order"$newDocDisabled>
                </li>
            </ul>
        </div>
        <div id="landing">
            <ul class="list-panel">
                <li>
                    {{doc.cover_image.getLabel()}}
                    {{doc.cover_image.field()}}
                    <br>
                    <details>
                        <summary>Sizing & Position</summary>
                        <small style="display: block">Keep in mind these are merely
                        <strong>suggestions</strong> to the browser. What you see here
                        is only an approximation of what the average visitor will see.
                        Due to varying screen sizes and aspect ratios it's <strong>impossible to
                        guarantee exactly what the user will see</strong>.</small>
                        <fieldset style="width: 431px">
                            <h3>Desktop</h3>
                            {{doc.cover_placement_desktop_y.field()}}
                            <div class="cover-placement-preview desktop page-splash" style="
                            height: 189px;
                            width: 383px;
                            --_background: url('<?= get_image_url($doc->cover_image) ?>');
                            --_desktop_offset_x: <?= $doc->cover_placement_desktop_x?>%;
                            --_desktop_offset_y: <?= $doc->cover_placement_desktop_y?>%;
                            --_desktop_scale: <?= ((string)$doc->cover_scale_desktop == "100") ? "cover" : "$doc->cover_scale_desktop%" ?>;
                            "></div>
                            {{doc.cover_placement_desktop_x.field("", ['list' => 'markers'])}}
                            <label>Zoom</label>
                            {{doc.cover_scale_desktop.field("", ['list' => 'markers'])}}
                        </fieldset>
                        <fieldset style="width: 158px">
                            <h3>Mobile</h3>
                            {{doc.cover_placement_mobile_y.field("", ['list' => 'markers'])}}
                            <div class="cover-placement-preview mobile page-splash" style="
                            height: 192px;
                            width: 108px;
                            --_background: url('<?= get_image_url($doc->cover_image) ?>');
                            --_desktop_offset_x: <?= $doc->cover_placement_mobile_x?>%;
                            --_desktop_offset_y: <?= $doc->cover_placement_mobile_y?>%;
                            --_desktop_scale: <?= ((string)$doc->cover_scale_desktop == "100") ? "cover" : "$doc->cover_scale_desktop%" ?>;
                            --_mobile_offset_x: <?= $doc->cover_placement_mobile_x?>%;
                            --_mobile_offset_y: <?= $doc->cover_placement_mobile_y?>%;
                            --_mobile_scale: <?= ((string)$doc->cover_scale_mobile == "100") ? "cover" : "$doc->cover_scale_mobile%" ?>;
                            "></div>
                            {{doc.cover_placement_mobile_x.field("", ['list' => 'markers'])}}
                            <label>Zoom</label>
                            {{doc.cover_scale_mobile.field("", ['list' => 'markers'])}}
                        </fieldset>
                        <datalist id="markers">
                            <option value="50"></option>
                            <option value="100"></option>
                            <option value="150"></option>
                        </datalist>
                    </details>
                </li>
                <li>
                    {{doc.teaser.getLabel()}}
                    {{doc.teaser.field()}}
                </li>
            </ul>
        </div>
        <div id="project">
            <ul class="list-panel">
                <li>
                    {{doc.body.getLabel()}}
                    {{doc.body.field()}}
                    <!-- <label>Blurb <help-span value="Used in link previews and when the page is shared."></help-span></label>
                    <markdown-area name="blurb" for="#fb-blurb-preview">{{doc.blurb}}</markdown-area>
                    <input name="url" type="hidden" value="{{doc.url}}"> -->
                </li>
                <li>
                    {{doc.images.getLabel()}}
                    {{doc.images.field()}}
                </li>
            </ul>
        </div>
        <div id="advanced">
            <ul class="list-panel">
                <li>
                <label>Header Color</label>
                    <select name="header_color">{{!doc.header_color.options()}}</select>
                </li>
                <li>
                    <label>Darken Header Image</label>
                    <select name="darken_header">{{!doc.darken_header.options()}}</select>
                </li>
                <!-- <li>
                    {{doc.shop.getLabel()}}
                    {{doc.shop.field()}}
                </li> -->
            </ul>
        </div>
    </tab-nav>
    {{!submit_button}}
</form-request>

<input name="primary" type="hidden" value="{{doc.primary}}" id="default-image">

<style>
    details {
        display: block;
        width: 100%;
    }
    fieldset {
        display: inline-block;
        vertical-align: top;
    }
    form-request[disabled] {
        opacity: .3;
        pointer-events: none;
    }
    fieldset input[type=range] {
        opacity: .8;
        display: inline-block;
        &:hover {
            opacity: 1;
        }
        &:is([name="cover_placement_desktop_x"],[name='cover_placement_mobile_x']) {
            height: 5px;
            margin-left: 16px;
            width: calc(100% - 16px);
        }
        &:is([name='cover_placement_desktop_y'],[name='cover_placement_mobile_y']) {
            writing-mode: vertical-lr;
            appearance: slider-vertical;
            position: relative;
            left: 0;
            height: 189px;
            float: left;
            width: 10px;
            rotate: 180deg;
            &[name='cover_placement_mobile_y']{
                height: 192px;
            }
        }
    }
    .cover-placement-preview {
        display: inline-block;
    }
    label {
        width:100%;
        display: block;
        flex-grow: 1;
        margin-top: var(--margin-m);
    }
    .page-splash {
        background-image: var(--_background);
        background-size: var(--_desktop_scale, cover);
        background-position-x: var(--_desktop_offset_x, center);
        background-position-y: var(--_desktop_offset_y, center);
    }

    .page-splash.mobile {
        background-size: var(--_mobile_scale, var(--_desktop_scale, cover));
        background-position-x: var(--_mobile_offset_x, var(--_desktop_offset_x,center));
        background-position-y: var(--_mobile_offset_y, var(--_desktop_offset_y,center));
    }

</style>