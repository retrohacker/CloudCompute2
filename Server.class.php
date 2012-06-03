<?php
/**
 * The Server class handles receiving connections, managing communication
 * groups, creating nodes, etc.
 */
class Server {
	//TEMORARILY SETTING THESE BECAUSE CONFIG FILE IS NOT WORKING
	private $mainLoopDelay = 10;
	private $pollSocketDelay = 20;
	//The address of the server
	private $address;

	//The port the server is connecting to
	private $port;
	
	//The socket for the main connection
	private $masterSocket;

	/**
	 * These 2 queues and 1 array are used to keep track of what "state"
	 * sockets are in within the program.
	 * 
	 * unallocated sockets: Sockets that are have connected to the server
	 * 	but have yet to be assigned to a node
	 * allocated sockets: Sockets that have connected to the server and
	 * 	have been assigned to a node. This is an array.
	 * disconnected sockets: Sockets that have disconnected from the server
	 * 	but are still assigned to nodes.
	 *
	 * When a client first connects, their socket is added to the
	 * unnallocatedSockets queue. Once the program assigns the socket
	 * to a node, its socket gets moved to allocatedSockets array. Then
	 * when a client disconnects from the server, it's socket gets moved
	 * from which ever queue it belongs to based on the above criteria to
	 * the disconnectedSocket. It stays in the disconnectedSocket queue
	 * until the node it has been assigned to has been deleted.
	 */
	private $unallocatedSockets = array();
	private $allocatedSockets = array();

	//This array stores all the active nodes
	private $nodeArray = array();

	//This array stores all of the communication groups being used by the
	//server to communicate with multiple nodes at the same time
	private $commGroupsArray = array();

	/**
	 * Requests are instructions to the server to carry out a specific
	 * function such as send a message, add a node to a group, etc.
	 * */
	//Requests that have been received by the server but not yet processed
	private $pendingRequests = array();
	
	//Requests that have been processed by the server. They remain here
	//until the function/project that opened the request checks to see if
	//it has been completed
	private $completedRequests = array();

	public function __construct($address, $port) {
		$this->address = $address;
		$this->port = $port;

		//Create the main socket that the server will use
		$this->masterSocket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		socket_set_option($this->masterSocket, SOL_SOCKET, SO_REUSEADDR, 1);
		socket_bind($this->masterSocket, $this->address, $this->port);
		socket_listen($this->masterSocket, SOMAXCONN);
		//We prevent the master socket from blocking so when we poll it
		//in the future we don't have to worry about the program
		//becomming unresponsive
		socket_set_nonblock($this->masterSocket);

		echo "Server started on {$this->address}:{$this->port}\n";
	}

	/**
	 * Temporarily adds a newly connected socket to the queue of
	 * unallocated sockets until it is converted to a node.
	 */
	private function connect($socket) {
		//Pretty sure we can make a more efficent queue. Possibly
		//in a later version of the product
		array_unshift($this->unallocatedSockets, $socket);
		global $verbose;
		if($verbose) {
			echo "Socket ".$socket." has been added to the array";
			echo " unallocated sockets which now contains:\n";
			for($i=0; $i<sizeof($this->unallocatedSockets);$i++) {
				echo $this->unallocatedSockets[$i].", ";
			}
			echo "\n";
		}
	}

	/**
	 * Upgrades a simple socket connection to a node class
	 */
	private function createNode($socket) {
		$newNode = new Node($socket);
		//Checks to see if the handshake was successful
		if($newNode->getHandshake()) {
			$this->nodeArray[] = $newNode;
			global $verbose;
			if(true) {
				echo "Created Node ".$newNode->getID()." From ".$newNode->getSocket();
				echo " and added it to the array nodesArray:\n";
				for($i=0;$i<sizeof($this->nodeArray);$i++) {
					echo $this->nodeArray[$i]->getID().":".$this->nodeArray[$i]->getSocket().", ";
				}
				echo "\n";
			} else {
				echo "Created Node With ID: ".$newNode->getID()."\n";
			}
			return true;
		}
		//If not we disconnect the socket
		else
		{
			echo "Failed to create node. Closing Socket\n";
			//We shutdown the socket to ensure it gets closed
			if(!socket_shutdown($socket)) {
				echo "Unable to shutdown socket!/n";
			}
			socket_close($socket);
			//force release of memory
			unset($socket);
			unset($newNode);
			return false;
		}
	}

	/**
	 * Will take up to n sockets from the unallocatedNodes queue 
	 * and upgrade them to Nodes
	 */
	private function upgradeSockets($n) {
		//We check to see if there are less unallocated sockets then
		//what was requested to be upgraded
		$totalUnallocated = count($this->unallocatedSockets);
		if($n > $totalUnallocated) {
			//if so we simply upgrade the entire array
			$n = $totalUnallocated;
		}

		for($i=0;$i<$n;$i++) {
			//Remove the socket from the unallocatedSockets queue
			$socket = array_pop($this->unallocatedSockets);
			//Attempt to create a node
			$success = $this->createNode($socket);
			//Add the socket to the allocatedSockets array if
			//successful
			if($success)
			{
				$this->allocatedSockets[] = $socket;
				global $verbose;
				if($verbose) {
					echo "allocatedSockets now contains: ";
					for($i=0;$i<count($this->allocatedSockets);$i++) {
						echo $this->allocatedSockets[$i].",";
					}
					echo"\n";
				}
			}
		}
	}
	
