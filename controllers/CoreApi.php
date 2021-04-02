<?php
class CoreApi extends \Controllers\Pages{

    function login(){
        $headers = apache_request_headers();
        if(!key_exists('Authentication',$headers)) throw new \Exceptions\HTTP\BadRequest("Request is missing Authentication");
        $credentials = explode(":",base64_decode($headers['Authentication']));
        return $GLOBALS['auth']->login_user($credentials[0],$credentials[1],$_POST['stay_logged_in']);
    }

    function logout(){
        return $GLOBALS['auth']->logout_user();
    }

    function page(){
        $route = $_GET['route'];
        $processor = new \Web\WebHandler();
        $router = new \Routes\Router("web","get");
        $router->get_routes();
        if(method_exists($processor,'post_router_init')) $processor->post_router_init();
        $router->discover_route($route);
        $router->execute_route();
        $body = $processor->process();
        return [
            "title" => $processor->template_vars['title'],
            "body" => $body
        ];
    }

    

}