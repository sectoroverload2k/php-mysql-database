<?php
namespace PhpMysqlDatabase;

class MySQL {
    var $db_handle;
    function __construct($db_handle){
        $this->db_handle = $db_handle;
    }
	// Run a query
	function query($statement) {
		$this->result = mysqli_query($this->db_handle, $statement);
		$this->error = mysqli_error($this->db_handle);
		$this->errno = mysqli_errno($this->db_handle);
		return $this->result;
	}
	// Fetch num rows
	function numRows() {
		return mysqli_num_rows($this->result);
	}
	function numFields() {
		return mysqli_num_fields($this->result);
	}
	// Fetch row
	function fetchRow() {
		return mysqli_fetch_array($this->result);
	}
	// Fetch assoc
	function fetchAssoc() {
		return mysqli_fetch_assoc($this->result);
	}
	// Get error
	function error() {
		if(isset($this->error)) {
			return $this->error;
		}
		return null;
	}
	// Get error number
	function errno() {
		if(isset($this->errno)){
			return $this->errno;
		}
		return null;
	}
}

