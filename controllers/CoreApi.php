<?php

use \Exceptions\HTTP\BadRequest;
use \Exceptions\HTTP\ServiceUnavailable;
use \Handlers\WebHandler;
use \Routes\Router;
use \Mail\SendMail;

class CoreApi extends \Controllers\Pages {

    function login() {
        $headers = apache_request_headers();
        if (!key_exists('Authentication', $headers)) throw new BadRequest("Request is missing Authentication");
        $credentials = explode(":", base64_decode($headers['Authentication']));
        return $GLOBALS['auth']->login_user($credentials[0], $credentials[1], $_POST['stay_logged_in']);
    }

    function logout() {
        return $GLOBALS['auth']->logout_user();
    }

    function page() {
        $GLOBALS['write_to_buffer_handled'] = true;
        $route = $_GET['route'];
        $processor = new WebHandler();
        $context = "web";
        $processor->no_write_on_destruct();
        $router = new Router($context, "get");

        $router->init_route_table();

        $router->get_routes();

        $processor->_stage_init(app("context_prefixes")[$context]);
        $processor->_stage_bootstrap['_stage_init'] = true;

        $current_route_meta = $router->discover_route($route);

        $processor->_stage_route_discovered(...$current_route_meta);
        $processor->_stage_bootstrap['_stage_route_discovered'] = true;

        $router_result = $router->execute_route();
        $processor->_stage_execute($router_result);
        $processor->_stage_bootstrap['_stage_execute'] = true;
        $processor->_stage_bootstrap['_stage_output'] = true;
        $body = $processor->process();
        return [
            "title" => $processor->template_vars['title'],
            "body" => $body
        ];
    }

    function contact() {

        if (empty($_POST['phone']) && empty($_POST['email'])) {
            $error = "";
            if (key_exists('phone', $_POST)) $error .= " or phone number (or both)";
            throw new BadRequest("You must specify an email$error");
        }

        if (empty($_POST['name']))
            throw new BadRequest("You need to provide your name");

        $email = new SendMail();
        $email->set_vars(array_merge(
            $_POST,
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
        return $_POST;
    }
}
