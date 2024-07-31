<?php

// $Id: class.database.php 342 2006-05-03 08:10:15Z stefan $

/*
Database class

This class will be used to interface between the database
and the Website Baker code
*/

// Stop this file from being accessed directly

namespace PhpMysqlDatabase;

use PhpMysqlDatabase\MySQL;
use RestRouter\Exceptions\DBErrorException;
use RestRouter\Exceptions\ForeignKeyException;

class Database {
  protected $server;
  protected $username;
  protected $password;
  protected $database;

	function __construct($config = []) {
    $this->server = $config['server'];
    $this->username = $config['username']; 
    $this->password = $config['password'];
    $this->database = $config['database'];

		// Connect to database
		$this->connect();
		// Check for database connection error
		if($this->is_error()) {
			die(new DBErrorException($this->get_error()));
		}
	}
	// Connect to the database
	function connect(){
    $status = $this->db_handle = mysqli_connect($this->server,$this->username,$this->password);
		if(mysqli_error($this->db_handle)) {
			$this->connected = false;
			$this->error = mysqli_error($this->db_handle);
		} else {
			if(!mysqli_select_db($this->db_handle, $this->database)) {
				$this->connected = false;
				$this->error = mysqli_error($this->db_handle);
			} else {
				$this->connected = true;
			}
		}
		return $this->connected;
	}
	// Disconnect from the database
	function disconnect() {
		if($this->connected==true) {
			mysqli_close($this->db_handle);
			return true;
		} else {
			return false;
		}
	}
	// Run a query
	function query($statement) {
		$mysql = new MySQL($this->db_handle);
		$mysql->query($statement);
		$this->set_error($mysql->error());
		if($mysql->error()) {
			switch($mysql->errno()){
				// delete fk constraint
				case 1451: // fk delete constraint
					throw new ForeignKeyException('Cannot delete or update this record. Other data depends on this record.');
				case 1452: // fk add/update constraint
					throw new ForeignKeyException('Cannot add or update this record. Other information is required.');
					break;
				default:
					throw new DBErrorException($mysql->error());
			}

			return null;
		} else {
			return $mysql;
		}
	}
	// Gets the first column of the first row
	function get_one($statement) {
		$fetch_row = mysqli_fetch_row(mysqli_query($this->db_handle, $statement));
		$result = $fetch_row[0];
		$this->set_error(mysqli_error($this->db_handle));
		if(mysqli_error($this->db_handle)) {
			return null;
		} else {
			return $result;
		}
	}
	// Set the DB error
	function set_error($message = null) {
		global $TABLE_DOES_NOT_EXIST, $TABLE_UNKNOWN;
		$this->error = $message;
		if(strpos($message, 'no such table')) {
			$this->error_type = $TABLE_DOES_NOT_EXIST;
		} else {
			$this->error_type = $TABLE_UNKNOWN;
		}
	}
	// Return true if there was an error
	function is_error() {
		return (!empty($this->error)) ? true : false;
	}
	// Return the error
	function get_error() {
		return $this->error;
	}
	function insert_id() {
		return mysqli_insert_id($this->db_handle);
	}
}


