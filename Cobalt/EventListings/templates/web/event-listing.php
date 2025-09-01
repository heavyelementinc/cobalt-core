<?php
/** @var Cobalt\EventListings\Models\Event $doc */
$format_compare = "Y-m-d";
$format_start = "l, F jS g:i A";
$separator = "to";
$format_end = "g:i A";
if($doc->start_date->format($format_compare) !== $doc->end_date->format($format_compare)) {
    $format_end = "F jS g:i A";
    $separator = "through";
}
$href = $doc->call_to_action_href->value;
if(!$href) $href = $doc->getUrlPath();
register_user_bar_items(['edit' => "<a href='".route('Cobalt\EventListings\Controllers\Events@__edit',[(string)$doc->_id])."'><i name='pencil'></i> Edit</a>"]);
$cal = $doc->getICalEvent();
?>
<div class="event-listing--content">
    <?= embed_image($doc->public_image, null, ['class' => 'event-listing--image']) ?>
    <div class="event-listing--body">
        <hgroup>
            <h2><?= $doc->getName() ?></h2>
            <time><?= $doc->start_date->format($format_start) . " $separator " . $doc->end_date->format($format_end) ?></time>
            <?php
            if($doc->location->value) {
                $location = urlencode($doc->location->value);
                echo <<<HTML
                    <a href="https://www.google.com/maps/search/?api=1&query=$location" target="_blank" class="location"><i name="map-marker"></i><span>$doc->location</span></a>
                HTML;
            }
            ?>
        </hgroup>
        <article>
            <?= $doc->getBody() ?>
            <div class="hbox">
                <a href="<?= $cal->googleCalendarLink() ?>" target="_blank" class="calendar-button"><i name="calendar"></i> <span>Add to Google</span></a>
                <a href="<?= $cal->outlookLink() ?>" target="_blank" class="calendar-button"><i name="microsoft-outlook"></i> <span>Add to Outlook</span></a>
                <a href="<?= route("Cobalt\EventListings\Controllers\Events@iCalEvent", [$doc->_id]) ?>" target="_blank" class="calendar-button"><i name="apple"></i> <span>Add to Calendar</span></a>
            </div>
        </article>
    </div>
</div>
<style>
    .event-listing--body {
        hgroup {
            flex-direction: column;
        }
    }
    time, .location {
        display: block;
    }
    :is(hgroup, header-group, headline-group) {
        margin-bottom: .5em;
    }
    .event-listing--body {
        .hbox {
            justify-content: space-evenly;
            margin-top: var(--margin-xxl);
        }
        a.calendar-button {
            display: inline-flex;
            align-items: center;
            background: var(--branding-color-1);
            padding: var(--margin-s) var(--margin-m);
            border-radius: var(--margin-m);
            --_anchor-element-color: var(--branding-color-1-fg);
            --_anchor-hover-color: var(--branding-color-0-fg);
            --_anchor-visited-color: var(--branding-color-1-fg);
            text-decoration: none;
            &:hover {
                background: var(--branding-color-0);
            }
            i {
                font-size: 1.5em;
                margin-right: var(--margin-xs)
            }
        }
    }
</style>