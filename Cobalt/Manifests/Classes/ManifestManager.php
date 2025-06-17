<?php

namespace Cobalt\Manifests\Classes;

use Cobalt\Manifests\Enums\ValidTypes;
use \Cache\Manager as CacheManager;
use MatthiasMullie\Minify\CSS;
use MatthiasMullie\Minify\JS;
use MatthiasMullie\Minify\Minify;

class ManifestManager {
    protected array $css = [];
    protected array $js = [];
    protected array $packages = [];
    const JS_TAGS_CACHE_NAME = "js-precomp/javascript-tags.";
    const CSS_TAGS_CACHE_NAME = "css-precomp/css-tags.";

    function get_tags(ValidTypes $type, string $context, bool $shouldCache = false, $shouldUpdate = false) {
        switch($type) {
            case ValidTypes::js:
                $file = self::JS_TAGS_CACHE_NAME."$context.html";
                break;
            case ValidTypes::css:
                $file = self::CSS_TAGS_CACHE_NAME."$context.html";
                break;
        }
        $cacheMan = new CacheManager($file);
        if($shouldUpdate === false && config()['boostrap_mode'] !== COBALT_BOOSTRAP_ALWAYS && config('mode') === COBALT_MODE_PRODUCTION) {
            if($cacheMan->cache_exists() === true) return $cacheMan->get();
        }
        
        // Let's establish our tags
        $tags = [
            "tag" => "",
            "end" => "",
        ];

        // And our cached tags
        $cache = [
            "tag" => [],
            "end" => [],
        ];
        $target = "tag";
        /** @var Item $package */
        foreach(__APP_SETTINGS__['app_packages'] as $package) {
            // Skip package types we're not currently processing
            if($package->get_type() !== $type) continue;
            // Skip packages that don't belong to the current context
            if(!$package->belongs_to_context($context)) continue;
            // Establish our tags
            $target = "tag";
            
            // Appended packages will be appended to the end of the list of tags
            if($package->get_append()) $target = "end";
            
            if($shouldCache) $this->set_cache_package($package, $cache[$target], $type, $context);
            else $tags[$target] .= $package->get_html_tag($this->packages);
        }

        // // $cache_details = $this->get_html_tag_details($type, $context, $cache);

        // if($tags['tag'] === "") {
        //     $tags['end'] = $cache_details['tag'];
        // }
        if ($shouldCache === true) {
            switch($type) {
                case ValidTypes::js:
                    $rendered_tags = $this->handle_js_cache($cache, $context);
                    break;
                case ValidTypes::css:
                default:
                    $rendered_tags = $this->handle_css_cache($cache, $context);
                    break;
            }
            return $rendered_tags;
        }
        return implode("\n",$tags);
    }
    
    /** This function takes a package reference and concats it into its relevant 
     * COMPILED PACKAGE.
     */
    private function set_cache_package(Item $package, &$cache, $type, $context) {
        // Let's find our tag name and determine our extension.
        $package_name = $package->get_package();
        $ext = ($type === ValidTypes::js) ? "js" : "css";
        $key = "$package_name.$context.$ext";

        // If the package doesn't exist, create an empty cache descriptor
        if(!key_exists($key, $cache)) $cache[$key] = [
            'meta' => [],
            'content' => "",
            // If the name of the package is 'inline' then we should always inline the content
            'inline' => ($package_name === "inline") ? true : $package->is_inline_content(),
            // If the name of the package is 'deferred' then we should always defer the content
            'deferred' => ($package_name === "deferred") ? true : $package->is_deferred_content()
        ];
        // Let's load and concat the values of our package.
        $cache[$key]['content'] .= "\n\n/** =======================\nPACKAGE: $package_name\n======================= */\n" . $package->read_content();
        $cache[$key]['meta'] = array_merge($cache[$key]['meta'] ?? [], $package->get_package_meta() ?? []);
    }

    function handle_js_cache($details, $context) {
        $tag_generator = function (string &$html_tags, string $package_name, array $package_details, string $compiled) {
            if(!$package_details['inline']) {
                $html_tags .= "<script src=\"".to_base_url("/core-content/js/$package_name?{{versionHash}}")."\" ".implode("\n",$package_details['meta'])."></script>";
                return true;
            }
            $html_tags .= "<script class=\"inline-js\" ".implode("\n",$package_details['meta']).">$compiled</script>";
            return false;
        };
        
        return $this->minify_and_cache($details, self::JS_TAGS_CACHE_NAME, "js-precomp/", new JS, $tag_generator, __APP_SETTINGS__['manifest_v2_minify_script'], $context);
    }

    function handle_css_cache($details, $context) {
        $tag_generator = function (string &$html_tags, string $package_name, array $package_details, string $compiled) {
            if(!$package_details['inline']) {
                $html_tags .= "<link rel=\"stylesheet\" href=\"".to_base_url("/core-content/css/$package_name?{{versionHash}}")."\">";
                return true;
            }
            $html_tags .= "<style class=\"inline-css\">$compiled</style>";
            return false;
        };
        
        return $this->minify_and_cache($details, self::CSS_TAGS_CACHE_NAME, "css-precomp/", new CSS, $tag_generator, __APP_SETTINGS__['manifest_v2_minify_css'], $context);
    }

    function minify_and_cache($details, string $tag_cache_path, string $package_cache_path, Minify $minifier, callable $tag_generator, bool $minify, $context) {
        // Establish our tags
        $html_tags = "";
        // foreach($details as $location => $packages) {
        foreach($details['tag'] as $package_name => $package_details) {
            $content = $package_details['content'];
            if(key_exists($package_name, $details['end'])) $content .= "\n\n".$details['end'][$package_name]['content'];
            // Minify our package content as needed
            if($minify) {
                $minifier = new $minifier;
                $minifier->add($content);
                $compiled = $minifier->minify();
            } else {
                $compiled = $content;
            }
            // $tag_generator must return a true or false. If true, the file will 
            // compiled as a minified package, otherwise it won't!
            // $tag_generator will create and concat relevant html tags for each package
            $compile_package = $tag_generator($html_tags, $package_name, $package_details, $compiled);
            if($compile_package) {
                $cache = new CacheManager($package_cache_path.$package_name);
                $cache->set($compiled, false);
            }
        }
        // }
        $htmlCache = new CacheManager($tag_cache_path . "$context.html");
        $htmlCache->set($html_tags, false);
        return $html_tags;
    }

    function process_filenames($minified_string, string $comment_start, string $comment_end) {
        return preg_replace("/[%]{3}(.*)[%]{3}/", $comment_start." $1".$comment_end, $minified_string);
    }
}