<?php

namespace PhpMysqlDatabase;

class MySQL
{
    public $db_handle;
    private $result;
    private $error;
    private $errno;
    public function __construct($db_handle)
    {
        $this->db_handle = $db_handle;
    }
    // Run a query
    public function query($statement, $params = [])
    {
		if(!empty($params)){
			// Prepare the statement
			$this->stmt = mysqli_prepare($this->db_handle, $statement);
			if(!$this->stmt) {
				$this->error = mysqli_error($this->db_handle);
				$this->errno = mysqli_errno($this->db_handle);
				return false;
			}

			// Bind the parameters if provided
			$types = $this->getParamTypes($params);
			mysqli_stmt_bind_param($this->stmt, $types, ...$params);

			// execute the query
			if(!mysqli_stmt_execute($this->stmt)){
				$this->error = mysqli_stmt_error($this->stmt);
				$this->errno = mysqli_stmt_errno($this->stmt);
				return false;
			}

			$this->result = mysqli_stmt_get_result($this->stmt);

		} else {

			$this->result = mysqli_query($this->db_handle, $statement);

	        $this->error = mysqli_error($this->db_handle);
	        $this->errno = mysqli_errno($this->db_handle);
		}

        return $this->result;
    }

	// Helper method to determine parameter types
	private function getParamTypes($params)
	{
		$types = '';
		foreach($params as $param){
			if(is_int($param)){
				$types .= 'i';
			} elseif(is_double($param)){
				$types .= 'd';
			} elseif(is_string($param)){
				$types .= 's';
			} else {
				$types .= 'b'; // blob
			}
		}
		return $types;
	}

	// Fetch a single row // backwards compat
	public function fetch()
	{
		return $this->fetchAssoc();
	}

    // Fetch num rows
    public function numRows()
    {
        return mysqli_num_rows($this->result);
    }
    public function numFields()
    {
        return mysqli_num_fields($this->result);
    }
    // Fetch row
    public function fetchRow()
    {
        return mysqli_fetch_array($this->result);
    }
    // Fetch assoc
    public function fetchAssoc()
    {
        return mysqli_fetch_assoc($this->result);
    }
    // Fetch all
    public function fetchAll()
    {
        $return = [];
        while($row = $this->fetch()) {
            $return[] = $row;
        }
        return $return;
    }
    // Get error
    public function error()
    {
        if(isset($this->error)) {
            return $this->error;
        }
        return null;
    }
    // Get error number
    public function errno()
    {
        if(isset($this->errno)) {
            return $this->errno;
        }
        return null;
    }
}