	/**
	 * Finds the node assigned to a socket. If no node is using the socket
	 * this function returns false
	 */
	private function getNodeBySocket($socket) {
		foreach($this->nodeArray as $node) {
			if($node->getSocket() == $socket)
				return $node;
		}
		return false;
	}

	/**
	 * Finds and returns a node by its ID. If no node has the specified ID
	 * this function will return false
	 */
	private function getNodeById($id) {
		foreach($this->nodeArray as $node) {
			if($node->getID() == $id)
				return $node;
		}
		return false;
	}

	/**
	 * Receives encoded text frame(s) and sends them over the socket to each node.
	 * @param $node = the node to send the data to.
	 * @param $text 
	*/
	public function send($node, $text) {
		$encoded = $node->getSpec()->encode($text);
		
		if(!is_array($encoded)){
			socket_write($node->getSocket(), $encoded, strlen($encoded));
		}
		else{
			for($i=0, $y=count($encoded);$i<$y;$i++){
				socket_write($node->getSocket(), $encoded[$i], strlen($encoded[$i]));
			}
		}
	}
	
	/**
	 * Sends text to an array of group members.
	*/
	public function send_group($groupId, $text) {
		//to do.
	}
	
	public function process($node, $text) {
		echo $text;
		$action = $node->getSpec()->decode($text);
		echo $action;
		switch($action){
			case "hello": $this->send($client,"short string!"); break;
			default: $this->send($client,$action); break;
		}
	}

	/**
	 * This function checks all of the sockets connected to the server to
	 * see if there is any data waiting to be received, if so it opens up
	 * a request token for the node that received it.
	 */
	public function receiveData() {
		$changedSockets = $this->allocatedSockets;
		//Checks if there is any new data returning immediately
		@socket_select($changedSockets, $write = null, $except = null, 0);
		//If any data was received process it
		foreach($changedSockets as $socket) {
			$node = $this->getNodeBySocket($socket);
			if($node) {
				$bytes = @socket_recv($socket, $data, 2048, 0);
				//Do stuff with received data
			}
		}
	}

	private $pollStartTime;
	/**
	 * This function goes through and attempts to contact nodes. If unable
	 * get a response from a node, it disconnects it.
	 */
	public function pollSockets() {
		if(!isset($this->pollStartTime)) {
			$this->pollStartTime = time();
		}
		if(time()-$this->pollStartTime>=$this->pollSocketDelay) {
			$this->pollStartTime = time();
			for($i=0;$i<count($this->nodeArray);$i++) {
				$encoded = $this->nodeArray[$i]->getSpec()->encode("ping");
				$result = @socket_send($this->nodeArray[$i]->getSocket(),$encoded,strlen($encoded),null);
				if($result===false)
					$this->droppedNode($i);
			}
		}
	}

	/**
	 * When the n-th node in the nodeArray timesout, this function is called
	 * to delete it.
	 */
	public function droppedNode($n) {
		$socket = $this->nodeArray[$n]->getSocket();
		if($n==0)
			$node = array_shift($this->nodeArray);
		else if($n<count($this->nodeArray))
			$node = array_splice($this->nodeArray,$n-1,1);
		else
			$node = null;	
		$n = $this->getSocketIndex($socket);
		if($n==0)
			$socket = array_shift($this->allocatedSockets);
		else if($n<count($this->allocatedSockets))
			$socket = array_splice($this->allocatedSockets,$n-1,1);
		else
			$socket = null;

		for($i=0;$i<count($socket);$i++)
			echo "Deleting ".count($socket)." Socket and ".count($node)." Node.\n";
		if(!is_null($socket[0])) {
			@socket_shutdown($socket[0]);
			socket_close($socket[0]);
			unset($socket);
		}
		if(!is_null($node)) {
			unset($node);
		}
	}

	/**
	 * Returns the index of a socket in allocatedSockets. If the socket is not
	 * found in the array, this function returns -1
	 * (can be optimized as array should be sorted)
	 */
	public function getSocketIndex($socket) {
		for($i = 0; $i < count($this->allocatedSockets);$i++) {
			if($socket === $this->allocatedSockets[$i]) {
				return $i;
			}
		}
		return -1;
	}

	/**
	 * Polls the Master Socket to receive any new connections. If there are
	 * any new connections we add them to the unallocated clients list
	 */
	public function getNewConnections() {
		while(($newConnection = @socket_accept($this->masterSocket)) !== false) {
			echo "Connected: ".$newConnection."\n";
			$this->connect($newConnection);
		}
	}

	public function run() {
		while(true) {
			$this->getNewConnections();
			$this->upgradeSockets(1);
			$this->receiveData();
			$this->pollSockets();
			//Test the resource consumption of script.. This should cause a
			//heartbeat in resource consumption.
			sleep($this->mainLoopDelay);
		}
	}
}
?>
