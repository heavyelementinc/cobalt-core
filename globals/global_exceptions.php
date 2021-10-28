<?php
function exception_handler($exception) {
    switch ($exception['code']) {
    }
    return true; // If we return true then the error will *not* execute PHP's built in error handler
}
function get_fetch_error($error) {
    return $error->getResponse()->getBody()->getContents();
}
