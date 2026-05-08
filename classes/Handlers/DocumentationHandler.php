<?php

/**
 * Admin Handler
 * 
 * This handler class should contain only that which is needed by the Cobalt
 * engine to handle Admin pages.
 * 
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @license https://github.com/heavyelementinc/cobalt-core/license
 * @copyright 2021 - Heavy Element, Inc.
 */

namespace Handlers;

use \Cache\Manager as CacheManager;

class DocumentationHandler extends WebHandler {
    var $route_table_cache = "js-precomp/admin-router-table.js";
    
    public string $userbar_admin_panel   = "";

    function auth_panel() {
        return "";
    }

    var $header_template = "/parts/blank.html";
    var $footer_template = "/parts/documentation-footer.html";
    var $meta_selector = "documentation";

    function getTheme() {
        if(__APP_SETTINGS__['universal_theme']) return parent::getTheme();
        return [
            "branding_increment"     => "0.05",
            "branding_rotation"      => "10",
            "color_branding"         => "#2F4858",
            "primary_increment"      => "0.1",
            "primary_rotation"       => "-10",
            "color_primary"          => "#009DDC",
            "neutral_increment"      => "0.1",
            "neutral_rotation"       => "0",
            "color_neutral"          => "#D2D6DA",
            "background_increment"   => "0.1",
            "background_rotation"    => "0",
            "color_background"       => "#F4F5F6",
            "issue_increment"        => "0.1",
            "issue_rotation"         => "-10",
            "color_issue"            => "#F96F5D",
            "color_font_body"        => "#02040F",
            "color_mixed_percentage" => 75,
        ];
    }

    function user_menu() {
        return "";
    }
}
