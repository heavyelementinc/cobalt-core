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
            <div class="">
                <?php
                if(isset($doc->town)) {
                    $town = $doc->town->display();
                    echo <<<HTML
                    <small><i name="map-marker"></i>$town &bull;</small>
                    HTML;
                }
                ?>
                <small>{{doc.date.format("long")}}</small>
            </div>
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

@view("Components/Projects/templates/parts/project-page-cta.php");

<style>
    [onclick^="shadowbox"] {
        cursor: pointer;
    }
    hgroup {
        display: flex;
        flex-direction: column;
    }
</style>