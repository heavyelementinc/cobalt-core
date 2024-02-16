<?php
namespace Cobalt\Logs;

use Exception;

class Logs {
    private $logPath = __APP_SETTINGS__['Logs_path'];
    private $logName = null;
    private $maxLength = __APP_SETTINGS__['Logs_max_length'];
    private $logStream;
    function __construct($logName = __APP_SETTINGS__['Logs_default_name']) {
        $this->ensureDirectory();
        $this->logName = $logName;
        $this->logStream = fopen($this->logName, "w+");
    }

    function ensureDirectory() {
        if(!$this->logPath) throw new Exception("Specified log path is falsy");
        if(is_dir($this->logPath)) return;
        mkdir($this->logPath, 0774, true);
    }

    function write($entry) {
        $this->seekToEof();
    }

    function seekToEof() {
        if(fseek($this->logStream, 0, SEEK_END) === 0) return;
        throw new Exception("Failed to seek");
    }

    // function deleteLastLine($numberOfLines = 1) {

    // }
}