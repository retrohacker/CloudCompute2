<?php
class Handshake{
	static function findProtocol($node, $headers){
		if(preg_match("/Sec-WebSocket-Version: (.*)\r\n/", $headers, $match)){
			$version = $match[1];
		}
		else {
			return false;
		}
		
		switch($version){
		case 8: case 13:
			$node->setSpec("RFC6455");
			$node->setHandshake(true);
			self::RFC6455($node, $headers);
			break;
		}
	}
	static function RFC6455($node, $headers){
		if(preg_match("/GET (.*) HTTP/", $headers, $match)) {
			$root = $match[1];
		}
		if(preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $headers, $match)) {
			$key = $match[1];
		}

		$acceptKey = $key.'258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
		$acceptKey = base64_encode(sha1($acceptKey, true));

		$upgrade = "HTTP/1.1 101 Switching Protocols\r\n".
			"Upgrade: websocket\r\n".
			"Connection: Upgrade\r\n".
			"Sec-WebSocket-Accept: $acceptKey".
			"\r\n\r\n";

		socket_write($node->getSocket(), $upgrade);
		$node->setHandshake(true);
		return true;
	}

	static function Hybi10($node, $headers){
		if(preg_match("/GET (.*) HTTP/", $headers, $match)) {
			$root = $match[1];
		}
		if(preg_match("/Sec-WebSocket-Key: (.*)\r\n/", $headers, $match)) {
			$key = $match[1];
		}

		$acceptKey = $key.'258EAFA5-E914-47DA-95CA-C5AB0DC85B11';
		$acceptKey = base64_encode(sha1($acceptKey, true));

		$upgrade = "HTTP/1.1 101 Switching Protocols\r\n".
			"Upgrade: websocket\r\n".
			"Connection: Upgrade\r\n".
			"Sec-WebSocket-Accept: $acceptKey".
			"\r\n\r\n";

		socket_write($node->getSocket(), $upgrade);
		$node->setHandshake(true);
		return true;
	}
}
?>
