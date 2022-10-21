<?php

namespace Cobalt\Kernel;

use Exception;
use Render\Render;

class Web extends Request {

    function __construct($mainTagOnly = false) {
        $this->mainTagOnly = $mainTagOnly;
    }

    public function initialize(array $contex): void { }

    public function route_data(array $route): void { }

    public function output($template_path): mixed {
        $this->template_path = $template_path;
        return $this->process_template();
    }

    public function exception($exception): mixed {
        
    }

    public function settings(): array {
        return [];
    }



    private function process_template():mixed {
        $renderer = new Render();
        $renderer->set_vars($GLOBALS['WEB_PROCESSOR_VARS']);
        $renderer->set_body($this->prep_body());
        return $renderer->execute();
    }


    private $content_replacement = [
        "app_meta"       => "",
        "style_meta"     => "",
        "app_settings"   => "",
        "user_menu"      => "",
        "router_table"   => "",
        "auth_panel"     => "",
        "post_header"    => "",
        "header_content" => "",
        "cookie_consent" => "",
        "footer_content" => "",
        "footer_credits" => "",
        "script_content" => "",
        "session_panel"  => "",
    ];

    private function prep_body() {
        if($this->mainTagOnly) return "%main_content%";
        $body_template = files_exist([
            __APP_ROOT__ . "/templates/parts/body.html",
            __ENV_ROOT__ . "/templates/parts/body.html"
        ]);
        $body_content = file_get_contents($body_template[0]);
        return str_replace($body_content);
    }

    

    function app_meta() {

    }
}