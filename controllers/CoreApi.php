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
        $GLOBALS['write_to_buffer_handled'] = true;
        $route = $_GET['route'];
        $processor = new \Web\WebHandler();
        $processor->no_write_on_destruct();
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

    function contact(){

        if(empty($_POST['phone']) && empty($_POST['email']))
            throw new \Exceptions\HTTP\BadRequest("You must specify an email or phone number (or both)");

        if(empty($_POST['name']))
            throw new \Exceptions\HTTP\BadRequest("You need to provide your name");

        $email = new \Mail\SendMail();
        $email->set_vars(array_merge(
            $_POST,
            [
                "ip" => $_SERVER['REMOTE_ADDR'],
                "token" => $_SERVER["HTTP_X_CSRF_MITIGATION"]
            ]
        ));
        $email->set_body_template("/emails/contact-form.html");
        try{
            $email->send(app("API_contact_form_email_to"),"New contact form submission");
        } catch (Exception $e) {
            return "Failed to submit your request. Try again later.";
        }
        return $_POST;
    }

}