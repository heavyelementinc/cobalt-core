<?php

namespace Cobalt\WebSocket;

use Auth\UserSchema;

/**
 * The Client class represents a client connection, it will contain data about each
 * incoming connection such as the IP address, cookie info, etc.
 * @package Cobalt\WebSocket
 * @property bool $associate_with_user_session - if `true` then the static::user will be an instance of UserSchema or null
 * @property ?UserSchema $user - null or UserSchema use isUser() for true/false
 */
class Client {
    public $resourceId;
    public array $requestHeaders;
    public string $ipAddress;
    public \Socket $socket;
    public ?UserSchema $user;
    private $associate_with_user_session = false;
    
    public function __construct(\Socket $socket, string $clientIp, array $requestHeaders) {
        $this->resourceId = "someid";
        $this->socket = $socket;
        $this->ipAddress = $clientIp;
        $this->requestHeaders = $requestHeaders;
    }


}
