<?php

/**
 * The WebHandler class handles all "web" context execution.
 * 
 * When the web context is found, Cobalt will instantiate this handler and start
 * building the page that we intend to display to the client.
 * 
 * First, we load the body.html parts as well as the $content_replacement items.
 * We execute content_replacement method and store the return value. Then we 
 * replace the references in body.html file with the parts we just rendered.
 * 
 * This is where we combine the disparate template pieces together to create a 
 * single, complete document for processing.
 * 
 * @author Gardiner Bryant <gardiner@heavyelement.io>
 * @license https://github.com/heavyelementinc/cobalt-core/license
 * @copyright 2021 - Heavy Element, Inc.
 */

namespace Handlers;

use \Cache\Manager as CacheManager;
use Cobalt\Notifications\PushNotifications;
use Cobalt\Renderer\Debugger;
use Cobalt\Renderer\Exceptions\TemplateException;
use Cobalt\SchemaPrototypes\Basic\HexColorResult;
use Cobalt\ThemeManager;
use Controllers\Controller;
use \Exceptions\HTTP\HTTPException;
use \Exceptions\HTTP\NotFound;
use MikeAlmond\Color\Color;
use Render\Render;

class WebHandler implements RequestHandler {
    public $template_cache_dir = "templates";
    protected $results_sent_to_client = false;
    var $meta_selector = "web";
    var $encoding_mode;
    var $renderer;
    var $_stage_bootstrap;
    protected string $mainTemplateFilename;


    /** The `body.html` is scanned for these specific tags and then the 
     * corresponding methods are called and their results are stored with the 
     * key. Then they're str_replace()'d in the body document. If you need to 
     * precompile templates into the `body.html` template, this is where you add
     * them. */
    private $content_replacement = [
        "@app_meta@"       => "",
        "@style_meta@"     => "",
        "@app_settings@"   => "",
        "@user_menu@"      => "",
        "@router_table@"   => "",
        "@auth_panel@"     => "",
        "@post_header@"    => "",
        "@header_content@" => "",
        "@cookie_consent@" => "",
        "@footer_content@" => "",
        "@footer_credits@" => "",
        "@script_content@" => "",
        "@session_panel@"  => "",
        "@notify_panel@"   => "",
    ];

    private $main_content_replacement = "@main_content@";

    private $template_body = "";
    public $template_vars = [];

    private $template_main_content = "";

    private $context_mode = null;
    private $push_handler = null;

    function __construct() {
        // $this->web_manifest = get_all_where_available([
        //     __ENV_ROOT__ . "/manifest.jsonc",
        //     __ENV_ROOT__ . "/manifest.json",
        //     __APP_ROOT__ . "/manifest.jsonc",
        //     __APP_ROOT__ . "/manifest.json"
        // ]);
        /** If we're in a web context, load the HTML body. This is so that we can
         * request just the main-content of a page via API later.
         */
        $this->encoding_mode = __APP_SETTINGS__['context_prefixes'][$GLOBALS['route_context']]['mode'];
        if ($this->encoding_mode === "text/html") {
            $this->context_mode = $GLOBALS['route_context'];
            $this->template_body = $this->load_template("parts/body.html"); // Load the main HTML template
        } else {
            $this->template_body = $this->main_content_replacement;
        }
        if(__APP_SETTINGS__['Render_use_v2_engine']) $this->renderer = new \Cobalt\Renderer\Render();
        else $this->renderer = new Render();
    }

    /** INTERFACE REQUIREMENTS */
    public function _stage_init($context_meta) {
        $this->prepare_html_framework();
        return;
    }

    public function _stage_route_discovered($route, $directives) {
        $this->renderer->stock_vars['route'] = $directives;
        $this->renderer->stock_vars['PATH'] = $GLOBALS['PATH'];
        return true;
    }

