<?php
function exception_handler($exception) {
    switch ($exception['code']) {
    }
    return true; // If we return true then the error will *not* execute PHP's built in error handler
}

function get_fetch_error($error) {
    return $error->getResponse()->getBody()->getContents();
}

function trim_trailing_slash(string $path, string $char = "/") {
    return ($path[strlen($path) - 1] == $char) ? substr($path, 0, -1) : $path;
}