<?php
/*
Database class

This class will be used to interface between the database
and the rest of your code.
*/

namespace PhpMysqlDatabase;

use PhpMysqlDatabase\MySQL;
use RestRouter\Exceptions\DBErrorException;
use RestRouter\Exceptions\ForeignKeyException;

class Database
{
    protected $server;
    protected $username;
    protected $password;
    protected $database;

    public function __construct($config = [])
    {
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
    public function connect()
    {
        $status = $this->db_handle = mysqli_connect($this->server, $this->username, $this->password);
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
    public function disconnect()
    {
        if($this->connected == true) {
            mysqli_close($this->db_handle);
            return true;
        } else {
            return false;
        }
    }
    // Run a query
    public function query($statement, $params = [])
    {
        $mysql = new MySQL($this->db_handle);
        $mysql->query($statement, $params);
        $this->set_error($mysql->error());
        if($mysql->error()) {
            switch($mysql->errno()) {
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
    
    /**
     * Prepare a SQL statement for execution
     * 
     * @param string $statement The SQL query to prepare
     * @return MySQL|null Returns MySQL object on success or null on failure
     */
    public function prepare($statement)
    {
        $mysql = new MySQL($this->db_handle);
        $mysql->prepare($statement);
        $this->set_error($mysql->error());
        if($mysql->error()) {
            throw new DBErrorException($mysql->error());
            return null;
        } else {
            return $mysql;
        }
    }
    
    /**
     * Bind parameters to a prepared statement
     * 
     * @param MySQL $stmt The prepared statement object
     * @param mixed $params Parameter or array of parameters to bind
     * @param string $types Optional string of types for the parameters
     * @return MySQL The prepared statement object
     */
    public function bind_param($stmt, $params, $types = null)
    {
        $stmt->bind_param($params, $types);
        $this->set_error($stmt->error());
        if($stmt->error()) {
            throw new DBErrorException($stmt->error());
        }
        return $stmt;
    }
    
    /**
     * Execute a prepared statement
     * 
     * @param MySQL $stmt The prepared statement object
     * @return MySQL The prepared statement object with results
     */
    public function execute($stmt)
    {
        $stmt->execute();
        $this->set_error($stmt->error());
        if($stmt->error()) {
            switch($stmt->errno()) {
                // delete fk constraint
                case 1451: // fk delete constraint
                    throw new ForeignKeyException('Cannot delete or update this record. Other data depends on this record.');
                case 1452: // fk add/update constraint
                    throw new ForeignKeyException('Cannot add or update this record. Other information is required.');
                    break;
                default:
                    throw new DBErrorException($stmt->error());
            }
        }
        return $stmt;
    }
    // Gets the first column of the first row
    public function get_one($statement)
    {
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
    public function set_error($message = null)
    {
        global $TABLE_DOES_NOT_EXIST, $TABLE_UNKNOWN;
        $this->error = $message;
        if(strpos($message, 'no such table')) {
            $this->error_type = $TABLE_DOES_NOT_EXIST;
        } else {
            $this->error_type = $TABLE_UNKNOWN;
        }
    }
    // Return true if there was an error
    public function is_error()
    {
        return (!empty($this->error)) ? true : false;
    }
    // Return the error
    public function get_error()
    {
        return $this->error;
    }
    public function insert_id()
    {
        return mysqli_insert_id($this->db_handle);
    }
}
