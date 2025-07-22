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
?>
<li class="event-listing--item">
    <a href="<?=$href?>"><?= embed_image($doc->public_image) ?></a>
    <div>
        <h2><a href="<?= $href ?>"><?= $doc->public_head->value ?? $doc->headline->value ?></a></h2>
        <time><?= $doc->start_date->format($format_start) . " $separator " . $doc->end_date->format($format_end) ?></time>
        <article>
            <?= $doc->public_body->firstParagraph() ?? $doc->body->md() ?>
        </article>
    </div>
</li>