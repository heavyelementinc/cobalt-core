<?php

use Components\ServiceAreas\Models\County;
use Components\ServiceAreas\Models\Town;

/**
 * @var County $county
 * @var Town $town
 * @var iterable $portfolioInRegion
 * @var int $distance
 * @var iterable $clientsInRegion
 */
$img    = $county['img'];
$dark   = $county['dark'];
$credit = $county['credit'];
if (isset($town->img->value)) {
    $img    = $town['img'];
    $dark   = $town['dark'];
    $credit = $town['credit'];
}
?>

<section id="content-splash" class="template-splash" style="color: <?= $img->meta->contrast_color->value ?>">
    <?= embed_image($img); ?>
    <div class="template-splash--content">
        <h1 class="section-title" lazy-reveal lazy-delay="100ms">{{!town.name}}, Maine</h1>
        <article class="em-callout" lazy-reveal lazy-delay="200ms">
            <?= (isset($town['blurb'])) ? from_markdown($town['blurb']) : "Located in Maine's
        $county[descriptor] $county[location] region, $town[name] is a $town[type]
        spanning $town[mi2] square miles." ?>
        </article>
    </div>
    <small class="template--credit"><?= $credit ?></small>
</section>

{{!portolioItems}}

<section id="{{town.type}}" class="template-callout template-up">
    <h2 class="section-title gradient-title">Our Services</h2>
    <div>
        <p>At {{app.app_name}}, we're proud to serve the
            <?= number_format(round(($town->pop->value / 100)) * 100) ?> residents
            of <a href="{{town.href}}">{{town.name}}, Maine</a>.
            {{distance}}
        </p>
        <p>We offer our services to {{town.name}}, other towns in {{town.county}} County,
            the greater <?= ucfirst($county->location->value) ?> Maine region, and beyond!
        </p>
    </div>
</section>

<section id="reach-out" class="template-callout neutral">
    <h1>{{app.ServiceAreas_default_cta_header}}</h1>
    <div>
        <?= from_markdown(str_replace(['%town%', '%county%', '%region%'], [$town->name->value, $town->county->value, $county->location->value], __APP_SETTINGS__['ServiceAreas_default_cta_body'])); ?>
    </div>
    <a href="{{app.ServiceAreas_default_cta_href}}" class="button">{{app.ServiceAreas_default_cta_label}}</a>
</section>

<section class="template-callout call-to-action">
    <h2>Contact Us</h2>
    <a href="tel:<?= __APP_SETTINGS__['PublicContact_phone'] ?>" class="vbox" style="text-align: center; font-size: 2em;">
        <i name="phone" style="font-size:3em"></i>
        <span><?= phone_number_format(__APP_SETTINGS__['PublicContact_phone']) ?></span>
    </a>
</section>

<style>
    .template-splash {
        position: relative;
        overflow: hidden;
        isolation: isolate;
        img {
            position: absolute;
            z-index: -1;
            inset: 0;
            object-fit: cover;
        }
        .template--credit {
            position: absolute;
            right: 0;
            bottom: 0;
        }
    }
</style>