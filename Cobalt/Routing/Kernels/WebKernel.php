<?php

namespace Cobalt\Routing\Kernels;

use Cobalt\Customization\CustomizationManager;
use Cobalt\Routing\Route;
use Cobalt\Routing\Router;
use Override;
use Throwable;

/** @package Cobalt\Routing\Kernels */
class WebKernel implements KernelInterface {
    private Router $router;

    #[Override]
    public function initialize(Router $router):void {
        $this->router = $router;
    }

    #[Override]
    public function onRouteDiscovered(Route $routeDetails): void {
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
            'context' => $routeDetails['vars'] ?? [],
            // '$main_id' => 'main-content',
            'og_template' => "/parts/opengraph/default.html",
            // 'extensions' => extensions(),
            // 'custom' => new CustomizationManager(),
            'custom' => new CustomizationManager()
        ]);
    }

    #[Override]
    public function onExecute(mixed &$routerResult):void {
        throw new \Exception('Not implemented');
    }

    #[Override]
    public function output(mixed &$routerResult): mixed {
        throw new \Exception('Not implemented');
    }

    #[Override]
    public function onThrowable(Throwable $throwable): mixed {
        return null;
    }
    
    #[Override]
    public function hasPermission(): bool {
        $session = user();
        $permissions = $this->router->getCurrentContextDetails()['permissions'];
        if((!$permissions || empty($permissions)) && __APP_SETTINGS__['Web_normally_open_pages']) return true;
        return $session->hasAnyPermission(null, $permissions);
    }

}
