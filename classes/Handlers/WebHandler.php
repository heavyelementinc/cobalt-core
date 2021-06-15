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
use \Exceptions\HTTP\HTTPException;
use \Exceptions\HTTP\NotFound;

class WebHandler implements RequestHandler {
    public $template_cache_dir = "templates";
    protected $results_sent_to_client = false;

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
        "@header_content@" => "",
        "@footer_content@" => "",
        "@footer_credits@" => "",
        "@script_content@" => "",
        "@session_panel@"  => "",
    ];

    private $main_content_replacement = "@main_content@";

    private $template_body = "";
    public $template_vars = [];

    private $template_main_content = "";

    private $context_mode = null;

    function __construct() {
        /** If we're in a web context, load the HTML body. This is so that we can
         * request just the main-content of a page via API later.
         */
        if ($GLOBALS['route_context'] === "web" || $GLOBALS['route_context'] === "admin") {
            $this->context_mode = $GLOBALS['route_context'];
            $this->template_body = $this->load_template("parts/body.html"); // Load the main HTML template
        } else {
            $this->template_body = $this->main_content_replacement;
        }
        $this->renderer = new \Render\Render();
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
        if (!isset($GLOBALS['web_processor_template'])) throw new NotFound("No template specified by controller");
        if (!\template_exists($GLOBALS['web_processor_template'])) throw new NotFound("That template doesn't exist!");
    }

    public function _stage_output() {
        if ($this->context_mode === "web" || $this->context_mode === "admin") {
            $GLOBALS['allowed_to_exit_on_exception'] = false;
            // Let's make sure that we aren't double-sending the final document.
            if (!$this->results_sent_to_client) echo $this->process();

            $this->results_sent_to_client = true;
        }
        return;
    }

    public function _public_exception_handler($e) {
        // Get the message string and data
        $message = $e->getMessage();
        $data = $e->data;

        // Get the default template for this error type:
        $template = "errors/" . $e->status_code . ".html";
        if (key_exists('template', $data)) {
            // Check if this error has a template specified.
            $template = $data['template'];
            unset($data['template']);
        }

        // If we're in debug mode, let's embed the actual error message
        $embed = "";
        if (app('debug')) $embed = "<pre class=\"error--message\">Status code:\n\n$message\n\n" . \json_encode($data) . "</pre>";
        $this->add_vars([
            'title' => $e->status_code,
            'message' => $message,
            'embed' => $embed,
            'status_code' => $e->status_code,
            'data' => $data,
            'body_id' => app("HTTP_error_body_id"),
        ]);

        // Check if the template exists
        if (!\template_exists($template)) $template = "errors/default.html";

        // This will let us display an error page if we've already loaded a template
        if ($this->_stage_bootstrap['_stage_output']) {
            // Flush whatever body template we might have already loaded
            $this->flush_body_template();
            $this->_stage_init(null);
        }

        // Add the error template as the main content
        $this->main_content_from_template($template);
    }

    /** END INTERFACE REQUIREMENTS */


    function no_write_on_destruct() {
        $this->results_sent_to_client = true;
    }

    function __destruct() {
        // Check if we have sent the output yet and return
        if ($this->results_sent_to_client === true) return;
        // If we HAVEN'T sent the output, we run _stage_output
        $this->_stage_output();
    }

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
        $settings = "<script id=\"app-settings\" type=\"application/json\">" . json_encode($GLOBALS['app']->public_settings) . "</script>";
        $settings .= "<style id=\"style-main\">:root{" . $GLOBALS['app']->root_style_definition . "}</style>";
        return $settings;
    }
    var $header_nav_cache_name = "template-precomp/header_nav.html";
    function header_content() {
        $header = $this->load_template("parts/header.html");
        $this->add_vars(['header_nav' => $this->cache_handler($this->header_nav_cache_name, "header_nav")]);
        // $mutant = preg_replace("href=['\"]$route['\"]","href=\"$1\" class=\"navigation-current\"",$header);
        return $header;
    }

    function header_nav() {
        return get_route_group("main_navigation", false, "navigation--main");
        // $links = "";
        // foreach ($GLOBALS['router']->routes['get'] as $regex => $route) {
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

    function footer_content() {
        return $this->load_template("parts/footer.html");
    }

    function footer_credits() {
        $credits  = '<section class="footer-credits">';
        $credits .= '<span>&copy;@date("Y"); {{app.app_copyright_name}}</span> &mdash; <span>All Rights Reserved</span>';
        if (app('Web_display_designer_credit')) $credits .= ' &mdash; <span>{{app.designer.prefix}} <a href="{{app.designer.href}}" title="{{app.designer.title}}">{{app.designer.name}}</a></span>';
        $credits .= '</section>';
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
        if ($GLOBALS['time_to_update']) {
            $script_content = $this->{$callable}($cache_name);
            $cache->set($script_content, false);
        } else {
            try {
                $script_content = $cache->get();
            } catch (\Exception $e) {
                $GLOBALS['time_to_update'] = true;
                $script_content = $this->cache_handler($cache_name, $callable);
            }
        }

        return $script_content;
    }

    var $route_table_cache = "js-precomp/router-table.js";
    function router_table() {
        $cache = new CacheManager($this->route_table_cache);
        $table_content = "";
        if ($GLOBALS['time_to_update'] || !$cache->cache_exists()) {
            $table_content = $GLOBALS['router']->get_js_route_table();
            $cache->set($table_content, false);
        } else $table_content = $cache->get();

        return "<script>$table_content</script>";
    }

    function generate_script_content($script_name) {
        $script_tags = "";
        $compiled = "";
        $debug = app("debug");
        foreach (app('packages') as $package) {
            if ($debug) {
                $script_tags .= "<script src=\"/core-content/js/$package?{{app.version}}\"></script>";
            } else {
                $files = files_exist([
                    __APP_ROOT__ . "/private/js/$package",
                    __ENV_ROOT__ . "/js/$package"
                ]);
                $compiled .= "\n\n" . file_get_contents($files[0]);
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

    function generate_style_meta() {
        $link_tags = "";
        $compiled = "";
        $debug = app("debug");
        foreach (app('css_packages') as $package) {
            $files = files_exist([
                __APP_ROOT__ . "/public/res/css/$package",
                __ENV_ROOT__ . "/shared/css/$package"
            ]);
            if ($debug === true) {
                $path = "/res/css/";
                if (strpos($files[0], "/shared/css/")) $path = "/core-content/css/";
                $link_tags .= "<link rel=\"stylesheet\" href=\"$path$package?{{app.version}}\">";
            } else {
                $compiled .= "\n\n" . file_get_contents($files[0]);
            }
        }
        if ($link_tags === "") $link_tags = "<link rel=\"stylesheet\" href=\"/core-content/css/package.css?{{app.version}}\">";

        if ($compiled !== "") {
            $minifier = new \MatthiasMullie\Minify\CSS();
            $minifier->add($compiled);
            $compiled = $minifier->minify();

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
        $templates = [
            // __APP_ROOT__ . "/private/$this->template_cache_dir/$session_template_name",
            // __ENV_ROOT__ . "/$this->template_cache_dir/$session_template_name",
            ...$GLOBALS['TEMPLATE_PATHS']
        ];

        $round_one = $template_name;
        if (session_exists()) {
            $round_one = $session_template_name;
        }
        $candidates = \find_one_file($templates, $round_one);
        if (!$candidates) $candidates = \find_one_file($templates, $template_name);

        if (!$candidates) throw new NotFound("Cannot find that file");

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
        $this->template_vars = array_merge($this->template_vars, $vars);
    }

    function process() {
        if (isset($GLOBALS['web_processor_template'])) $this->main_content_from_template($GLOBALS['web_processor_template']);
        if (isset($GLOBALS['WEB_PROCESSOR_VARS'])) $this->add_vars($GLOBALS['WEB_PROCESSOR_VARS']);
        $this->renderer->set_body($this->template_body);
        $this->renderer->set_vars($this->template_vars);
        return $this->renderer->execute();
    }
}
