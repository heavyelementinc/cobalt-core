<?php

namespace Cobalt\Remote;

use Drivers\Database;
use Error;
use Exception;
use Exceptions\HTTP\MethodNotAllowed;

abstract class Authenticator extends Database{
    /**
     * The key used to exchange data
     * @var AuthSchema
     */
    protected ?AuthSchema $details = null;
    protected bool $hasDetails = false;
    protected array $metadata = [];
    
    protected string $authType = "bearer";

       
    abstract function initializeCallback(&$metadata):void;
    abstract function credentialsCallback(&$result):void;
    abstract function storageCallback(&$toStore):void;
    abstract function readCallback(&$result):void;
    /**
     * @return string should always return dunderscored `__NAMESPACE__`
     */
    abstract function getNamespace():string;

    function __construct() {
        parent::__construct();
        $this->initialize();
    }

    private function initialize():void {
        $metadata = $this->getMetadata();
        $requiredFields = ['name' => 'string', 'icon' => 'string', 'identifier' => 'string', 'schema' => "AuthSchema"];
        
        foreach($requiredFields as $key => $type) {
            if(!isset($metadata[$key])) throw new Exception("Authentication config error. Missing '$key'");
        }
        
        
        $this->initializeCallback($metadata);
        $this->metadata = $metadata;
        
        // Now let's get our credentials
        $schema = $this->get_schema_name();
        $this->details = new $schema();
        $this->readCredentialsFromDatabase();
    }

    public function get_collection_name() {
        return "ApiKeys";
    }

    public function get_schema_name($doc = []) {
        return $this->getNamespace() . "\\" . $this->metadata['schema'];
    }

    function readCredentialsFromDatabase() {
        if($this->hasDetails) return;
        $result = $this->findOne(['identifier' => $this->metadata['identifier']]);
        if(!$result) {
            $this->hasDetails = false;
            return;
            // throw new Exception("Failed to load '" . $this->metadata['name'] . "' credentials from database");
        }
        $this->details = $result;
    }

    /**
     * Array must include
     *   * "name" - (string) The friendly name of the credential
     *   * "icon" - (string) The name of an icon (MDI icon)
     *   * "identifier" - (string) The DB identifier (for lookup and storage)
     *   * "schema" - (AuthSchema) The schema for the current database
     * @return array best practice is to return the service.json file
     */
    abstract static function getMetadata():array;

    public function getCredentials(array &$result) {
        // Let's make sure we have 'headers' and 'params' field
        if(!key_exists("headers", $result)) $result['headers'] = [];
        if(!key_exists("params", $result)) $result['params'] = [];

        switch($this->authType) {
            case "bearer":
                $result['headers']['Authorization'] = $this->details->tokenPrefix . " " . $this->details->key;
                break;
            case "basic":
                $result['headers']['Authorization'] = 'Basic ' . base64_encode($this->details->key . ":" . $this->details->secret);
                break;
            case "digest":
                throw new MethodNotAllowed("Digest authentication is not yet supported");
                // $result['headers']['Authorization'] = 'Digest ' . base64_encode($this->details->key . ":" . $this->details->secret);
                break;
            case "param":
                $result['params'][$this->details->tokenField] = $this->details->key;
                break;
        }
        
        // Now let's run our callback so that individual plugins can override
        // the default functionality if we need them to.
        $this->credentialsCallback($result);
    }

 

    function renderView() {
        $view = "";

        foreach($this->details->readSchema() as $field => $value) {
            if(!isset($value['label'])) continue; // If the label is falsy, it shouldn't be included
            $view .= "<li><label>$value[label]</label>";
            $view .= "{{auth.$field.field()}}</li>\n\n";
        }
        
        return view_from_string($view, [
            'auth' => $this->details,
        ]);
    }

    function store($values) {
        $schema = $this->get_schema_name();
        $auth = new $schema();
        $auth->__validate($values);
        $operators = $auth->__operators();

        $auth->identifier = $this->metadata['identifier'];

        // Check if the identifier already exists
        $result = $this->findOne(['identifier' => $this->metadata['identifier']]);

        $created  = 0;
        $modified = 0;
        // If not, insert the document
        if(!$result) {
            $r = $this->insertOne($auth);
            $created = $r->getInsertedCount();
        } else {
            // Otherwise, update the document
            $r = $this->updateOne(['identifier' => $this->metadata['identifier']], $operators);
            $modified = $r->getModifiedCount();
        }

        return [$created, $modified];
    }
}