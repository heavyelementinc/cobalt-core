<?php
namespace Controllers\Landing;

use Controllers\Crudable;
use Cobalt\Pages\PageMap;
use Cobalt\Pages\PageManager;
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
        $page = $this->get_page_data($query);
        $does_not_exist = "That page does not exist.";
        if($page === null) throw new NotFound($does_not_exist, true);
        
        $visibility = (int)$page->visibility->getRaw();
        if($visibility !== $page::VISIBILITY_PUBLIC) {
            switch($visibility) {
                case $page::VISIBILITY_PRIVATE:
                    throw new NotFound($does_not_exist, true);
                    break;
                default:
                    if(!session()) throw new NotFound($does_not_exist, true);
                    break;
            }
        }
        /** @var DateTime */
        $live_date = (int)$page->live_date->getValue()->format("U");
        if($live_date > time()) throw new NotFound($does_not_exist, true);
        if($page->flags->and($page::FLAGS_REQUIRES_ACCOUNT) && !session()) throw new Unauthorized("You must be logged in to view this content");

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
        update('body', ['attribute' => ['class' => 'landing-page--body']]);
        $v = (string)$page->view;
        return view($page::VIEW_TYPE[$v]);
    }

    function splash(PageMap $page) {
        $view = "/pages/landing/views/splash-default.html";
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

        return view($view, ['page' => $page, 'class' => $classes]);
    }

    function biography(PageMap $page) {
        if(!$page->include_bio->getValue()) return "";
        if(!$page->author->getRaw()) return "";
        $avatar_classes = "";

        if($page->bio_flags->and($page::BIO_AVATAR_RADIUS_CIRCULAR)) $avatar_classes = "border-radius--circular";
        else if($page->bio_flags->and($page::BIO_AVATAR_RADIUS_ROUNDED)) $avatar_classes = "border-radius--rounded";
        return view("/pages/landing/biography.html", [
            'page' => $page,
            'cta' => $page->bio_cta->getValue() ?? $page->cta->getValue(),
            'avatar_classes' => $avatar_classes,
        ]);
    }

    function aside(PageMap $page) {
        // If there's no aside, just return nothing.
        if($page->include_aside->getValue() !== true) return "";

        $settings = $page->aside_positioning->getValue();
        $classes = "";
        if($settings & $page::ASIDE_SIDEBAR_NATURAL) $classes = "aside-config--natural";
        else if($settings & $page::ASIDE_SIDEBAR_REVERSE) $classes = "aside-config--reverse";
        else if($settings & $page::ASIDE_SIDEBAR_FOOTER) $classes = "aside-config--reverse";

        if($settings & $page::ASIDE_STICKY) $classes .= " aside-config--sticky";
        $aside = "<aside class=\"landing-main--aside $classes\"><div class=\"aside--content\">$page->aside</div></aside>";
        return $aside;
    }

    function style(PageMap $page) {
        if(!__APP_SETTINGS__['LandingPage_allow_custom_css_injection']) return "";
        $main_nav = ($page->show_main_nav->getValue() == false) ? "#app-header nav, #nav-menu-spawn {display:none}" : "";
        $style = "<style>$main_nav$page->style</style>";
        return $style;
    }

    function getRelated(PageMap $page) {
        if($page->flags->and($page::FLAGS_EXCLUDE_RELATED_PAGES)) return "";
        $related = $this->manager->getRelatedPages($page);
        $related_title = $page->related_title->getValue() ?? __APP_SETTINGS__['LandingPage_related_content_title'];
        $html = "<section class=\"landing-main--related-pages\"><h2>$related_title</h2><div class=\"landing-related--container\">";
        foreach($related as $p) {
            if($p instanceof PageMap == false) continue;
            $html .= view("/pages/landing/related.html", ['page' => $p]);
        }
        return $html . "</div></section>";
    }
}