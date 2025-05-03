<?php

namespace Cobalt\WebSockets;

use Cobalt\Websockets\Exceptions\SocketException;
use Cobalt\Websockets\Exceptions\TargetedException;
use Cobalt\WebSockets\Interfaces\MessageHandler;
use Cobalt\WebSockets\Traits\ReadCLI;
use Cobalt\WebSockets\Traits\SocketInit;
use JsonException;
use stdClass;

class WebSocketServer {
    use SocketInit, ReadCLI;
    const LOG_FILE_PATH = __APP_ROOT__ . "/ignored/logs/websockets/";
    const HEARTBEAT_TICK_INTERVAL = __APP_SETTINGS__['Websocket_heartbeat_tick_interval'];
    private int $tick = 0;
    private array $timeouts = [];
    private array $intervals = [];
    private $logFile;
    
    private MessageHandler $handler;

    private bool $execute_next_tick = true;

    function __construct() {
        $this->startLogFile();
    }

    private function startLogFile() {
        $logFile = self::LOG_FILE_PATH . "socket-".time().".log";
        mkdir(self::LOG_FILE_PATH, 0777, true);
        touch($logFile);
        $this->logFile = fopen($logFile, "a");
    }

    public function logWrite($message) {
        $time = microtime();
        fwrite($this->logFile, "$time-$message\n");
    }

    public function setHost(string $host):void { $this->host = $host; }
    public function setPort(int $port):void { $this->port = $port; }
    public function setHandler(MessageHandler $handler):void {
        $args = func_get_args();
        array_shift($args);
        $this->handler = $handler;
        try {
            $this->handler->setServer($this);
            $this->handler->initialize(...$args);
        } catch (SocketException $e) {
            $this->sendError($e);
        }
    }
        
    public function sendMessage(string $type, array|stdClass $message, ?int $to = null) {
        $json = json_encode(['type' => $type, ...$message]);
        $mdata = $this->mask($json);
        if($to && key_exists($to, $this->clients)) {
            $this->logWrite("⬆️$to"."⬆️$json");
            $this->sendMessageToIndividualClient($this->clients[$to], $mdata);
            return true;
        }
        $this->logWrite("⬆️null"."⬆️$json");
        foreach($this->clients as $id => $client_socket) {
            $this->sendMessageToIndividualClient($client_socket, $mdata);
        }
        
        return true;
    }

    private function sendMessageToIndividualClient($changed_socket, $mdata) {
        @socket_write($changed_socket, $mdata, strlen($mdata));
    }

