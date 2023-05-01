<?php

namespace Cobalt\WebSocket;

/**
 * The loop runs as an application in the background. Its job is to recieve 
 * incoming WebSocket messages, abstract the low level shit, and route each
 * WebSocket message to the appropraite MessageHandler callback.
 * @package Cobalt\WebSocket
 */
class Loop {
    protected MessageHandler $handler;
    protected $hostname;
    protected $port;
    protected $loop = false;
    protected $socketResource;
    public $clientSocketArray;

    function __construct(MessageHandler $handler, $hostname, $port) {
        $this->handler = $handler;
        $this->handler->setLoopInstance($this);
        $this->hostname = $hostname;
        $this->port = $port;
    }

    function execute() {
        $this->loop = true;
        
        $socketResource = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        socket_set_option($socketResource, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_bind($socketResource, 0, $this->port);
        socket_listen($socketResource);
        $null = null;

        $this->clientSocketArray = array($socketResource);
        while($this->loop) {
            $newSocketArray = [...$this->clientSocketArray];
            socket_select($newSocketArray, $null, $null, 0, 10);
            
            // If a new connection is made, we want to handle that here:
            if (in_array($socketResource, $newSocketArray)) $this->newSocketConnection($newSocketArray, $socketResource);
            
            // Listen for incoming data on all sockets
            $this->listen_on_sockets($newSocketArray);

            // $this->listen_to_sdtin();
        }
    }

    private function newSocketConnection(&$newSocketArray, &$socketResource) {
        $newSocket = socket_accept($socketResource);

        $header = socket_read($newSocket, 1024);
        $requestHeaders = $this->handshake($header, $newSocket);
        
        socket_getpeername($newSocket, $clientIpAddress);
        $client = new Client($newSocket, $clientIpAddress, $requestHeaders);

        $this->clientSocketArray[] = $client;  // Push newSocket to client socket array
        $this->handler->onOpen($client);
        
        $newSocketIndex = array_search($socketResource, $newSocketArray);
        unset($newSocketArray[$newSocketIndex]);

        $this->send(new Message(['message' => "New connection $clientIpAddress", 'command' => ['type' => 'system_notification']]));
    }

    private function handshake($header, $newSocket) {
        $requestHeaders = [];
		$lines = preg_split("/\r\n/", $header);
		foreach($lines as $line) {
			$line = chop($line);
			if(preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
				$headers[$matches[1]] = $matches[2];
			}
		}

		$secKey = $requestHeaders['Sec-WebSocket-Key'];
		$secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
		$buffer  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
		"Upgrade: websocket\r\n" .
		"Connection: Upgrade\r\n" .
		"WebSocket-Origin: $this->hostname\r\n" .
		"WebSocket-Location: wss://$this->hostname:$this->port\r\n".
		"Sec-WebSocket-Accept:$secAccept\r\n\r\n";
		socket_write($newSocket, $buffer, strlen($buffer));
        return $requestHeaders;
    }


    private function listen_on_sockets(&$newSocketArray) {
        foreach ($newSocketArray as $client) {
            // Handle disconnect
            while(socket_recv($client->socket, $socketData, 1024, 0) >= 1){
                $socketMessage = $this->unseal($socketData);
                $message = json_decode($socketMessage);
                
                $this->handler->onMessage($client, $message);
                // $chat_box_message = $chatHandler->createChatBoxMessage($messageObj->chat_user, $messageObj->chat_message);
                // $chatHandler->send($chat_box_message);
                break 2;
            }
            

            // Handle disconnects
            $socketData = @socket_read($client, 1024, PHP_NORMAL_READ);
            if ($socketData === false) { 
                socket_getpeername($client, $client_ip_address);
                $this->handler->onClose($client);
                // $connectionACK = $chatHandler->connectionDisconnectACK($client_ip_address);
                // $chatHandler->send($connectionACK);
                $newSocketIndex = array_search($client, $this->clientSocketArray);
                unset($this->clientSocketArray[$newSocketIndex]);
                $this->send(new Message(['message' => "$client_ip_address disconnected", 'command' => ['type' => 'system_notification']]));
            }
        }
    }

    

    private function listen_to_sdtin() {
        $line = trim(fgets(STDIN));
        if(!$line) return;
        switch($line) {
            case "exit": 
            case "stop":
            case "break":
                $this->loop = false;
                break;
            case "state":
                $clients = count($this->clientSocketArray);
                say("There are ". fmt($clients) . " client".plural($clients). " connected");
                break;
            case $line[0] === "@":
                $this->send(new Message(["system_message" => $line]));
                break;
        }
        $line = null;
        return;
    }

    function seal($socketData) {
		$b1 = 0x80 | (0x1 & 0x0f);
		$length = strlen($socketData);
		
		if($length <= 125)
			$header = pack('CC', $b1, $length);
		elseif($length > 125 && $length < 65536)
			$header = pack('CCn', $b1, 126, $length);
		elseif($length >= 65536)
			$header = pack('CCNN', $b1, 127, $length);
		return $header.$socketData;
	}

    function unseal($socketData) {
		$length = ord($socketData[1]) & 127;
		if($length == 126) {
			$masks = substr($socketData, 4, 4);
			$data = substr($socketData, 8);
		}
		elseif($length == 127) {
			$masks = substr($socketData, 10, 4);
			$data = substr($socketData, 14);
		}
		else {
			$masks = substr($socketData, 2, 4);
			$data = substr($socketData, 6);
		}
		$socketData = "";
		for ($i = 0; $i < strlen($data); ++$i) {
			$socketData .= $data[$i] ^ $masks[$i%4];
		}
		return $socketData;
	}

    function send(Message $messageContents) {
        $message = $this->seal(json_encode($messageContents->redacted()));
		$messageLength = strlen($message);
		foreach($this->clientSocketArray as $client) {
			@socket_write($client->socket ?? $client,$message,$messageLength);
		}
		return true;
	}
}
