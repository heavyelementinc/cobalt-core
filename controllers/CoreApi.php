<?php

use Cobalt\Notifications\Notification1_0Schema;
use Cobalt\Payments\PaymentGateway;
use Contact\ContactManager;
use \Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\NotFound;
use \Exceptions\HTTP\ServiceUnavailable;
use \Handlers\WebHandler;
use \Routes\Router;
use \Mail\SendMail;

class CoreApi extends \Controllers\Pages {

    function page() {
        // Housekeeping
        $GLOBALS['write_to_buffer_handled'] = true;
        $route = $_GET['route'];
        unset($_GET['route']);
        $method = "get";

        // Get our route
        $route_context = Routes\Route::get_router_context($route);
        if($route_context === "apiv1") throw new BadRequest("Too much recursion.");

        // Set up context processor
        $contextProcessor = app("context_prefixes")[$route_context]['processor'];
        $processor = new $contextProcessor();
        $processor->no_write_on_destruct();

        // Initialize the processor
        $processor->_stage_init(app("context_prefixes")[$route_context]);
        $processor->_stage_bootstrap['_stage_init'] = true;

        // Get the current route
        $current_route_meta = $GLOBALS['router']->discover_route($route, null, $method, $route_context);
        if($current_route_meta === null) throw new NotFound("Route not found");

        // Set up route meta in the processor
        $processor->_stage_route_discovered(...$current_route_meta);
        $processor->_stage_bootstrap['_stage_route_discovered'] = true;

        // Allow
        $router_result = $GLOBALS['router']->execute_route($current_route_meta[0], $method, $route_context);
        $processor->_stage_execute($router_result);
        $processor->_stage_bootstrap['_stage_execute'] = true;
        $processor->_stage_bootstrap['_stage_output'] = true;
        $body = $processor->process();
        if($current_route_meta[1]['cache_control'] === false) {
            header("Cache-Control: ");
        }
        return array_merge($GLOBALS['EXPORTED_PUBLIC_VARS'], [
            "title" => $processor->template_vars['title'],
            "body" => $body
        ]);
    }

    /** @todo make this sensitive to contexts */
    // function pageOld() {
    //     $GLOBALS['write_to_buffer_handled'] = true;
    //     $route = $_GET['route'];
    //     $contextProcessor = "\Handlers\WebHandler";

    //     $route_context = Routes\Route::get_router_context($_GET['route']);
    //     if($route_context === "apiv1") throw new BadRequest("Too much recursion.");
    //     $contextProcessor = app("context_prefixes")[$route_context]['processor'];
    //     $processor = new $contextProcessor();
    //     $context = $route_context;
    //     $processor->no_write_on_destruct();
    //     $method = "get";
    //     $router = new Router($context, $method);
    //     // $GLOBALS['api_router'] = $router;

    //     $router->init_route_table();

    //     $router->get_routes();

    //     $processor->_stage_init(app("context_prefixes")[$context]);
    //     $processor->_stage_bootstrap['_stage_init'] = true;

    //     $current_route_meta = $router->discover_route($route);

    //     $processor->_stage_route_discovered(...$current_route_meta);
    //     $processor->_stage_bootstrap['_stage_route_discovered'] = true;

    //     $router_result = $router->execute_route();
    //     $processor->_stage_execute($router_result);
    //     $processor->_stage_bootstrap['_stage_execute'] = true;
    //     $processor->_stage_bootstrap['_stage_output'] = true;
    //     $body = $processor->process();
    //     if($current_route_meta[1]['cache_control'] === false) {
    //         header("Cache-Control: ");
    //     }
    //     return array_merge($GLOBALS['EXPORTED_PUBLIC_VARS'], [
    //         "title" => $processor->template_vars['title'],
    //         "body" => $body
    //     ]);
    // }

    
    function modify_plugin_state($name) {
        if (!is_bool($_POST['enabled'])) throw new BadRequest("State must be boolean");
        $GLOBALS['plugin_manager']->change_plugin_state($name, $_POST['enabled']);
        return $_POST;
    }

    function update_gateway_data($id){
        $gateMan = new PaymentGateway();
        $schema = $gateMan->get_schema_name();
        
        $schema = new $schema;
        $mutant = $schema->validate($_POST);

        $result = $gateMan->updateOne(
            ['_id' => $gateMan->__id($id)],
            ['$set' => $mutant],
            ['upsert' => true]
        );
        
        return [
            'secret' => '',
            'token' => ''
        ];
    }
}
