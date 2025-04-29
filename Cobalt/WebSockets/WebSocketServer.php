<?php

namespace Cobalt\WebSockets;

use Cobalt\WebSockets\Interfaces\MessageHandler;
use Socket;
use stdClass;

class WebSocketServer {
    const HEARTBEAT_TICK_INTERVAL = __APP_SETTINGS__['Websocket_heartbeat_tick_interval'];
    private $tick = 0;
    private string $host = __APP_SETTINGS__['domain_name'];
    private int    $port = __APP_SETTINGS__['Websocket_default_port'];
    
    private Socket|false $socket;
    public array $clients = [];
    private MessageHandler $handler;

    private bool $execute_next_tick = true;

    function __construct() {
        
    }

    public function setHost(string $host):void { $this->host = $host; }
    public function setPort(int $port):void { $this->port = $port; }
    public function setHandler(MessageHandler $handler):void {
        $args = func_get_args();
        array_shift($args);
        $this->handler = $handler;
        $this->handler->setServer($this);
        $this->handler->initialize(...$args);
    }

    public function initSocket() {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($this->socket, 0, $this->port);
        socket_listen($this->socket);
        $this->clients[] = $this->socket;
        $this->consoleLog("init", "Socket open on port $this->port");
    }
        
    public function sendMessage(string $type, array|stdClass $message, ?int $to = null) {
        $mdata = $this->mask(json_encode(['type' => $type, ...$message]));
        if($to && key_exists($to, $this->clients)) {
            $this->sendMessageToIndividualClient($this->clients[$to], $mdata);
        }
        foreach($this->clients as $id => $client_socket) {
            if($to && $to !== $id) continue;
            $this->sendMessageToIndividualClient($client_socket, $mdata);
        }
        
        return true;
    }

    private function sendMessageToIndividualClient($changed_socket, $mdata) {
        @socket_write($changed_socket, $mdata, strlen($mdata));
    }

    
    public function addClient(&$changed) {
        // There are new sockets, so we'll bring them aboard.
        $socket_new = socket_accept($this->socket);
        $socket_id  = spl_object_id($socket_new);
        // $clientObject = new $this->handler->getClientObject();
        // $clientObject->setSocket($socket_new);
        // $clientObject->setSocketId($socket_id);
        // $clientObject->setIdentity();
        $this->clients[$socket_id] = $socket_new;

        $header = socket_read($socket_new, 1024);
        $this->performHandshake($header, $socket_new, $this->host, $this->port);

        socket_getpeername($socket_new, $ip);

        $this->handler->onClientJoin($socket_id);

        $this->consoleLog("JOIN", "New connection ID: $socket_id");

        // $found_socket = array_search($this->socket, $changed);
        unset($changed[$socket_id]);
    }

    public function loop() {
        while($this->execute_next_tick) {
            // Create a temp array of clients
            $changed = $this->clients;
            $null = null;

            socket_select($changed, $null, $null, 0, 10);

            // Check if there are any new connections we need to handle
            if (in_array($this->socket, $changed)) {
                $this->addClient($changed);
            }
            // Server update code should go here
            $this->handler->onEveryGameTick($this->tick);

            // Loop through all socket connections
            foreach($changed as $socket_id => $changed_socket) {
                // Sift through incoming data and then broadcast it to all other connections
                while(socket_recv($changed_socket, $buf, 1024, 0) >= 1) {
                    if (substr($buf, 0, 1) == "{") {
                        $received_text = $buf;
                    } else {
                        $received_text = $this->unmask($buf);
                    }
                    $data = json_decode($received_text, true);
                    if(!$data) {
                        // If there was an error parsing data, skip doing anything else with the message
                        $this->consoleLog("error", "Client ".fmt($socket_id, "e")." sent a malformed message:\n" .fmt($received_text, "i"));
                        continue; 
                    }
                    $broadcast_to_all_players = true;

                    $this->handler->onMessage($socket_id, $data, $broadcast_to_all_players);
                    
                    if ($data && $broadcast_to_all_players) {
                        // $response_text = mask(json_encode($data));
                        $this->sendMessage('responseRebroadcast', $data);
                    }
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
                    $this->handler->onClientLeave($socket_id);
                    $this->consoleLog("disconnect", "Client $socket_id was disconnected");
                }
            }

            $this->tick += 1;
            if($this->tick >= 100_000) {
                $this->tick = 0;
                $this->sendMessage("heartbeat", []);
            }
        }
        socket_close($this->socket);
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
        print("[$type] - $log_message\n");
    }

    private function mask($message) {
        $b1 = 0x80 | (0x1 & 0x0f);
        $length = strlen($message);
        
        if ($length <= 125) {
            $header = pack("CC", $b1, $length);
        } elseif ($length > 125 && $length < 65536) {
            $header = pack("CCn", $b1, 126, $length);
        } elseif ($length >= 65536) {
            $header = pack("CCNN", $b1, 127, $length);
        }
        
        return $header.$message;
    }
    private function unmask($message) {
        $length = ord($message[1]) & 127;
        
        if ($length == 126) {
            $masks = substr($message, 4, 4);
            $data = substr($message, 8);
        }
        elseif ($length == 127) {
            $masks = substr($message, 10, 4);
            $data = substr($message, 14);
        }
        else {
            $masks = substr($message, 2, 4);
            $data = substr($message, 6);
        }
        
        $message = "";
        
        for ($i = 0; $i < strlen($data); $i++) {
            $message .= $data[$i] ^ $masks[$i % 4];
        }
        
        return $message;
    }
    private function performHandshake($received_header, $client_conn, $host, $port) {
        $headers = array();
        $protocol = (stripos($host, "local.") !== false) ? "ws" : "wss";
        $lines = preg_split("/\r\n/", $received_header);
        
        foreach ($lines as $line) {
            $line = chop($line);
            
            if (preg_match("/\A(\S+): (.*)\z/", $line, $matches)) {
                $headers[strtolower($matches[1])] = $matches[2];
            }
        }
        
        $secKey = $headers["sec-websocket-key"];
        $test1 = "258EAFA5-E914-47DA-95CA-C5AB0DC85B11";
        // $test2 = "42EF7650-43AB-4809-9C69-B227C714552F";
        $secAccept = base64_encode(pack("H*", sha1($secKey . $test1)));
        
        
        $upgrade =
            "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
            "Upgrade: WebSocket\r\n" .
            "Connection: Upgrade\r\n" .
            "WebSocket-Origin: $host\r\n" .
            "WebSocket-Location: $protocol://$host:$port/server.php\r\n" .
            "Sec-WebSocket-Version: 13\r\n" .
            "Sec-WebSocket-Accept:$secAccept\r\n\r\n";
            
        socket_write($client_conn, $upgrade, strlen($upgrade));
    }
}