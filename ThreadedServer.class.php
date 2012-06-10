<?php
	$socketID = 1;
	$socketArray;
	$masterSocket = socket_create_listen(8080);
	if(socket_listen($masterSocket)) {
		echo "Server Started on All Interfaces on ".$masterSocket."\n";
	}else{
		die("UNABLE TO CONNECT!\n");
	}
	$isChild = false;
	while(true) {
		$connection = socket_accept($masterSocket);
		$success = socket_select($getHandshake = (array)$connection,$write = null,$read = null,null);	
		if($success!=1) {
		} else {
			$socketArray[] = array($socketID++,$connection,0);
			$index = count($socketArray)-1;
			socket_recv($socketArray[$index][1], $handshake,2048,0);
			echo "Received ".$socketArray[$index][1]." with ID ".$socketArray[$index][0]."\n";	

			//Create fork for listening to the socket
			$pid = pcntl_fork();
			if($pid) {
				//We are the parent	
				$socketArray[$index][2] = $pid;
			}
			else {
				//We are the child
				//Grab Everything that is imporant for running the thread
				$isChild = true;
				echo "Created Thread ".$pid." For Listening To new Socket\n";
				$assignedSocket[]= $socketArray[$index][1];
				//Delete everything else
				unset($socketArray);
				unset($pid);
				unset($connection);
				unset($success);
				unset($index);
				//Begin listening to socket
				while(true) {
					socket_select($assignedSocket,$write[]=null,$except[]=null,null);
				}
			}
		}
		
		sleep(2);
	}
?>
