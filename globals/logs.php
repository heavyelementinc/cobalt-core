<?php

define('CL_DEBUG', 0);
define('CL_NOTICE', 1);
define('CL_WARNING', 2);
define('CL_ERROR', 4);
define('CL_CRITICAL', 8);
define('CL_ALERT', 16);
define('CL_EMERGENCY', 32);
define('CL_MINIMUM_LOG_LEVEL', CL_WARNING);
define('CL_MAX_LOG_SIZE', 1024 * 1024);

define('CL_REVERSE_LOOKUP', [
    CL_DEBUG     => 'CL_DEBUG',
    CL_NOTICE    => 'CL_NOTICE',
    CL_WARNING   => 'CL_WARNING',
    CL_ERROR     => 'CL_ERROR',
    CL_CRITICAL  => 'CL_CRITICAL',
    CL_ALERT     => 'CL_ALERT', 
    CL_EMERGENCY => 'CL_EMERGENCY', 
]);

function journal(string $string, int $level = CL_DEBUG, string $EOL = "\n") {
    if($level < CL_MINIMUM_LOG_LEVEL) return;
    $journal = __APP_ROOT__ . "/logs/app.log";
    journal_rotate($journal);
    $resource = fopen($journal, 'w') or die("Cannot lock journal for writing.");
    try {
        if(!function_exists("say")) $id = session('_id');
        else {
            $id = "CLI";
            say($string . $EOL);
        }
    } catch (Exception $e) {

    }
    fwrite($resource, $string . " : " . (string)$id->_id . " : ". $EOL);
    fclose($resource);
}


function journal_rotate($file) {
    if(!file_exists($file)) touch($file);
    // if(filesize($file) > CL_MAX_LOG_SIZE) rename($file, $file . glob)
}
