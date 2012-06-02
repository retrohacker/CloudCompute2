<?php
require('mysqlCred');

/**
 * This class contains a single data sturcture for a project.
 * Keep in mind a single project can have multiple data sturctures.
 * A data structure will contain a set of related information all
 * of the same data type (similar to an array). This data will be
 * lumped together into "chunks" of a predetermined size. These
 * chunks will contain n elements each. When a node connects
 * to the server, it will be given k chunks where k is proporitonal
 * to the number of clients currently connected to the server.
 *
 * An example: Lets say you have a list of 2000 prime numbers. You
 * choose a chunk size to be 20. This will create 100 chunks of
 * data each with 20 members. If 5 nodes are currently connected
 * to the server, the server will dish out 20 chunks of data to
 * each node. When one node drops from the server, the 20 chunks it
 * was using will be redistributed to the remaining 4 nodes so that
 * each node receives an extra 5 chunks of data for a total of 25
 * chunks. If at this point another node connects, that node is given
 * 4 'chunks' from each of the current 4 nodes.
 */
class Data {
	//The number of elements to store in a single chunk of data
	private $chunkSize;

	//A temporary holder for a chunck being built by the algorithm
	//that hasn't reached chunkSize yet (and its length).
	private $tempChunk;
	private $tempChunkLength;

	//The name this data set will be represented by in the database
	//and its associated project
	private $dataName;
	private $projectName;

	//The total number of chunks in the database
	private $totalChunks;

	//This keeps an open connection the the sql database.
	//It is static so that every data set does not need its own exclusive
	//connection in order to function.
	private static $mysqlConnection;
	
	//This is used for the initial query of the database, if the database
	//has already been initalized, we will store the rows in this variable.
	private $databaseRows;

	/**
	 * Here we request a data set. If this data set already exists
	 * in the database we will import all of the information for it
	 * otherwise we will create a new data set.
	 *
	 * params:
	 * $sizeOfChunk: The total number of elements that will be stored
	 * 	in each chunk of data.
	 * $dataName: The name that will be associated with this data set,
	 * 	it will be stored under this name in the database.
	 * $projectName: The name of the project this data belongs to, it
	 * 	will be stored under this name in the database.
	 */
	public function __construct($sizeOfChunk,$dataName,$projectName) {

		//These are declared in the file mysqlCred
		global $mysql_address, $mysql_user_name, $mysql_password;
		
		//Establish a connection to the mysql database if this hasnt
		//been done yet
		if(!self::$mysqlConnection) {
			self::$mysqlConnection = mysql_connect($mysql_address,$mysql_user_name,$mysql_password);
			
			if(!mysql_query("CREATE DATABASE IF NOT EXISTS cloudCompute",self::$mysqlConnection)){ //make sure that we have a database to select.
				echo "\nProblem creating database: ".mysql_error();
			}
			
			if(mysql_select_db("cloudCompute",self::$mysqlConnection)){ //connect to the database
				echo "\nConnected to database.";
			}
			else{
				die('\nUnable to connect to database: '.mysql_error());
			}

			if(!mysql_query("CREATE TABLE IF NOT EXISTS chunk( 
			id int NOT NULL AUTO_INCREMENT,
			chunk varchar(255), 
			otherThing varchar(50), 
			blah char(4),
			PRIMARY KEY (id)
			)",self::$mysqlConnection)){echo "\nProblem creating table: ".mysql_error();}
			
			//Initialize Global Variables for the data set
			$this->chunkSize = $sizeOfChunk;
			$this->dataName = $dataName;
			$this->projectName = $projectName;
			$this->tempChunk = '';
			$this->tempChunkLength = 0;
			$this->totalChunks = $this->initializeChunks();
			$this->largestInt = 0;

			//Echo out global variables for debugging
			echo "\nProject Name: ".$this->projectName;
			echo "\nData Set: ".$this->dataName;
			echo "\nChunk Size: ".$this->chunkSize;
			echo "\nTotal Chunks: ".$this->totalChunks;
		}
	}

	/**
	 * This will be used to get the total number of chunks already in
	 * the database for this project. It also initializes the global
	 * variable for the chunks.
	 */
	private function initializeChunks() {
		$query = "SELECT * FROM chunks WHERE projectID='".$this->projectName."'";
		$this->databaseRows = mysql_query($query,self::$mysqlConnection);
		if(!$this->databaseRows) {
			echo "\nUNABLE TO QUERY DATABASE: ".mysql_error();
		}
		return mysql_num_rows($this->databaseRows); 
	}

	/**
	 * This function adds an element to the data set. Once the number
	 * of elements inserted into the data set reaches the number of
	 * elements allocated per chunk, it uploads the elements to the
	 * database.
	 */
	public function add($data)
	{
		//Append the current element to the chunk of data in memory
		$this->tempChunk = $this->tempChunk.$data.',';	
		$this->tempChunkLength++;

		//If the number of chunks temporarily being held in memory is
		//equal to the number of elements allocated per chunk, upload
		//it to the database
		if($this->tempChunkLength==$this->chunkSize){
			//NEED TO REMOVE! SHOULD NOT RETURN A VALUE
			$result = $this->tempChunk;
			$query = "INSERT INTO chunks (clientID,projectID,dataSet,chunk) VALUES (-1,'".$this->projectName."','".$this->dataName."','".substr($this->tempChunk,0,-1)."')";
			echo "\n".$query;
			if(!mysql_query($query)) {
				echo "\n Query Failed!: ".mysql_error();
			}
			$this->totalChunks++;
			$this->tempChunk = '';
			$this->tempChunkLength = 0;
			//NEED TO REMOVE! SHOULD NOT RETURN A VALUE
			return $result;
		}
		else
			//NEED TO REMOVE! SHOULD NOT RETURN A VALUE
			return null;
	}

	/**
	 * This function returns the total number of chunks currently being
	 * held in the database.
	 */
	public function totalChunks() {
		return $this->totalChunks;
	}

	/**
	 * When the data object is declared and initialized, if it imports
	 * elements from an already existing project in the database into
	 * memory, this will return one "chunk" of data every time this
	 * function is called until the data has been distributed.
	 */
	public function getNextChunk() {
		$row = mysql_fetch_array($this->databaseRows);
		return $row['chunk']; 
	}

	/**
	 * This function returns the total number of objects in a single chunk
	 * of data for the current data object
	 */
	public function getChunkSize() {
		return $this->chunkSize;
	}
}
?>
