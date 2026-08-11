<?php

namespace Cobalt\Integrations;

use Drivers\Database;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;
use ReflectionClass;
use RuntimeException;
use TypeError;

/**
 * Integrations must live in the \Cobalt\Integrations\ namespace
 * @package Cobalt\Integrations
 */
abstract class Base extends Database {
    
    const STATUS_CHECK_OK   = 0;
    const STATUS_CHECK_FAIL = 1;

    const ERROR_HANDLING = [
        "CONTINUE_REQUEST" => 0,
        "RESTART_REQUEST" => 1,
        "ABORT_REQUEST" => 2,
        "UNHANDLED_ERROR" => 10,
    ];

    public Config $config;
    public bool $configured = false;

    function __construct() {
        parent::__construct();

        $keys = $this->findOne(['__token_name' => $this->get_unique_token()]);

        if($keys) {
            $this->config = $keys;
            $this->configured = true;
        } else $this->config = $this->configuration($keys);
        
        $reflect = new ReflectionClass($this);
        $this->config->name = $reflect->getShortName();
        $this->config->publicName = $this->publicName();
        $this->config->tokenName = $this->get_unique_token();
        $this->config->icon = $this->publicIcon();
    }

    abstract function publicName(): string;
    abstract function publicIcon(): string;

    /**
     * Returns the name of the collection from which all configs are stored
     * @return string 
     */
    final function get_collection_name() {
        return "IntegrationTokens";
    }

    /**
     * This is the name of the token as it's stored in the database
     * @return string
     */
    abstract function get_unique_token(): string;

    /**
     * This function is called when a new configuration is first
     * created in order to tell the database how to persist
     * our token info. If data is already stored for this 
     * integration then this function is never called.
     * @return Config
     */
    abstract function configuration(): Config;

    abstract function status(): int;

    /**
     * This should return the contents of the button that takes you to
     * the integration management page
     * @return string 
     */
    public function html_index_button(): string {
        return view("Cobalt/Integrations/templates/button.html", [
            "icon" =>  $this->config->icon,
            "name" => $this->config->publicName,
            "class" => $this->config->name
        ]);
    }

    /**
     * This should return the HTML for the token management screen
     * @return string 
     */
    abstract function html_token_editor():string;

    /**
     * Fetch will reach out via the method and action and return a response
     * @param mixed $method - The HTTP Method to use for the request
     * @param mixed $action - The URL to issue the request to
     * @param array $data - Any data to be submitted with the request
     * @param array $headers - Any additional headers to apply to the request
     * @param bool $authenticate - Automatically add authentication headers/params to this request
     * @return array{response:ResponseInterface|null, headers:array, result:mixed, error: ClientException|null}
     * @throws GuzzleException
     * @throws RuntimeException
     * @throws IntegrationRemoteException
     */
    public function fetch(string $method, string $action, array $data = [], array $headers = [], bool $authenticate = true):array {
        $client = new Client();
        $method_type = strtoupper($method);
        $headers = $this->requestHeaders($headers);
        $rq = ['headers' => $headers];
        $body = $this->requestBody($data);

        if($body && in_array($method_type, ['POST','PUT', 'PATCH'])) {
            $rq += $body;
        }

        if($authenticate) $this->config->authenticate($rq, $client);
        $request = [];
        try {
            $request = $client->request($method_type, $action, $rq);
        } catch(ClientException $error) {
            $errorHandlingResult = $this->handleError($error, $request) ;
            switch($errorHandlingResult) {
                case self::ERROR_HANDLING['ABORT_REQUEST']:
                    return ['response' => null, 'headers' => [], 'result' => null, 'error' => $error];
                case self::ERROR_HANDLING['CONTINUE_REQUEST']:
                    break;
                case self::ERROR_HANDLING['RESTART_REQUEST']:
                    return $this->fetch($method, $action, $data, $headers, $authenticate);
                case self::ERROR_HANDLING['UNHANDLED_ERROR']:
                default:
                    $response = $error->getResponse();
                    $responseBodyAsString = $response->getBody()->getContents();
                    throw new IntegrationRemoteException($responseBodyAsString, $error);
            }
        }
        $response = $request->getBody()->getContents();
        $responseHeaders = $request->getHeaders();
        $result = "";
        if(strpos($responseHeaders['Content-Type'][0], 'json')) $result = json_decode($response, true);
        else if(strpos($responseHeaders['Content-Type'][0], 'urlencoded')) parse_str($response, $result);
        else $result = $response;
        return ['response' => $result, 'headers' => $responseHeaders, 'result' => $response, 'error' => null];
    }

    public function handleError($error, &$request):int {
        return self::ERROR_HANDLING['UNHANDLED_ERROR'];
    }

    public function requestHeaders(array $headers = [], bool $authenticate = false): array {
        return $headers;
    }

    public function requestBody(mixed $data):array {
        // return $data;
        switch($this->__requestEncoding ?? (int)$this->config->__requestEncoding) {
            case REQUEST_ENCODE_JSON:
                return ['body' => json_encode($data)];
                break;
            case REQUEST_ENCODE_FORM:
                return [RequestOptions::FORM_PARAMS => $data];
                break;
            case REQUEST_ENCODE_XML:
                return ['body' => xmlrpc_encode($data)];
                break;
            case REQUEST_ENCODE_MULTIPART_FORM:
                return [RequestOptions::MULTIPART => $data];
                break;
            case REQUEST_ENCODE_PLAINTEXT:
            default:
                return ['body' => (string)$data];
                break;
        }
    }
    private ?int $__requestEncoding = null;
    public function requestEncoding(?int $type) {
        switch($type) {
            case REQUEST_ENCODE_JSON:
            case REQUEST_ENCODE_FORM:
            case REQUEST_ENCODE_XML:
            case REQUEST_ENCODE_MULTIPART_FORM:
            case REQUEST_ENCODE_PLAINTEXT:
            case null:
                $this->__requestEncoding = $type;
                return;
        }
        throw new TypeError("$type is out of range");
    }

}
