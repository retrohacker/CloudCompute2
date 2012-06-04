<?php
	$socketArray;
	$masterSocket = socket_create_listen(8080);
	if(socket_listen($masterSocket)) {
		echo "Server Started on All Interfaces on ".$masterSocket."\n";
	}else{
		die("UNABLE TO CONNECT!\n");
	}
	while($connection = socket_accept($masterSocket)) {
		echo "Received Connection: ".$connection."\n";
		$success = socket_select($getHandshake = (array)$connection,$write = null,$read = null,null);	
		if($success!=1) {
			echo "DID NOT RECEIVE HANDSHAKE\n";
		} else {
			@socket_recv($connection, $handshake,2048,0);
			echo $handshake."\n";
		}
	}
?>
