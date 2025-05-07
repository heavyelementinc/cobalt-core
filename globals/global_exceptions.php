<?php
const INTERNAL_SERVER_ERROR = 0;
const UNKNOWN_ERROR = 0;
const ERROR_ARRAY = [
    [
        'header' => "HTTP/1.0 500 Internal Server Error",
        'title' => 'Internal Server Error',
        'message' => "The server encountered an error and had to stop."
    ]
];

const ERROR_RESOURCE_NOT_FOUND = "Requested resource does not exist.";
const ERROR_STALE_TOKEN = "Stale token detected. You're no longer the author of this document.";

function kill(string $specific_message = "", int $error_type = INTERNAL_SERVER_ERROR) {
    if(!key_exists($error_type, ERROR_ARRAY)) $error_type = 0;
    [$header, $title, $message] = ERROR_ARRAY[$error_type];
    if(!$header) [$header, $title, $message] = ERROR_ARRAY[UNKNOWN_ERROR];
    header($header);
    $msg = $message;
    if($specific_message) $msg = $specific_message;
    cobalt_log("KILL", $msg, COBALT_LOG_EXCEPTION);
    $html = "<html>
    <head>
        <title>$title</title>
        <style>
            body {
                background: gray; 
                display: flex; 
                justify-content: center; 
                align-items: center;
                font-family: 'Arial', sans-serif;
            }
            main {
                background: white; 
                color: black; 
                height: 40ch; 
                width: 40ch;
                border-radius: 10px;
                text-align:center;
                display:flex;
                justify-content: center;
                align-items: center;
                flex-direction: column;
            }
            p {
                margin-bottom: auto;
            }
        </style>
    </head>
    <body>
        <main>
            <h1>$title</h1>
            <p>$msg</p>
        </main>
    </body>
    </html>";
    die($html);
}

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