    public function _stage_execute($router_result = "") {
        $this->template_main_content = $router_result;
        // if($router_result)
        // if (!isset($GLOBALS['WEB_PROCESSOR_TEMPLATE'])) throw new NotFound("No template specified by controller");
        // if (!\template_exists($GLOBALS['WEB_PROCESSOR_TEMPLATE'])) throw new NotFound("That template doesn't exist!");
    }

    public function _stage_output($context_result) {
        if ($this->encoding_mode === "text/html") {
            $GLOBALS['allowed_to_exit_on_exception'] = false;
            // Let's make sure that we aren't double-sending the final document.
            if (!$this->results_sent_to_client) return $this->process();

            $this->results_sent_to_client = true;
        }
        return;
    }

    public function _public_exception_handler($e) {
        // Prevent trying to load a template that might not exist already.
        unset($GLOBALS['WEB_PROCESSOR_TEMPLATE']);

        // Get the message string and data
        $message = $e->name ?? "Unknown Error";

        if(method_exists($e, "publicMessage")) $message = $e->publicMessage();

        $data = $e->data;

        // Get the default template for this error type:
        $template = "errors/" . $e->status_code . ".html";
        if (gettype($data) === 'array') {
            if (key_exists('template', $data)) {
                // Check if this error has a template specified.
                $template = $data['template'];
                unset($data['template']);
            }
        }

        // If we're in debug mode, let's embed the actual error message
        $embed = "$message";
        if(__APP_SETTINGS__['debug_exceptions_publicly']) $embed .= "<pre class=\"error--message\">" . base64_encode($e->getMessage()) . "</pre>";

        $this->add_vars([
            'title' => $e->status_code,
            'message' => $message,
            'embed' => $embed,
            'status_code' => $e->status_code,
            'data' => $data,
            'body_id' => app("HTTP_error_body_id"),
            'keywords' => __APP_SETTINGS__['keywords'],
        ]);

        // Check if the template exists
        if (!\template_exists($template)) $template = "errors/default.html";

        // This will let us display an error page if we've already loaded a template
        if ($this->_stage_bootstrap['_stage_execute'] || $this->_stage_bootstrap['_stage_output']) {
            // Flush whatever body template we might have already loaded
            $this->flush_body_template();
            $this->_stage_init(null);
        }

        // Add the error template as the main content
        $this->main_content_from_template($template);
        return $this->_stage_output("");
    }

    /** END INTERFACE REQUIREMENTS */


    function no_write_on_destruct() {
        $this->results_sent_to_client = true;
    }

    // function __destruct() {
    //     // Check if we have sent the output yet and return
    //     if ($this->results_sent_to_client === true) return;
    //     // If we HAVEN'T sent the output, we run _stage_output
    //     $this->_stage_output();
    // }

    /** Here we're searching our base template for any additional template stuff we might want
     * to include. We do this because we want to provide a base HTML framework along with public
     * app settings, the client's routing table, and other stuff. 
     * */
    function prepare_html_framework() {
        /** Search through our template */
        foreach ($this->content_replacement as $search => $replace) {
            /** Remove the preceding and trailing % */
            $name = substr($search, 1, -1);
            /** Run the function by the same name and store the value with the name of the key */
            $this->content_replacement[$search] = $this->{$name}();
        }

        $this->template_body = str_replace(array_keys($this->content_replacement), array_values($this->content_replacement), $this->template_body);
    }

    function app_meta() {
        $template = $this->load_template("parts/meta.html");
        return $template;
    }


    function app_settings() {
        $GLOBALS['PUBLIC_SETTINGS']['trusted_host'] = in_array($_SERVER['HTTP_HOST'], __APP_SETTINGS__['API_CORS_allowed_origins']);
        $settings = "<script id=\"app-settings\" type=\"application/json\">" . json_encode($GLOBALS['PUBLIC_SETTINGS']) . "</script>";
        $settings .= $this->getRouteBoundaries();
        $theme = new ThemeManager(__APP_SETTINGS__['color_primary'] ?? "#004BA8", __APP_SETTINGS__['color_background'] ?? "#EFEFEF", __APP_SETTINGS__['color_mixed_percentage'] ?? 50);
        $vars = $theme->getPrimaryColor() . $theme->getBackgroundColor() . $theme->getMixedColor();
        foreach(__APP_SETTINGS__["vars"][$this->meta_selector] as $var => $value) {
            $vars .= "--project-$var: $value;\n";
        }
        foreach(__APP_SETTINGS__['fonts'] as $name => $family) {
            $vars .= "--project-$name-family: $family[family];\n";
        }
        
        $settings .= "<style id=\"style-main\">:root{\n$vars\n}</style>";
        return $settings;
    }


