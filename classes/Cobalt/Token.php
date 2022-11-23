<?php

namespace Token;

use Stringable;

class Token implements Stringable {

    function __construct(?string $token = null) {
        if($token) $this->token;
        if(!$this->token) $this->generate_token();
    }

    function generate_token() {
        $this->token = bin2hex(random_bytes(32));
        return $this->token;
    }

    function __toString():string {
        return $this->token;
    }
}
