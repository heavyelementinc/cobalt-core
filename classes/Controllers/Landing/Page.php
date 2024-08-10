<?php
namespace Controllers\Landing;

use Controllers\Crudable;
use Cobalt\Pages\PageMap;
use Cobalt\Pages\PageManager;
use Cobalt\SchemaPrototypes\Basic\BlockResult;
use DateTime;
use Drivers\Database;
use Exceptions\HTTP\NotFound;
use Exceptions\HTTP\Unauthorized;

abstract class Page extends Crudable {
    var string $landing_content_classes = "";
    /** @var PageManager */
    public Database $manager;
    
    function get_page_data($query):?PageMap {
        $result = $this->manager->findOne(['url_slug' => $query]);
        if(!$result) return null;
        return $result;
    }

    function page($query = null) {
        // Let's get our page data
        $page = $this->get_page_data($query);
        $does_not_exist = "That page does not exist.";
        // If there's no result, then we know it's not found.
        if($page === null) throw new NotFound($does_not_exist, true);
        
        // Check the page's visibility criteria
        $visibility = (int)$page->visibility->getRaw();
        
        if($visibility !== $page::VISIBILITY_PUBLIC) {
            $pkey = (string)$page->preview_key;
            switch($visibility) {
                case(isset($_GET['pkey']) && $pkey && $_GET['pkey'] === $pkey):
                    // Do nothing
                    break;
                case $page::VISIBILITY_PRIVATE:
                    throw new NotFound($does_not_exist, true);
                    break;
                default:
                    // If the page is set to draft, check if the user is logged in
                    if(!session()) throw new NotFound($does_not_exist, true);
                    break;
            }
        }

        /** @var DateTime */
        $live_date = (int)$page->live_date->getValue()->format("U");
        
        // If the current time is less than the live date, then the page doesn't exist!
        if($live_date > time()) throw new NotFound($does_not_exist, true);

        // One more check to see if this page requires an account, throw an Unauthorized so they are prompted to log in
        if($page->flags->and($page::FLAGS_REQUIRES_ACCOUNT) && !session()) throw new Unauthorized("You must be logged in to view this content");

        // Set up our (messy) variable table
        add_vars([
            'title' => $page['title'],
            'og_template' => '/pages/landing/opengraph.html',
            'description' => strip_tags(str_replace(["&#039;","&amp;#039;","\""],["'", "'", "'"], $page->summary ?? $page->body->firstParagraph())),
            'og' => [
                'title' => $page['title'],
                'image' => $page->splash_image->filename(),
                'image_x' => $page->splash_image->width(),
                'image_y' => $page->splash_image->height(),
            ],
            'splash' => $this->splash($page),
            'aside' => $this->aside($page),
            'biography' => $this->biography($page),
            'style' => $this->style($page),
            'page' => $page,
            'main_id' => 'landing-page--main',
            'body_class' => 'landing-page--body',
            'classes' => $this->landing_content_classes,
            'keywords' => $page->tags->join(", "),
            'related' => $this->getRelated($page),
        ]);

        // Get our view and check if it's in the view types
        $v = (string)$page->view;
        return view($page::VIEW_TYPE[$v]);
    }

    function splash(PageMap $page) {
        // Let's get our splash view
        $view = "/pages/landing/views/splash-default.html";
        
        // Set our classes so it appears properlty
        $classes = "";
        switch($page->splash_type->getValue()) {
            case $page::SPLASH_POSITION_SPLIT:
                $classes .= " landing-splash--type-split";
                break;
            case $page::SPLASH_POSITION_FLOAT:
                $classes .= " landing-splash--type-float";
                break;
            case $page::SPLASH_POSITION_CENTER:
                $classes .= " landing-splash--type-centered";
            case $page::SPLASH_POSITION_FADE:
            default:
                $classes .= " landing-splash--type-fade";
                break;
        }

        // And render it
        return view($view, ['page' => $page, 'class' => $classes]);
    }

