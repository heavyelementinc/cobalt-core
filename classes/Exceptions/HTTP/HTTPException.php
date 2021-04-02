<?php
namespace Exceptions\HTTP;
class HTTPException extends \Exception{
    private $mode;
    public $status_code = 500;

    function __construct($message,$data = null,$exit = true){
        $this->mode = $GLOBALS['route_context'];
        $this->exit = $GLOBALS['allowed_to_exit_on_exception'];
        $exe = "web";
        if($this->mode !== "web") $exe = "api";
        $header = "HTTP/1.0 " . $this->status_code . " " . $this->name;
        header($header,true,$this->status_code);
        $this->{$exe . "_execute"}($message,$data);
        parent::__construct($message);
    }

    public function web_execute($message,$data){
        $template = "errors/" . $this->status_code . ".html";
        if(key_exists('template',$data)) {
            $template = $data['template'];
            unset($data['template']);
        }
        $embed = "";
        // if(app('debug')) $embed = "<h2>If anyone asks:</h2><pre>" . base64_encode("$message\n\n" . \json_encode($data)) . "</pre>";
        \add_vars([
            'title' => $this->status_code,
            'message' => $message,
            'embed' => $embed,
            'status_code' => $this->status_code,
            'data' => $data,
            'body_id' => app("HTTP_error_body_id"),
        ]);
        if(!\template_exists($template)) $template = "errors/default.html";
        \add_template($template);
        if($this->exit) exit; // This will allow the rest of the shutdown sequence (i.e. the __destruct methods on classes)
    }

    public function api_execute($message,$data){
        $GLOBALS['router_result'] = [
            'code' => $this->status_code,
            'error' => $message,
            'data' => $data
        ];
        if($this->exit) exit;
    }
}