    function getRouteBoundaries() {
        $boundaries = [];
        foreach(__APP_SETTINGS__['context_prefixes'] as $context => $data) {
            $trailing_slash = ($data['prefix'][strlen($data['prefix'] ?? "") - 1] === "/") ? "?" : "";
            $boundaries["^".preg_quote($data['prefix'] ?? "")."$trailing_slash"] = $data['prefix'];
        }
        return "<script id='route-boundaries' type='application/json'>" . json_encode($boundaries) . "</script>";
    }

    var $header_template = "parts/header.html";

    var $header_nav_cache_name = "template-precomp/header_nav.html";
    function header_content() {
        $masthead = "";
        
        if(__APP_SETTINGS__['Web_include_app_branding']) {
            $logo = app("logo.thumb");
            $meta = $logo['meta'];
            $masthead = "<a href='/' title='Home'><img class='cobalt-masthead' src='$logo[filename]' width='$meta[width]' height='$meta[height]'></a>";
        }
        
        $header = $this->load_template($this->header_template);
        $this->add_vars([
            'header_nav' => $this->header_nav(),
            'masthead' => (app("display_masthead")) ? $masthead : "",
            'admin_masthead' => str_replace("href=", "is='real' href=", $masthead),
        ]);
        // $mutant = preg_replace("href=['\"]$route['\"]","href=\"$1\" class=\"navigation-current\"",$header);
        return $header;
    }

    function header_nav() {
        return get_route_group("main_navigation", ['withIcons' => false, 'classes' => "navigation--main"]);
        // $links = "";
        // global $ROUTER:
        // foreach ($router->routes['get'] a Saturday, April 27th 2024 9:29 PM s $regex => $route) {
        //     if (!isset($route['header_nav'])) continue;
        //     $href  = $route['header_nav']['href'] ?? $route['original_path'];
        //     $label = $route['header_nav']['label'];
        //     $attrs = $route['header_nav']['attributes'] ?? "";
        //     $links .= "<li><a href=\"$href\"$attrs>$label</a></li>";
        // }

        // return "<ul class=\"cobalt--navigation\">$links</ul>";
    }

    function auth_panel() {
        return "";
    }

    function post_header() {
        return $this->load_template("/parts/post-header.html");
    }

    function cookie_consent() {
        if (!app("Cookie_consent_prompt")) return "";
        if (isset($_COOKIE['cookie_consent'])) return "";
        return $this->load_template("/parts/cookie-consent.html");
    }

    var $footer_template = "parts/footer.html";

    function footer_content() {
        return $this->load_template($this->footer_template);
    }

    function footer_credits() {
        $credits  = '<section class="footer-credits">';
        $credits .= '<span>&copy;@date("Y"); {{app.app_copyright_name}}</span> &mdash; <span>All Rights Reserved</span>';
        if (app('Web_display_designer_credit')) $credits .= ' &mdash; <span>{{app.designer.prefix}} <a href="{{app.designer.href}}" title="{{app.designer.title}}">{{app.designer.name}}</a></span>';
        if (__APP_SETTINGS__['Web_privacy_policy']) $credits .= " &mdash; <a href='" . __APP_SETTINGS__['Web_privacy_policy'] . "'>Privacy Policy</a>";
        if (__APP_SETTINGS__['Web_terms_of_service']) $credits .= " &mdash; <a href='" . __APP_SETTINGS__['Web_terms_of_service'] . "'>Terms of Service</a>";
        $credits .= '</section>';
        $login = "Login";
        if (session()) $login = "Panel";
        if (app('Auth_logins_enabled') && !app('Auth_session_panel_enabled')) $credits .= "<a href=\"{{app.context_prefixes.admin.prefix}}\"  class=\"footer-credits\" is=\"\">Administrator $login</a>";
        return $credits;
    }

