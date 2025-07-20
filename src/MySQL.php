<?php

namespace PhpMysqlDatabase;

class MySQL
{
    public $db_handle;
    private $result;
    private $error;
    private $errno;
    private $stmt;
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
			if(is_null($param)){
				$types .= 's'; // NULL values as strings
			} elseif(is_int($param)){
				$types .= 'i';
			} elseif(is_double($param) || is_float($param)){
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
    
    /**
     * Prepare a statement for execution
     * 
     * @param string $query The SQL query to prepare
     * @return bool|mysqli_stmt Returns statement object on success or false on failure
     */
    public function prepare($query)
    {
        $this->stmt = mysqli_prepare($this->db_handle, $query);
        if(!$this->stmt) {
            $this->error = mysqli_error($this->db_handle);
            $this->errno = mysqli_errno($this->db_handle);
            return false;
        }
        
        return $this->stmt;
    }
    
    /**
     * Bind parameters to the prepared statement
     * 
     * @param mixed $params Parameter or array of parameters to bind
     * @param string $types Optional string of types for the parameters
     * @return bool True on success or false on failure
     */
    public function bind_param($params, $types = null)
    {
        if(!$this->stmt) {
            $this->error = 'No prepared statement exists';
            return false;
        }
        
        // Convert to array if single parameter
        if(!is_array($params)) {
            $params = [$params];
        }
        
        // Auto-detect types if not provided
        if($types === null) {
            $types = $this->getParamTypes($params);
        }
        
        // Bind parameters
        if(!mysqli_stmt_bind_param($this->stmt, $types, ...$params)) {
            $this->error = mysqli_stmt_error($this->stmt);
            $this->errno = mysqli_stmt_errno($this->stmt);
            return false;
        }
        
        return true;
    }
    
    /**
     * Execute a prepared statement
     * 
     * @return bool|mysqli_result Returns result object on success or false on failure
     */
    public function execute()
    {
        if(!$this->stmt) {
            $this->error = 'No prepared statement exists';
            return false;
        }
        
        // Execute the statement
        if(!mysqli_stmt_execute($this->stmt)) {
            $this->error = mysqli_stmt_error($this->stmt);
            $this->errno = mysqli_stmt_errno($this->stmt);
            return false;
        }
        
        // Get the result
        $this->result = mysqli_stmt_get_result($this->stmt);
        
        return $this->result;
    }
    
    /**
     * Close the prepared statement
     * 
     * @return bool True on success or false on failure
     */
    public function close_stmt()
    {
        if(!$this->stmt) {
            return true; // No statement to close
        }
        
        $result = mysqli_stmt_close($this->stmt);
        $this->stmt = null;
        return $result;
    }
}
