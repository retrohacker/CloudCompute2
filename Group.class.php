<?php

/**
 * Known Bugs:
 * 	-Add Node
 * 	 +Need to check if the Node is already in the array
 * 	 +Need to make sure the ID is valid
 * 	 +Need to make sure the ID is an integer
 * 	-Remove Node
 * 	 +Need to check to see if the ID is an integer
 */
class Group {

	//The unique ID of this group
	private $groupID;

	//The nodes that are assigned to this group
	private $nodes = array();

	public function __construct($id) {
		$this->groupID = $id;
	}

	public function addNode($id) {
		$this->nodes[] = $id;
	}

	public function removeNode($id) {
		//Checks to see if the requested ID is in the array
		$key = array_search($id, $this->nodes);

		//If the requested ID is in the array then $key will hold its
		//location, otherwise key will be false
		if($key !== false) {
			array_splice($this->nodes, $key, 1);
		}
	
	}
}
?>