    public function loop() {
        $this->setInterval(function () {
            $this->sendMessage("heartbeat", []);
        }, self::HEARTBEAT_TICK_INTERVAL);

        while($this->execute_next_tick) {
            $this->readCommand();
            // Create a temp array of clients
            $changed = $this->clients;
            $null = null;

            socket_select($changed, $null, $null, 0, 10);

            // Check if there are any new connections we need to handle
            if (in_array($this->socket, $changed)) {
                $this->addClient($changed);
            }
            try {
                // Server update code should go here
                $this->handler->onEveryTick($this->tick);
            } catch (SocketException $e) {
                $this->sendError($e);
            }

            // Loop through all socket connections
            foreach($changed as $socket_id => $changed_socket) {
                // Sift through incoming data and then broadcast it to all other connections
                while(socket_recv($changed_socket, $buf, 1024, 0) >= 1) {
                    if (substr($buf, 0, 1) == "{") {
                        $received_text = $buf;
                    } else {
                        $received_text = $this->unmask($buf);
                    }
                    $this->logWrite("⬇️$socket_id"."⬇️$received_text");
                    $message_id = -1;
                    $data = $this->parseSocketMessage($received_text, $message_id, $socket_id);
                    if(!$data) {
                       continue;
                    }

                    try {
                        $this->handler->onMessage($socket_id, $data, $message_id);
                    } catch (SocketException $e) {
                        $this->sendMessageToIndividualClient($this->clients[$socket_id], $this->mask("REP:$message_id:"));
                        $this->sendError($e);
                    }
                    // if ($data && $broadcast_to_all_players) {
                    //     // $response_text = mask(json_encode($data));
                    //     $this->sendMessage('responseRebroadcast', $data);
                    // }
                    break 2;
                }
                
                // Check if the client has disconnected and clean up if needed
                $buf = @socket_read($changed_socket, 1024, PHP_NORMAL_READ);
            
                if ($buf === false) {
                    $this->consoleLog("error", "Socket error reading $socket_id: ".socket_strerror(socket_last_error($changed_socket)));
                    $found_socket = array_search($changed_socket, $this->clients);
                    if($found_socket === 0) continue; // Don't remove the first element from the array
                    socket_getpeername($changed_socket, $ip);
                    unset($this->clients[$found_socket]);
                    
                    // $response = $this->mask(json_encode(["type" => "system", "message" => $ip . " disconnected"]));
                    try {
                        $this->handler->onClientLeave($socket_id);
                    } catch (SocketException $e) {
                        $this->sendError($e);
                    }
                    $this->consoleLog("disconnect", "Client $socket_id was disconnected");
                }
            }

            // $this->tick += 1;
            // if($this->tick >= 100_000) {
            //     $this->tick = 0;
            //     $this->sendMessage("heartbeat", []);
            // }

            /** @var TimeOut $interval */
            foreach($this->timeouts as $id => $timeout) {
                if(!$timeout->hasExpired()) continue;
                $timeout->getCallback()($this);
                $this->clearTimeout($id);
            }

            /** @var TimeOut $interval */
            foreach($this->intervals as $interval) {
                if(!$interval->hasExpired()) continue;
                $interval->getCallback()($this);
                $interval->setStart(millitime());
            }
        }
        socket_close($this->socket);
    }

    public function parseSocketMessage($data, &$message_id, $socket_id) {
        if($data === "") {
            // socket_close($socket_id);
            return false;
        }
        $delimeter_position = strpos($data, "!");
        $message_id = substr($data,0,$delimeter_position);
        $d = substr($data, $delimeter_position + 1);
        try {
            return json_decode($d, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $e) {
            $this->sendMessageToIndividualClient($this->clients[$socket_id], $this->mask("REP:$message_id:"));
            // If there was an error parsing data, skip doing anything else with the message
            $this->consoleLog("error", "Client ".fmt($socket_id, "e")." sent a malformed message:\n" .fmt($data, "i"));
        }
    }

    public function sendError($e) {
        $to = null;
        if($e instanceof TargetedException) {
            $to = $e->getTo();
        }
        $this->sendMessage($e->getType(), [
            'message' => $e->getMessage(),
            'command' => [
                $e->getCommand() => $e->getArgs()
            ]
        ], $to);
        $this->consoleLog('error', $e->getMessage());
    }

    public function readCommand() {

    }

    public function consoleLog(string $type, string $log_message, $log_type = "i", $verbosity_level = 10):void {
        $length = 7;
        $log_type = "i";
        switch($type) {
            case "e":
            case "error":
                $log_type = "e";
                $type = "ERROR";
                break;
        }
        $type = fmt(str_pad(substr(strtoupper($type), 0, $length), $length, " ", STR_PAD_BOTH),$log_type);
        $log = "[$type] - $log_message\n";
        print($log);
        $this->handler->onConsoleLog($log, $type, $log_message, $log_type, $verbosity_level);
    }

    public function setTimeout(callable $callback, int $microtime):int {
        return $this->registerTimer($callback, $microtime, $this->timeouts);
    }

    public function setInterval(callable $callback, int $microtime):int {
        return $this->registerTimer($callback, $microtime, $this->intervals);
    }

    private function registerTimer(callable $callback, int $microtime, array &$list):int {
        $timeout = new TimeOut();
        $timeout->setStart(millitime());
        $timeout->setMilliseconds($microtime);
        $timeout->setCallback($callback);
        $ids = array_keys($list);
        $id = $ids[count($ids) - 1] + 1;
        $list[$id] = $timeout;
        return $id;
    }

    public function clearTimeout(int $id) {
        unset($this->timeouts[$id]);
    }

    public function clearInterval(int $id) {
        unset($this->intervals[$id]);
    }
}