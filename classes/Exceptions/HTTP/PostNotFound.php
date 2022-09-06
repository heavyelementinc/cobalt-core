<?php

namespace Exceptions\HTTP;

class PostNotFound extends NotFound {

    function __construct($message, $data = []) {
        parent::__construct($message, array_merge(['template' => 'posts/parts/not-found.html'],$data));
    }
}