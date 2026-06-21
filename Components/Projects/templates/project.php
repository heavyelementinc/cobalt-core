<?php

use Components\Projects\Models\Project;

/**
 * @var Project $doc
 * @var ?array $other_projects
 * @var array $vars
 */
?>
<!-- parallax-mode="background" -->
<div class="project-container">
    <article class="project-sidebar">
        <hgroup>
            <h1>{{doc.name}}</h1>
            <small>{{doc.date.display()}}</small>
        </hgroup>
        {{!doc.body}}
        {{!lets_chat_button}}
        <!-- <ul>
            <li><button id="share" link><i name="export-variant"></i> Share</button></li>
        </ul> -->
    </article>
    <div class="project-gallery">
        {{!gallery}}
    </div>
</div>
<?= ($other_projects) ? <<<HTML
<section class="main-section other-projects-container">
    <h2 class="section-title">Other Projects</h2>
    <div class="project-gallery" style="margin: 0 auto; gap: 3ch; place-items: center;">
        $other_projects
    </div>
</section>
HTML : ""
?>

<section class="main-section cta">
    <h2 class="section-title">Ready to Elevate Your Space?</h2>
    <article>
        <p>
            Don't put off that home refresh any longer. Whether you need an 
            exterior update, custom interior staining, or marine-grade protection, 
            {{app.app_name}} gets the job done right the first time. Reach out 
            today to secure your free assessment.
        </p>
    </article>
    <a href="tel:{{app.ContactPublic_phone}}" class="button">
        <i name="phone"></i><?= phone_number_format(__APP_SETTINGS__['PublicContact_phone']) ?>
    </a>
</section>

<style>
    [onclick^="shadowbox"] {
        cursor: pointer;
    }
    hgroup {
        display: flex;
        flex-direction: column;
    }
</style>