    function biography(PageMap $page) {
        // If let's filter out any irrelevant stuff
        if(!$page->include_bio->getValue()) return "";
        if(!$page->author->getRaw()) return "";
        if(!$page->bio->getRaw()) return "";
        
        // Let's determine how our avatar should look.
        $avatar_classes = "";
        if($page->bio_flags->and($page::BIO_AVATAR_RADIUS_CIRCULAR)) $avatar_classes = "border-radius--circular";
        else if($page->bio_flags->and($page::BIO_AVATAR_RADIUS_ROUNDED)) $avatar_classes = "border-radius--rounded";
        
        // Finally, let's render out our biography section
        return view("/pages/landing/biography.html", [
            'page' => $page,
            'headline' => ($page->bio_headline->getValue()) ? $page->bio_headline->getValue() : __APP_SETTINGS__['LandingPage_bio_default_headline'],
            'cta' => ($page->bio_cta->getValue()) ? $page->bio_cta->getValue() : $page->cta->getValue(),
            'avatar_classes' => $avatar_classes,
        ]);
    }

    function aside(PageMap $page) {
        // If this page doesn't want a sidebar, just return nothing.
        if($page->include_aside->getValue() !== true) return "";

        // Let's see how we want our aside to position itself
        $settings = $page->aside_positioning->getValue();
        $classes = "";
        if($settings & $page::ASIDE_SIDEBAR_NATURAL) $classes = "aside-config--natural";
        else if($settings & $page::ASIDE_SIDEBAR_REVERSE) $classes = "aside-config--reverse";
        else if($settings & $page::ASIDE_SIDEBAR_FOOTER) $classes = "aside-config--reverse";

        // And another check for stickiness
        if($settings & $page::ASIDE_STICKY) $classes .= " aside-config--sticky";

        // Let's get our Table of Contents
        $headlines = "";
        if($settings & $page::ASIDE_INCLUDE_TOC_INDEX) $this->generate_headline_index($page->body, $headlines);

        // Decide how it's positioned.
        $aside = "<aside class=\"landing-main--aside $classes\"><div class=\"aside--content\">";
        if($settings & $page::ASIDE_INDEX_BEFORE_CONTENT) $aside .= "$headlines"."$page->aside";
        else $aside .= "$page->aside"."$headlines";

        // Return our rendered sidebar
        return $aside . "</div></aside>";
    }

    /**
     * 
     * @param BlockResult $body 
     * @param string &$html 
     * @return void
     */
    function generate_headline_index(BlockResult $body, string &$html) {
        $html = "<h2 class=\"aside--table-of-contents\">" . __APP_SETTINGS__['LandingPage_table_of_contents_label'] . "</h2>" . $body->tableOfContents();
    }

    function style(PageMap $page) {
        // If the app disallows custom css injection, return an empty string
        if(!__APP_SETTINGS__['LandingPage_allow_custom_css_injection']) return "";
        // Check if this page allows us to show the app navigation, if not, add this CSS to the page
        $main_nav = ($page->show_main_nav->getValue() == false) ? "#app-header nav, #nav-menu-spawn {display:none}" : "";
        $style = "<style>$main_nav"."$page->style</style>";
        return $style;
    }

    function getRelated(PageMap $page) {
        if($page->flags->and($page::FLAGS_EXCLUDE_RELATED_PAGES)) return "";
        $related = $this->manager->getRelatedPages($page);
        $related_title = ($page->related_title->getValue()) ? $page->related_title->getValue() : __APP_SETTINGS__['LandingPage_related_content_title'];
        $html = "<section class=\"landing-main--related-pages\"><h2>$related_title</h2><div class=\"landing-related--container\">";
        foreach($related as $p) {
            if($p instanceof PageMap == false) continue;
            $html .= view("/pages/landing/related.html", ['page' => $p]);
        }
        return $html . "</div></section>";
    }
}