    function user_menu() {
        if (!app("Auth_user_menu_enabled")) return "";
        $list = (session_exists()) ? "session" : "no-session";
        $files = files_exist([
            __APP_ROOT__ . "/private/config/user_menu.json",
            __ENV_ROOT__ . "/config/user_menu.json"
        ]);
        $user_menu = new \Auth\UserMenu(get_json($files[0])[$list]);
        $menu = $user_menu->create_menu();
        return $menu;
    }

    var $script_cache_name = "template-precomp/script.html";

    function script_content() {
        return $this->cache_handler($this->script_cache_name, "generate_script_content");
    }

    function notify_panel() {
        if(!__APP_SETTINGS__['Notifications_system_enabled']) return "";
        return view('/cobalt/notifications/panel.html');
    }

    var $style_cache_name = "template-precomp/style.html";
    function style_meta() {
        return $this->cache_handler($this->style_cache_name, "generate_style_meta");
    }

    /**
     * 
     */
    function cache_handler($cache_name, $callable) {
        $cache = new CacheManager($cache_name);
        $script_content = "";
        // if($cache->outdated(__APP_ROOT__ . "/cache/config/settings.000.json",5)) {
        if (app('cached_content_disabled') || $GLOBALS['TIME_TO_UPDATE']) {
            $script_content = $this->{$callable}($cache_name);
            $cache->set($script_content, false);
        } else {
            try {
                $script_content = $cache->get();
            } catch (\Exception $e) {
                $GLOBALS['TIME_TO_UPDATE'] = true;
                $script_content = $this->cache_handler($cache_name, $callable);
            }
        }

        return $script_content;
    }

    var $route_table_cache = "js-precomp/router-table.js";
    function router_table() {
        global $ROUTER;
        $table_name = str_replace(".js", ".$this->context_mode.js", $this->route_table_cache);
        $cache = new CacheManager($table_name);
        $table_content = "";
        if (app('route_cache_disabled') === false || $GLOBALS['TIME_TO_UPDATE'] || !$cache->cache_exists()) {
            $table_content = $ROUTER->get_js_route_table();
            $cache->set($table_content, false);
        } else $table_content = $cache->get();

        return "<script>$table_content</script>";
    }


    function generate_script_content($script_name) {
        $script_tags = "";
        $compiled = "";
        $generate_script_content = app("Package_JS_script_content");

        if(config()['mode'] === COBALT_MODE_DEVELOPMENT) $generate_script_content = false;
        // Load packages from manifest
        foreach (app("js.$this->meta_selector") as $package) {
            if ($generate_script_content === false) {
                $script_tags .= "<script src=\"".$this->get_script_pathname_from_manifest_entry($package)."?{{app.version}}\"></script>";
            } else {
                $files = files_exist([
                    __APP_ROOT__ . "/src/$package",
                    __ENV_ROOT__ . "/src/$package"
                ]);
                $compiled .= "\n\n" . file_get_contents($files[0]);
            }
        }

        // Load JS packages from plugins.
        foreach ($GLOBALS['PACKAGES']['js'] as $public => $private) {
            if (!file_exists($private)) continue;
            if ($generate_script_content) {
                $script_tags .= "<script src='$public?{{app.version}}'></script>";
            } else {
                $compiled .= "\n\n" . file_get_contents($private);
            }
        }

        if ($script_tags === "") $script_tags = "<script src=\"/core-content/js/package.js?{{app.version}}\"></script>";

        if ($compiled !== "") {
            $minifier = new \MatthiasMullie\Minify\JS();
            $minifier->add($compiled);
            $compiled = $minifier->minify();

            $cache = new CacheManager("js-precomp/package.js");
            $cache->set($compiled, false);
        }
        return $script_tags;
    }

