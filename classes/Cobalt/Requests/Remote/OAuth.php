<?php

namespace Cobalt\Requests\Remote;

use Exceptions\HTTP\Unauthorized;

abstract class OAuth extends API {
    public function getDefaultTokenQuery($mode = null, \MongoDB\BSON\ObjectId|null $id = null) {
        // return ["token_name" => $this::class];

        // return ["platform" => $class, 'type' => 'oauth'];
        
        $class = $this::class;
        $class = substr($class, strrpos($class,"\\") + 1);
        
        $id = [];
        if(!function_exists("say")) {
            $id = ['_id' => session("_id")];
            if(!$id['_id']) throw new Unauthorized("You're not authorized to access this resource.");
        }

        return array_merge($id, ["platform" => $class, 'type' => 'oauth']);
    }

    public function updateAuthorizationToken(?array $query = null):array {
        
        if(!$query) $query = $this->getDefaultTokenQuery();
        $iface = $this->getInterface();
        
        $tmp = new $iface(doc_to_array($this->findOne($query)));
        $result = $this->refreshTokenCallback($tmp);
        
        $token = new $iface($result);
        $normalized = $token->normalize($result);

        $this->updateOne(
            $query,
            ['$set' => $normalized],
            ['upsert' => true]
        );
        $token = $this->findOne($query);

        return iterator_to_array($token);
    }

    public function getInterface(){
        $iface = $this->getIfaceName();
        if($iface) return $iface;
        $namespace = "\\Cobalt\Requests\\Remote\\";
        $className = $this::class;
        $exploded = explode("\\",$className);
        return $namespace . $exploded[count($exploded) - 1];
    }
}
