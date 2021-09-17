<?php

namespace ServerSentEvents;

class Stream {
    private $interval = 2;

    function __construct() {
        header("Cache-Control: no-cache");
        header("Content-Type: text/event-stream");
        $this->started = microtime(true) * 1000;
        $this->interval = $this->getInterval();
        $this->ping();
        ob_start();
    }

    function getInterval(): int {
        return 2;
    }

    function execute() {
        return null;
    }

    function start() {
        while (true) {
            $execute = $this->execute();
            if (isset($execute['type']) && isset($execute['data'])) $this->dispatchEvent($execute['type'], $execute['data']);
            else $this->ping();

            if (connection_aborted()) break;
            sleep($this->interval);
        }
    }

    public function finish() {
        $time = microtime(true) * 1000;
        $this->dispatchEvent("completed", ['completed' => $time, 'duration' => $time - $this->started]);
    }

    public function error() {
        $time = microtime(true) * 1000;
        $this->dispatchEvent("error", ['timeout' => $time, 'duration' => $time - $this->started]);
    }

    public function updateProgressBar($data, $message) {
        $this->dispatchEvent("update", ["percent" => $data, 'message' => $message ?? "Post-processing"]);
    }

    public function dispatchEvent($type, $data) {
        echo "event: $type\n";
        echo "data: " . json_encode($data) . "\n\n";
        ob_end_flush();
        flush();
    }

    private function ping() {
        $this->dispatchEvent('ping', ['time' => date(DATE_ISO8601)]);
    }
}