    function get_script_pathname_from_manifest_entry($entry) {
        // $basic_script = "/core-content/js/$entry";
        $type = gettype($entry);
        if($type === "string") return "/core-content/js/$entry";
        if($type !== "array") throw new NotFound("Type $type is not a valid manifest entry");
        if(key_exists('url', $entry)) return $entry['url'];
        throw new NotFound("Unable to handle resource $type");
    }

    function get_css_pathname_from_manifest_entry($entry) {
        $type = gettype($entry);
        if($type === "string") return "/core-content/css/$entry";
        if($type !== "array") throw new NotFound("Type $type is not a valid manifest entry");
        if(key_exists('url', $entry)) return $entry['url'];
        throw new NotFound("Unable to handle resource $type");
    }

    // function generate_script_content($script_name) {
    //     $script_tags = "";
    //     $compiled = "";
    //     foreach (app('packages') as $package) {
    //         if ($debug) {
    //             $script_tags .= "<script src=\"/core-content/js/$package?{{app.version}}\"></script>";
    //         } else {
    //             $files = files_exist([
    //                 __APP_ROOT__ . "/src/$package",
    //                 __ENV_ROOT__ . "/src/$package"
    //             ]);
    //             $compiled .= "\n\n" . file_get_contents($files[0]);
    //         }
    //     }

    //     foreach ($GLOBALS['PACKAGES']['js'] as $public => $private) {
    //         if (!file_exists($private)) continue;
    //         if ($debug) {
    //             $script_tags .= "<script src='$public?{{app.version}}'></script>";
    //         } else {
    //             $compiled .= "\n\n" . file_get_contents($private);
    //         }
    //     }

    //     if ($script_tags === "") $script_tags = "<script src=\"/core-content/js/package.js?{{app.version}}\"></script>";

    //     if ($compiled !== "") {
    //         $minifier = new \MatthiasMullie\Minify\JS();
    //         $minifier->add($compiled);
    //         $compiled = $minifier->minify();

    //         $cache = new CacheManager("js-precomp/package.js");
    //         $cache->set($compiled, false);
    //     }
    //     return $script_tags;
    // }

    function generate_style_meta() {
        $link_tags = "";
        $compiled = "";
        $package_style_content = app("Package_style_content");
        if(config()['mode'] === COBALT_MODE_DEVELOPMENT) $package_style_content = false;
        $toPackage = __APP_SETTINGS__["css"][$this->meta_selector];
        foreach ($toPackage as $package) {
            $files = files_exist([
                __APP_ROOT__ . "/shared/css/$package",
                __APP_ROOT__ . "/public/res/css/$package",
                __ENV_ROOT__ . "/shared/css/$package"
            ], false);
            if ($package_style_content === false) {
                $path = "/res/css/";
                if (strpos($files[0], "/shared/css/")) $path = "/core-content/css/";
                else if(empty($files)) throw new NotFound("That file does not exist");
                $link_tags .= "<link rel=\"stylesheet\" href=\"$path$package?{{app.version}}\">";
            } else {
                $compiled .= "\n\n/* $package */";
                $compiled .= file_get_contents($files[0]);
            }
        }

        foreach ($GLOBALS['PACKAGES']['css'] as $public => $private) {
            $file = file_exists($private);
            if (!$file) continue;
            if ($package_style_content === true) {
                $link_tags .= "<link rel=\"stylesheet\" href=\"$public?{{app.version}}\">";
            } else {
                $compiled .= "\n\n" . file_get_contents($file);
            }
        }
        if ($link_tags === "") $link_tags = "<link rel=\"stylesheet\" href=\"/core-content/css/package.css?{{app.version}}\">";

        $minify = __APP_SETTINGS__['Package_style_minify'];
        if(config()['bootstrap_mode'] === COBALT_BOOSTRAP_ALWAYS) $minify = false;
        if ($compiled !== "") {
            if($minify) {
                $minifier = new \MatthiasMullie\Minify\CSS();
                $minifier->add($compiled);
                $compiled = $minifier->minify();
            }

            $cache = new CacheManager("css-precomp/package.css");
            $cache->set($compiled, false);
        }
        return $link_tags;
    }

