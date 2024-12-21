<?php

namespace Cobalt\Manifests\Classes;

use Cobalt\Manifests\Enums\ValidTypes;
use \Cache\Manager as CacheManager;

class ManifestManager {
    protected array $css = [];
    protected array $js = [];

    function get_tags(ValidTypes $type, string $context, bool $shouldCache = false) {
        $tags = [
            "tag" => "",
            "end" => "",
        ];

        $cache = [
            "tag" => "",
            "end" => "",
        ];
        $target = "tag";
        /** @var Item $package */
        foreach(__APP_SETTINGS__['app_packages'] as $package) {
            if($package->get_type() !== $type) continue;
            if(!$package->belongs_to_context($context)) continue;
            $target = "tag";
            if($package->get_append()) $target = "end";
            
            if($shouldCache) $cache[$target] .= $package->read_content();
            else $tags[$target] .= $package->get_html_tag();
        }

        $cache_details = $this->get_cache_details($type, $context);

        if($tags['tag' === ""]) {
            $tags['end'] = $cache_details['tag'];
        }
        if ($shouldCache === true) {
            switch($type) {
                case ValidTypes::js:
                    $this->handle_js_cache($cache, $cache_details['file']);
                    break;
                case ValidTypes::css:
                default:
                    $this->handle_css_cache($cache, $cache_details['file']);
                    break;
            }
        }
        return implode("\n",$tags);
    }

    function get_cache_details($type, $context) {

        switch($type) {
            case ValidTypes::js:
                return [
                    'tag' => "<script src=\"/core-content/js/v2/package.$context.js?{{versionHash}}\"></script>",
                    'file' => "js-precomp/v2/package.$context.js"
                ];
            case ValidTypes::css:
            default:
                return [
                    'tag' => "<link rel=\"stylesheet\" href=\"/core-content/css/v2/package.$context.js?{{versionHash}}\">",
                    'file' => "css-precomp/v2/package.$context.js"
                ];
        }
    }

    function handle_js_cache($details, $context) {
        $minifier = new \MatthiasMullie\Minify\JS();
        $minifier->add(implode("\n", $details));
        $compiled = $minifier->minify();

        $cache = new CacheManager($context);
        $cache->set($compiled, false);
    }

    function handle_css_cache($details, $context) {
        $minifier = new \MatthiasMullie\Minify\CSS();
        $minifier->add(implode("\n",$details));
        $compiled = $minifier->minify();

        $cache = new CacheManager($context);
        $cache->set($compiled, false);
    }
}