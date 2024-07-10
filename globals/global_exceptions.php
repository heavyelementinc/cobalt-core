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


// set_exception_handler(function ($e) {
//     if (ini_get('display_errors')) {
//         echo $e;
//     } else {
//         echo "<h1>500 Internal Server Error</h1>
//               An internal server error has been occurred.<br>
//               Please try again later.";
//     }
// });

// register_shutdown_function(function () {
//     $error = error_get_last();
//     if ($error !== null) {
//         kill("Error");
//     }
// });
