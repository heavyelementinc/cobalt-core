<?php

use Cobalt\Payments\PaymentGateway;
use \Exceptions\HTTP\BadRequest;
use Exceptions\HTTP\NotFound;
use \Exceptions\HTTP\ServiceUnavailable;
use \Handlers\WebHandler;
use \Routes\Router;
use \Mail\SendMail;

class CoreApi extends \Controllers\Pages {

    function login() {
        // Get the headers
        $headers = apache_request_headers();
        // Check if the authentication values exist
        if (!key_exists('Authentication', $headers)) throw new BadRequest("Request is missing Authentication");

        // Decode and split the credentials
        $credentials = explode(":", base64_decode($headers['Authentication']));

        // Log in the user using the credentials provided. If invalid credentials
        // then login_user will throw an exception.
        $result = $GLOBALS['auth']->login_user($credentials[0], $credentials[1], $_POST['stay_logged_in']);

        // If we're here, we've been logged in successfully. Now it's time to
        // determine what we should be doing. If we're on the login page, 
        // redirect the user to "/admin" otherwise refresh the page
        $redirect = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH);
        if (!$redirect) $redirect = "/";
        if ($redirect === app("Auth_login_page") && has_permission('Admin_panel_access')) {
            // If the user has admin panel privs, we redirect them there
            $redirect = app("Admin_panel_prefix") . "/";
        }
        http_response_code(200);
        header("X-Redirect: $redirect");
        return $result;
    }

    function logout() {
        return $GLOBALS['auth']->logout_user();
    }

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

    function contact() {

        $validator = new \Contact\ContactFormValidator();

        $mutant = $validator->validate($_POST);
        // if (empty($_POST['phone']) && empty($_POST['email'])) {
        //     $error = "";
        //     if (key_exists('phone', $_POST)) $error .= " or phone number (or both)";
        //     throw new BadRequest("You must specify an email$error");
        // }

        // if (empty($_POST['name']))
        //     throw new BadRequest("You need to provide your name");

        $email = new SendMail();
        $email->set_vars(array_merge(
            $mutant,
            ['POST' => $_POST],
            [
                "ip" => $_SERVER['REMOTE_ADDR'],
                "token" => $_SERVER["HTTP_X_CSRF_MITIGATION"]
            ]
        ));
        $email->set_body_template("/emails/contact-form.html");
        try {
            $subject = "New contact form submission";
            if (key_exists("subject", $_POST)) $subject = "Webform: \"" . strip_tags($_POST['subject'] . "\"");
            $email->send(app("API_contact_form_recipient"), $subject);
        } catch (Exception $e) {
            throw new ServiceUnavailable("There was an error on our end.");
        }
        return $mutant;
    }

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
