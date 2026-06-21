<?php
/**
 * @var array $county
 * @var array $town
 * @var iterable $portfolioInRegion
 * @var int $distance
 * @var iterable $clientsInRegion
 */
$img = $county['img'];
$dark = $county['dark'];
$credit = $county['credit'];
if ($town['img']) {
    $img = $town['img'];
    $dark = $town['dark'];
    $credit = $town['credit'];
}
?>

<section id="content-splash" class="content-splash" style="--_background: url('<?= $img ?>');">
    <div class="content-splash--callout">
        <!-- <h3 class="section-eyebrow" lazy-reveal lazy-delay="0ms">{{!town}}</h3> -->
        <h1 class="section-title" lazy-reveal lazy-delay="100ms">{{!town.name}}, Maine</h1>
        <article class="em-callout" lazy-reveal lazy-delay="200ms">
            <?= (isset($town['blurb'])) ? from_markdown($town['blurb']) : "Located in Maine's
        $county[descriptor] $county[location] region, $town[name] is a $town[type]
        spanning $town[mi2] square miles." ?>
        </article>

        <!-- <a class="button" href="/contact/">We'll help you tell it</a> -->
    </div>
</section>

{{!portolioItems}}

<div id="{{town.type}}" class="callout">
    <h2 class="section-title gradient-title" style="margin-top: 8rem;">Our Services</h2>
    <article style="width: calc(var(--content-width) * 0.8); margin: 0 auto">
        <p>At {{app.app_name}}, we're proud to serve the
            <?= number_format(round(($town['pop'] / 100)) * 100) ?> residents
            of <a href="{{town.href}}">{{town.name}}, Maine</a>.
            {{distance}}
        </p>
        <p>We offer our services to {{town.name}}, other towns in {{town.county}} County,
            the greater <?= ucfirst($county['location']) ?> Maine region, and beyond!
        </p>
    </article>
</div>

<section id="reach-out" class="full-viewport organic-border">
    <h1>Ready to Elevate Your Brand?</h1>
    <article style="width: calc(var(--content-width) * 0.8); margin: 0 auto">
        <p>Your competitors are already leveraging video. Don't let amateur
            production hold your business back. Let's create media that does
            the selling for you.</p>
    </article>
    <a href="/contact/" class="button">Get a Custom Quote</a>
</section>

<div class="template-callout call-to-action">
    <h2>Contact Us</h2>
    <a href="tel:<?= __APP_SETTINGS__['PublicContact_phone'] ?>" class="vbox" style="text-align: center; font-size: 2em;">
        <i name="phone" style="font-size:3em"></i>
        <span><?= phone_number_format(__APP_SETTINGS__['PublicContact_phone']) ?></span>
    </a>
</div>