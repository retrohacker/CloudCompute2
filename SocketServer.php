<?php
declare(ticks = 1);
//We want to ensure we close all children processes and close all sockets
//before closing the program. So we handle the terminate signals.
$processes;
$isChild=false;
function SIGHANDLE($SIG) {
	global $isChild;
	switch($SIG) {
	case SIGTERM:
		if(!$isChild) {
			echo "The user has requested we terminate this process\n";
		}
		shutdown();
		exit;
	case SIGHUP:
		if(!$isChild) {
			//The user has lost connection to the tty
			echo "The user has lost his connection to the terminal\n";
		}
		shutdown();
		exit;
	case SIGQUIT:
		if(!$isChild) {
			//The user has requested we terminate with a core dump
			echo "The user has requested we terminate with core dump\n";
		}
		shutdown();
		exit;
	case SIGINT:
		if(!$isChild) {
			echo "The user has politely asked we terminate\n";
		}
		shutdown();
		exit;
	default:
		if(!$isChild) {
	        	echo "Some unknown signal has been received\n";	
		}
		shutdown();
	        exit;
	}
}

function shutdown() {
	global $isChild;
	global $processes;
	global $socketArray;
	if(!$isChild) {
		//Check to see if we have setup the master socket
		//if so release it.
		if(isset($socketArray)) {
			foreach($socketArray as $socket) {
				echo "Shutting down ".$socket.": ";
				$success = socket_shutdown($socket);
				socket_close($socket);
				if($success) {
					echo "Success!\n";
				} else {
					echo "FAILED!\n";
				}
			}
		} else {
			echo "Socket Server has not been initialized yet.\n";
		}
		//Wait to ensure all children have terminated
		for($i = 0; $i < count($processes); $i++) {
			echo "Terminating Process ".($i+1).":";
			pcntl_waitpid($processes[$i], $garbage);
			echo " Success!\n";
		}
		echo "Exiting Main Process\n";
	}
}
//Assign the SIGHANDLE function to handle caught signals
pcntl_signal(SIGTERM,"SIGHANDLE");
pcntl_signal(SIGHUP,"SIGHANDLE");
pcntl_signal(SIGQUIT,"SIGHANDLE");
pcntl_signal(SIGINT,"SIGHANDLE");

//Create the master socket listening on all ports
$masterSocket = @socket_create_listen(8080);
echo "Starting Server on port 8080: ";
if(@socket_listen($masterSocket)) {
	echo "Success!\n";
} else {
	die("Error ".socket_last_error()." FAILED!\n");
}
//Prepare to start connecting clients
$socketID=1;
$socketArray[] = $masterSocket;

//Start connecting sockets and receiving data
while(true) {
	$changedSockets = $socketArray;
	if(false===@socket_select($changedSockets,$write=null,$exception=null,null)) {
		echo "Error receiving data\n";
	} else {
		foreach($changedSockets as $recv) {
			if($recv==$masterSocket) {
				echo "New Connection: ";
				$newSocket = socket_accept($recv);
				echo $newSocket."\n";
				$socketArray[] = $newSocket;
			} else {
				//Create a fork to retrieve data
				$pid = pcntl_fork();
				$processes[] = $pid;
				if(!$pid) {
					$isChild = true;
					echo "Received Data from ".$recv."\n";
					$length = socket_recv($recv,$buffer,1024,null);
					if($length===0) {
						//The node is no longer connected
						echo $recv." disconnected\n";
						for($i=0;$i<count($socketArray);$i++) {
							if($socketArray[$i]==$recv) {
								if($i==0) {
									array_shift($socketArray);
								} else{
									array_splice($socketArray,($i),1);
								}
								break;
							}
						}
					}else{
						//We are receiving and processing data
						echo $buffer;
					}
					//Terminate Child Process
					exit;
				} else {
					echo "Parent!\n";
				}
			}
		}
	}
}
//Create 3 threads for testing signal handling.
for($i = 0; $i < 3; $i++) {
	$pid = pcntl_fork();
	$processes[] = $pid;
	if(!$pid) {
		unset($processes);
		$isChild = true;
		switch($i) {
			case 0:
				echo "Starting Thread 1\n";
				sleep(1);
				echo "Exiting Thread 1\n";
				exit;
			case 1:
				echo "Starting Thread 2\n";
				sleep(2);
				echo "Exiting Thread 2\n";
				exit;
			case 2:
				echo "Starting Thread 3\n";
				sleep(3);
				echo "Exiting Thread 3\n";
				exit;
			default:
				echo "Woah!\n";
		}
	}
}	
?>
