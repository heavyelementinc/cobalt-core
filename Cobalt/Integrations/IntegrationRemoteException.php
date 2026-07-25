<?php

namespace Cobalt\Integrations;

use Exception;
use GuzzleHttp\Exception\ClientException;

class IntegrationRemoteException extends Exception {
    private ClientException $originalError;

    function __construct($message, ClientException $originalError) {
        parent::__construct($message);
        $this->originalError = $originalError;
    }

    function unpack() {
        return $this->originalError->getResponse()->getBody();
    }

    function error():ClientException {
        return $this->originalError;
    }
}