    function session_panel() {
        if (!app("Auth_session_panel_enabled")) return "";
        $template = "";
        if (app("Auth_account_creation_enabled")) $template = "user_panel.html";
        else $template = "user_panel_login_only.html";
        return $this->load_template("authentication/user-panel/" . $template);
    }

    function main_content_from_template($template) {

        /** Load the template in question */
        $this->template_main_content = $this->load_template($template);

        /** If the template body is empty, let's just set the template body equal
         * to the template we just loaded.*/
        if ($this->template_body === "") $this->template_body = $this->template_main_content;

        /** If not, we will replace the main_content placeholder in the body with
         * a template we just loaded. */
        else $this->template_body = str_replace($this->main_content_replacement, $this->template_main_content, $this->template_body);
    }

    /** @todo restore .session.html functionality */
    function load_template($template_name) {
        $ext = pathinfo($template_name, PATHINFO_EXTENSION);
        $session_template_name = str_replace($ext, "session.$ext", $template_name);
        global $TEMPLATE_PATHS;
        $templates = $TEMPLATE_PATHS;
        // [__APP_ROOT__ . "/private/$this->template_cache_dir/$session_template_name",
        // __ENV_ROOT__ . "/$this->template_cache_dir/$session_template_name",]


        $round_one = $template_name;
        if (session_exists()) {
            $round_one = $session_template_name;
        }
        $candidates = \find_one_file($templates, $round_one);
        if (!$candidates) $candidates = \find_one_file($templates, $template_name);

        if (!$candidates) throw new NotFound("Cannot find that file");

        $this->mainTemplateFilename = $candidates;

        return file_get_contents($candidates);
    }

    function flush_body_template() {
        $this->template_body = $this->main_content_replacement;
    }
    /** A wrapper function for main_content_from_template. This is for backwards
     * compatibilty with Ephemeral 1.0
     */
    function add_template($path) {
        $this->main_content_from_template($path);
    }

    function add_vars($vars) {
        $always_export_these_keys = ['body_id','body_class','main_id','main_class'];

        // Let's reset these every time vars are added
        $exportable = ['body_id' => '','body_class' => '','main_id' => '','main_class' => ''];

        foreach(array_merge($vars) as $var => $val) {
            if(in_array($var, $always_export_these_keys)) $exportable += correct_exported_values($vars, $var, $val);
            if($var[0] . $var[1] == "__") $exportable += correct_exported_values($vars, $var, $val);
        }
    
        export_vars($exportable);
    
        $this->template_vars = array_merge($this->template_vars, $vars);
    }

    function process() {
        if($this->template_main_content) {
            $this->template_body = str_replace($this->main_content_replacement, $this->template_main_content, $this->template_body);
        } else {
            if (isset($GLOBALS['WEB_PROCESSOR_TEMPLATE'])) $this->main_content_from_template($GLOBALS['WEB_PROCESSOR_TEMPLATE']);
        }
        try{ 
            if (isset($GLOBALS['WEB_PROCESSOR_VARS'])) $this->add_vars($GLOBALS['WEB_PROCESSOR_VARS']);
            if (!isset($GLOBALS['WEB_PROCESSOR_VARS']['main_id'])) $this->add_vars(['__main_id' => get_main_id()]);
            $this->renderer->set_body($this->template_body);
            if(method_exists($this->renderer, "setFileName")) $this->renderer->setFileName($this->mainTemplateFilename);
            $this->renderer->set_vars($this->template_vars);
            return $this->renderer->execute();
        } catch (TemplateException $e) {
            $debug = new Debugger($e);
            return $debug->render();
        }
    }
}
