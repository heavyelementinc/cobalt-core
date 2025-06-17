<?php

namespace Cobalt\WebSockets\Traits;

use Cobalt\Websockets\Exceptions\SocketException;
use Socket;

trait SocketInit {
    private string $host = __APP_SETTINGS__['domain_name'];
    private int    $port = __APP_SETTINGS__['Websocket_default_port'];
    
    private Socket|false $socket;
    public array $clients = [];
    
    public function initSocket() {
        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($this->socket, 0, $this->port);
        socket_listen($this->socket);
        $this->clients[] = $this->socket;
        $this->consoleLog("init", "Socket open on port $this->port");
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
        try {
            $this->handler->onClientJoin($socket_id);
        } catch (SocketException $e) {
            $this->sendError($e);
        }

        $this->consoleLog("JOIN", "New connection ID: $socket_id");

        // $found_socket = array_search($this->socket, $changed);
        unset($changed[$socket_id]);
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
        $secAccept = base64_encode(pack("H*", sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
        
        $upgrade =
            "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
            "Upgrade: WebSocket\r\n" .
            "Connection: Upgrade\r\n" .
            "WebSocket-Origin: $host\r\n" .
            "WebSocket-Location: $protocol://$host/socket/\r\n" .
            "Sec-WebSocket-Version: 13\r\n" .
            "Sec-WebSocket-Accept:$secAccept\r\n\r\n";
            
        socket_write($client_conn, $upgrade, strlen($upgrade));
    }
}