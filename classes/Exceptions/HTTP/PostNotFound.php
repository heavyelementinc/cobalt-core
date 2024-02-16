<?php

namespace Exceptions\HTTP;

class PostNotFound extends NotFound {
    public $name = "Post Not Found";

    function __construct($message, $clientMessage = "That post does not exist", $data = []) {
        parent::__construct($message, $clientMessage, array_merge(['template' => 'posts/parts/not-found.html'],$data));
    }
}