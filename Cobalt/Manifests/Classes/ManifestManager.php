<?php

namespace Cobalt\Manifests\Classes;

use Cobalt\Manifests\Enums\ValidTypes;
use \Cache\Manager as CacheManager;

class ManifestManager {
    protected array $css = [];
    protected array $js = [];
    protected array $packages = [];

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
            else $tags[$target] .= $package->get_html_tag($this->packages);
        }

        $cache_details = $this->get_cache_details($type, $context);

        if($tags['tag'] === "") {
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
                    'tag' => "<script src=\"/core-content/js/package.$context.js?{{versionHash}}\"></script>",
                    'file' => "js-precomp/package.$context.js"
                ];
            case ValidTypes::css:
            default:
                return [
                    'tag' => "<link rel=\"stylesheet\" href=\"/core-content/css/package.$context.css?{{versionHash}}\">",
                    'file' => "css-precomp/package.$context.css"
                ];
        }
    }

    function handle_js_cache($details, $context) {
        if( __APP_SETTINGS__['manifest_v2_minify_script']) {
            $minifier = new \MatthiasMullie\Minify\JS();
            $minifier->add(implode("\n", $details));
            $compiled = $minifier->minify();
        } else {
            $compiled = implode("\n", $details);
        }

        $cache = new CacheManager($context);
        $cache->set($compiled, false);
    }

    function handle_css_cache($details, $context) {
        if(__APP_SETTINGS__['manifest_v2_minify_css']) {
            $minifier = new \MatthiasMullie\Minify\CSS();
            $minifier->add(implode("\n",$details));
            $compiled = $minifier->minify();
        } else {
            $compiled = implode("\n", $details);
        }

        $cache = new CacheManager($context);
        $cache->set($compiled, false);
    }

    function process_filenames($minified_string, string $comment_start, string $comment_end) {
        return preg_replace("/[%]{3}(.*)[%]{3}/", $comment_start." $1".$comment_end, $minified_string);
    }
}