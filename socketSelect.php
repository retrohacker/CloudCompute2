<?php
$masterSocket = socket_create_listen(8080);
if(@socket_listen($masterSocket)) {
	echo "Started Server on 8080\n";
} else {
	die("Unable to bind to socket!\n");
}
$socketArray[] = $masterSocket;
while(true) {
	$changedSockets = $socketArray;
	if(false===socket_select($changedSockets,$write=null,$exception=null,1)) {
		echo "Error receiving data\n";
	} else {
		foreach($changedSockets as $recv) {
			if($recv==$masterSocket) {
				echo "New Connection\n";
				$newSocket = socket_accept($recv);
				echo $newSocket."\n";
				$socketArray[] = $newSocket;
			} else {
				echo "Received Data from ".$recv."\n";
				$length = socket_recv($recv,$buffer,1024,null);
				if($length===0) {
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
					echo $buffer."\n";
				}
			}
		}
	}
}
socket_shutdown($masterSocket);
socket_close($masterSocket);
?>
