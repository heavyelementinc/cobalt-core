<?php

namespace Cobalt\Routing\Kernels;

use Cobalt\Customization\CustomizationManager;
use Cobalt\Routing\Interfaces\ExecutionResult;
use Cobalt\Routing\Route;
use Cobalt\Routing\Router;
use Override;
use Throwable;

/** @package Cobalt\Routing\Kernels */
class WebKernel implements KernelInterface {
    private Router $router;
    private Route $route;

    function __construct(private bool $isApi = false){
    }

    #[Override]
    public function initialize(Router $router):void {
        $this->router = $router;
    }

    #[Override]
    public function onRouteDiscovered(Route $routeDetails): void {
        $this->route = $routeDetails;
        $_REQUEST['url'] = server_name() . $_SERVER['REQUEST_URI'];
        $_REQUEST['url'] .= ($_SERVER['QUERY_STRING']) ? "?$_SERVER[QUERY_STRING]" : "";
        $_REQUEST['referrer'] = $_SERVER['HTTP_REFERRER'];
        global $WEB_PROCESSOR_VARS;
        $WEB_PROCESSOR_VARS = array_merge($WEB_PROCESSOR_VARS ?? [], [
            'app'  => __APP_SETTINGS__,
            'get'  => &$_GET,
            'post' => &$_POST,
            'session' => session(),
            'request' => &$_REQUEST,
            'context' => $routeDetails->uri_vars ?? [],
            // '$main_id' => 'main-content',
            'og_template' => "/parts/opengraph/default.html",
            // 'extensions' => extensions(),
            // 'custom' => new CustomizationManager(),
            'custom' => new CustomizationManager()
        ]);
    }

    #[Override]
    function onExecute(ExecutionResult &$routerResult):void {
        if($this->isApi) return;
        $routerResult->setControllerResult("@style_meta@", $this->style_meta());
        $routerResult->setControllerResult("@app_settings@", $this->app_settings());
        $routerResult->setControllerResult("@user_menu@", $this->user_menu());
        $routerResult->setControllerResult("@router_table@", $this->router_table());
        $routerResult->setControllerResult("@auth_panel@", $this->auth_panel());
        $routerResult->setControllerResult("@post_header@", $this->post_header());
        $routerResult->setControllerResult("@header_content@", $this->header_content());
        $routerResult->setControllerResult("@cookie_consent@", $this->cookie_consent());
        $routerResult->setControllerResult("@footer_content@", $this->footer_content());
        $routerResult->setControllerResult("@footer_credits@", $this->footer_credits());
        $routerResult->setControllerResult("@script_content@", $this->script_content());
        $routerResult->setControllerResult("@session_panel@", $this->session_panel());
        $routerResult->setControllerResult("@notify_panel@", $this->notify_panel());
    }

    #[Override]
    public function output(ExecutionResult &$routerResult): mixed {
        if($this->isApi) return $routerResult->getControllerResult();
        return $routerResult->getBodyView();
    }

    #[Override]
    public function onThrowable(Throwable $throwable): mixed {
        return $throwable->getMessage();
    }
    
    #[Override]
    public function hasPermission(): bool {
        $session = user();
        $permissions = $this->router->getCurrentContextDetails()['permissions'];
        if((!$permissions || empty($permissions)) && __APP_SETTINGS__['Web_normally_open_pages']) return true;
        return $session->hasAnyPermission(null, $permissions);
    }

    private function style_meta():string {
        return "";
    }
    private function app_settings():string {
        // return "";
        $GLOBALS['PUBLIC_SETTINGS']['trusted_host'] = in_array($_SERVER['HTTP_HOST'], __APP_SETTINGS__['API_CORS_allowed_origins']);
        $settings = "<script id=\"app-settings\" type=\"application/json\" ".nonce().">" . json_encode($GLOBALS['PUBLIC_SETTINGS']) . "</script>";
        $settings .= "<script id='route-boundaries' type='application/json' ". nonce().">" . json_encode($this->router->getRouterBoundaries()) . "</script>";
        return $settings;
    }
    private function user_menu():string {
        return "";
    }
    private function router_table():string {
        return "";
    }
    private function auth_panel():string {
        return "";
    }
    private function post_header():string {
        return "";
    }
    private function header_content():string {
        return "";
    }
    private function cookie_consent():string {
        return "";
    }
    private function footer_content():string {
        return "";
    }
    private function footer_credits():string {
        return "";
    }
    private function script_content():string {
        return "";
    }
    private function session_panel():string {
        return "";
    }
    private function notify_panel():string {
        return "";
    }
}
