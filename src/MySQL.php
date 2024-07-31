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
    public function query($statement)
    {
        $this->result = mysqli_query($this->db_handle, $statement);
        $this->error = mysqli_error($this->db_handle);
        $this->errno = mysqli_errno($this->db_handle);
        return $this->result;
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
        while($row = $this->fetchAssoc()) {
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
