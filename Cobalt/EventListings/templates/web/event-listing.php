<?php
$format_compare = "Y-m-d";
$format_start = "l, F jS g:i A";
$separator = "to";
$format_end = "g:i A";
if($doc->start_date->format($format_compare) !== $doc->end_date->format($format_compare)) {
    $format_end = "F jS g:i A";
    $separator = "through";
}
$href = $doc->call_to_action_href->value;
if(!$href) $href = route("Cobalt\\EventListings\\Controllers\\Events@public_listing", [(string)$doc->_id]);
register_user_bar_items(['edit' => "<a href='".route('Cobalt\EventListings\Controllers\Events@__edit',[(string)$doc->_id])."'><i name='pencil'></i> Edit</a>"]);

?>
<div class="event-listing--content">
    <?= embed_image($doc->public_image, null, ['class' => 'event-listing--image']) ?>
    <div class="event-listing--body">
        <h2><?= $doc->public_head->value ?? $doc->headline->value ?></h2>
        <time><?= $doc->start_date->format($format_start) . " $separator " . $doc->end_date->format($format_end) ?></time>
        <article>
            <?= ($doc->public_body->length()) ? $doc->public_body->display() : $doc->body->md() ?>
        </article>
    </div>
</div>