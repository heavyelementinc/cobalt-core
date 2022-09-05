<?php

namespace Cobalt\Requests\Remote;

use Exception;
use Exceptions\HTTP\HTTPException;
use GuzzleHttp\Client as Guzzle;
use GuzzleHttp\Exception\GuzzleException;
use Traversable;

abstract class API extends \Drivers\Database implements APICall {

    public $headers = [];
    public $doc = null;
    protected $mode = "app";

    public $request_headers = [];
    public $request_body = [];
    public $request_params = [];

    function __construct() {
        parent::__construct();
    }

    abstract function getIfaceName():string;

    abstract function getPaginationToken():array;

    abstract function refreshTokenCallback($result):string;

    /**
     * Must return an 'icon' and a 'name' value
     * @return array 
     */
    abstract static function getMetadata():array;

    final public function get(string $url, array $headers = []) {
        return $this->fetch($url, 'get', [], $headers);
    }

    final public function post(string $url, $body, $headers = []) {
        return $this->fetch($url, 'post', $body, $headers);
    }

    final public function put(string $url, $body, $headers = []) {
        return $this->fetch($url, 'put', $body, $headers);
    }

    final public function delete(string $url, $body, $headers = []) {
        return $this->fetch($url, 'delete', $body, $headers);
    }
    
    /**
     * Updated the stored value for the current user in the database
     * 
     * @param ?array $query - An override query
     * @return array The updated document
     * @throws Exception 
     */
    public function updateAuthorizationToken(?array $query = null):array {
        if(!$query) $query = $this->getDefaultTokenQuery();
        $iface = $this->getInterface();
        
        $tmp = new $iface($this->findOne($query));
        $result= $this->refreshTokenCallback();
        
        $token = new $iface($result);

        $this->updateOne(
            $query,
            ['$set' => $token->normalize()],
            ['upsert' => true]
        );
        $token = $this->findOne($query);

        return iterator_to_array(new $iface($token));
    }

    /**
     * Retrieves the authorization token
     * @param mixed $query 
     * @return object 
     * @throws Exception 
     */
    public function authorizationToken($query = null) {
        if(!$query) $query = $this->getDefaultTokenQuery();

        $this->doc = $this->findOne($query);

        $iface = $this->getInterface();
        if(is_iterable($this->doc)) $this->doc = iterator_to_array($this->doc);
        $tk = new $iface($this->doc,$this->mode);
        
        /** Now we figure out what to do with this stuff */
        switch(strtolower($tk->type)) {
            case "authorization":
                $this->addRequestHeaders(["Authorization" => "$tk->prefix $tk->token"]);
                break;
            case "parameter":
                $this->addRequestParams([$tk->prefix => $tk->token]);
                break;
        }
        
        return $tk;
    }

    public function addRequestHeaders(array $headers = []){
        $this->request_headers = array_merge($this->request_headers,$headers);
    }

    public function setRequestBody($body) {
        $this->request_body = $body;
    }

    public function addRequestParams(array $params) {
        $mutant_params = array_fill_keys(array_keys($params),"");
        foreach($params as $key => $value) {
            if(gettype($value) === "array") $value = $this->serializeParamArray($value);
            $mutant_params[$key] = $value;
        }
        $this->request_params = array_merge($this->request_params,$mutant_params);
    }

    public function setMode($mode = "app") {
        $modes = ["app", "user"];
        if(!in_array($mode,$modes)) throw new \Exception("Unrecognized mode");
        $this->mode = $mode;
    }

    /**
     * Override this is you need to change the default way array parameters are
     * serialized to a string.
     * @param array $param - The array to be serialized.
     * @return string - The resulting serialized string.
     */
    private function serializeParamArray(array $param) {
        return implode(",", $param);
    }

    private function getInterface(){
        $iface = $this->getIfaceName();
        if($iface) return $iface;
        $namespace = "\\Cobalt\Requests\\Remote\\";
        $className = $this::class;
        $exploded = explode("\\",$className);
        return $namespace . $exploded[count($exploded) - 1];
    }

    final private function fetch(string $url, string $method) {
        $this->authorizationToken();

        $data = [
            'headers' => $this->request_headers
        ];

        $mutant_url = $url;
        if(!empty($this->request_params)) $mutant_url = "$mutant_url?".http_build_query($this->request_params);
        if($method !== "get") $data['body'] = $this->request_body;
        $client   = new Guzzle();
        try{
            $response = $client->request($method, $mutant_url, $data);
        } catch (GuzzleException $e) {
            $error_message = $e->getResponse()->getBody()->getContents();
            if(is_root()) $e = $error_message;
            throw new HTTPException($e);
        }
        $this->parseHeaders($response->getHeaders());
        
        return $this->parseBody($response->getBody());
    }

    private function getDefaultTokenQuery($mode = null, \MongoDB\BSON\ObjectId|null $id = null) {
        // return ["token_name" => $this::class];
        if($mode === null) $mode = $this->mode ?? "app";
        if($mode === "app") return ["token_name" => $this::class];
        
        if (!$id) $id = session("_id");

        return ["for" => $id, "token_name" => $this::class];
    }

    function get_collection_name() {
        return "CobaltTokens";
    }

    private function parseHeaders($headers) {
        foreach($headers as $name => $values) {
            $this->headers[$name] = $values;
        }
    }

    private function parseBody($body) {
        $contentType = null;
        if(key_exists("Content-Type", $this->headers)) $contentType = $this->headers['Content-Type'];
        if(!$contentType && key_exists('content-type',$this->headers)) $contentType = $this->headers['content-type'];
        if(!$contentType) return (string)$body;

        if(preg_match("/json/",$contentType[0])) {
            return json_decode((string)$body);
        } else if($contentType[0] === "application/x-www-form-urlencoded"){
            $result = [];
            parse_str((string)$body,$result);
            return $result;
        }
        return (string)$body;
    }
}