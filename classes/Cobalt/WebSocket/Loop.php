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
    public $clientArray;

    function __construct(MessageHandler $handler, $hostname, $port) {
        $this->handler = $handler;
        $this->handler->setLoopInstance($this);
        $this->hostname = $hostname;
        $this->port = $port;
        $this->clientArray = [];
    }

    function execute() {
        $this->loop = true;
        
        $socketResource = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
        $this->socketResource = &$socketResource;
        $this->printErrorToCLI($socketResource);

        socket_set_option($socketResource, SOL_SOCKET, SO_REUSEADDR, 1);
        $this->printErrorToCLI($socketResource);

        socket_bind($socketResource, 0, $this->port);
        $this->printErrorToCLI($socketResource);

        socket_listen($socketResource);
        $this->printErrorToCLI($socketResource);
        $null = null;

        $this->clientSocketArray = array($socketResource);
        while($this->loop) {
            $this->handler->onLoopStart();
            $this->printErrorToCLI($socketResource);

            // Duplicate our array of clients
            $newSocketArray = $this->clientSocketArray;

            // Select new socket connections and store them in $newSocketArray
            socket_select($newSocketArray, $null, $null, 0, 10);
            $this->printErrorToCLI($socketResource);

            // If a new connection is made, we want to handle that here
            if (in_array($socketResource, $newSocketArray)) $this->newSocketConnection($newSocketArray, $socketResource);
            
            // Listen for incoming data on all sockets
            $this->listen_on_sockets($newSocketArray);

            // Handle console command input
            $this->listen_to_sdtin();
            $this->handler->onLoopEnd();
        }
        socket_close($socketResource);
    }

    private function newSocketConnection(&$newSocketArray, &$socketResource) {
        $newSocket = socket_accept($socketResource);
        $this->printErrorToCLI($newSocket);

        $header = socket_read($newSocket, 1024);
        $this->printErrorToCLI($newSocket);

        $requestHeaders = $this->handshake($header, $newSocket);
        
        socket_getpeername($newSocket, $clientIpAddress);
        $this->printErrorToCLI($newSocket);

        say("New socket connection: $clientIpAddress");
        $client = new ClientConnection($newSocket, $clientIpAddress, $requestHeaders);

        $this->clientSocketArray[] = $newSocket;  // Push newSocket to client socket array
        $this->clientArray[spl_object_id($newSocket)] = $client;
        $this->handler->onOpen($this->getClientObjectFromResource($newSocket));
        
        $newSocketIndex = array_search($socketResource, $newSocketArray);
        unset($newSocketArray[$newSocketIndex]);

        $this->send(new Message(['message' => "New connection $clientIpAddress", 'command' => ['type' => 'system_notification']]));
    }

    private function handshake($header, $client_socket_resource) {
        $headers = [];
		$lines = preg_split("/\r\n/", $header);
		foreach($lines as $line) {
			$line = chop($line);
			if(preg_match('/\A(\S+): (.*)\z/', $line, $matches)) {
				$headers[$matches[1]] = $matches[2];
			}
		}

		$secKey = $headers['Sec-WebSocket-Key'];
		$secAccept = base64_encode(pack('H*', sha1($secKey . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11')));
		$buffer  = "HTTP/1.1 101 Web Socket Protocol Handshake\r\n" .
		"Upgrade: websocket\r\n" .
		"Connection: Upgrade\r\n" .
		"WebSocket-Origin: $this->hostname\r\n" .
		"WebSocket-Location: ws://$this->hostname:$this->port\r\n".
		"Sec-WebSocket-Accept:$secAccept\r\n\r\n";
		socket_write($client_socket_resource, $buffer, strlen($buffer));
        return $headers;
    }


    private function listen_on_sockets(&$newSocketArray) {
        foreach ($newSocketArray as $i => $client) {
            // Handle disconnect
            while(socket_recv($client, $socketData, 1024, 0) >= 1){
                $socketMessage = $this->unseal($socketData);
                $message = json_decode($socketMessage);
                
                $this->handler->onMessage($this->getClientObjectFromResource($client), $message);
                // $chat_box_message = $chatHandler->createChatBoxMessage($messageObj->chat_user, $messageObj->chat_message);
                // $chatHandler->send($chat_box_message);
                break 2;
            }
            

            // Handle disconnects
            $socketData = @socket_read($client, 1024, PHP_NORMAL_READ);
            if ($socketData === false) { 
                socket_getpeername($client, $client_ip_address);
                $this->handler->onClose($this->getClientObjectFromResource($client));
                // $connectionACK = $chatHandler->connectionDisconnectACK($client_ip_address);
                // $chatHandler->send($connectionACK);
                $newSocketIndex = array_search($client, $this->clientSocketArray);
                unset($this->clientSocketArray[$newSocketIndex]);
                $this->send(new Message(['message' => "$client_ip_address disconnected", 'command' => ['type' => 'system_notification']]));
            }
        }
    }

    private function getClientObjectFromResource($resource):?ClientConnection {
        return $this->clientArray[spl_object_id($resource)];
    }

    private function listen_to_sdtin() {
        $line = trim(fgets(STDIN));
        if(!$line) return;
        if($line[0] === "/") {
            $c = substr($line,1);
            switch($c) {
                case "exit": 
                case "stop":
                case "break":
                    $this->loop = false;
                    break;
                case "state":
                    $clients = count($this->clientSocketArray);
                    say("There are ". fmt($clients) . " client".plural($clients). " connected");
                    break;
                default:
                    say("Unrecognized command", "e");
            }
            $line = null;
            return;
        } 
        
        if($line[0] === "@") {
            $this->send(new Message(["system_message" => $line]));
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
        $clients = 0;
		foreach($this->clientSocketArray as $client) {
			@socket_write($client->socket ?? $client,$message,$messageLength);
            $clients += 1;
		}
        say("Message dispatched to $clients client(s)");
		return true;
	}

    function printErrorToCLI($socket){
        $error = socket_last_error($socket);
        if($error === 0) return;
        $string = socket_strerror($error);
        socket_clear_error($socket);
        // print(fmt("Error: ","e"));
        say($string, "e");
    }

    function __destruct() {
        socket_close($this->socketResource);
    }
}
