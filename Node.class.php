<?php

/**
 * Known Bugs:
 * 	-Add Group
 * 	 +Need to check if the group is already in the array
 * 	 +Need to make sure the ID is valid
 * 	 +Need to make sure the ID is an integer
 * 	-Remove Group
 * 	 +Need to check to see if the ID is an integer
 */
class Node {

	//A unique id that is used to reference the node
	private $nodeID;

	//An array of IDs for the comm groups this node is a member of
	private $commGroups = array();

	//Holds the resource ID for the socket this Node belongs to
	private $socket;

	//Keeps track of which handshake this socket uses
	private $handshake=false;

	//Keeps track of which specification this client uses
	private $spec;

	public function __construct($socket) {
		$this->socket = $socket;
		$this->nodeID = self::getUniqID();

		//Get the handshake from the socket
		$getHandshake = array($socket);
		socket_select($getHandshake, $write = null, $except = null, 1);
		//If we don't receive any data from the client. Disconnect it.
		if(count($getHandshake)!=1){
			echo "DID NOT RECEIVE HANDSHAKE!\n";
		}
		else {
			@socket_recv($socket, $handshake, 2048, 0);
			Handshake::findProtocol($this,$handshake);
		}
	}

	public function __destruct() {
		echo "Deleting Node ".$this->nodeID."\n";
	}

	private static function getUniqID() {
		//This counter is used to allocate unique IDs to nodes
		static $idCounter = 0;
		$result = $idCounter;
		$idCounter++;
		return $result;
	}

	//Associates this Node with a group
	public function addCommGroup($id) {
		//Need to add a typecheck to make sure we only allow people to
		//add integers to our groups array.
		//Also need to check if the group ID exists
		$this->commGroups[] = $id;
	}

	public function removeCommGroup($id) {
		//Checks to see if the requested ID is in the array
		$key = array_search($id, $this->commGroups);

		//If the requested ID is in the array then $key will hold its
		//location, otherwise key will be false
		if($key !== false) {
			array_splice($this->commGroups, $key, 1);
		}
	}

	/**
	 * SETTERS AND GETTERS
	 */
	public function getID() {
		return $this->nodeID;
	}
	public function getSocket() {
		return $this->socket;
	}
	public function setHandshake($handshake) {
		$this->handshake = $handshake;
	}
	public function getHandshake() {
		return $this->handshake;
	}
	public function setSpec($spec) {
		$this->spec = new $spec;
	}
	public function getSpec() {
		return $this->spec;
	}
}

?>
