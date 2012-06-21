<?php

/**
 * Description of MySQL
 *
 * @author nlubin
 */
class MySQL extends mysqli  {
    
    private $_result;    
    private $_last_query = null;
    private $_debug;
    /**
     * Start a mysqli connection
     * @param string $server
     * @param string $username
     * @param string $password
     * @param string $dbname 
     */
    function __construct($server, $username, $password, $dbname, $debug = false)    {
        parent::__construct($server, $username, $password, $dbname);
        
        if (mysqli_connect_errno()) {
            printf("Connect failed: %s\n", mysqli_connect_error());
            exit();
        }
        
        $this->debug = $debug;
    }
    
    /**
     * Run a mysql query
     * @param string $query
     * @return mysqli_result 
     */
    function run_query($query)  {
        if($this->debug) DebugLogger::displayLog($query, true, true);
        
        $this->_last_query = $query;
        $this->_result = $this->query($query);
        
        if($this->_result)  {
            if($this->debug) DebugLogger::displayLog(sprintf("Number of rows: %d ", $this->_result->num_rows), false, true);
            return $this->_result;
        }
        else    {
            return false;
        }
    }
    
    /**
     *
     * @param mysqli_result $result
     * @return mixed 
     */
    function get_row($result = null, $fetchby = MYSQLI_BOTH)   {
        if($result == null){
            $result = $this->_result;
        }        
        
        if($result instanceof mysqli_stmt){
            return $this->get_bound_row($result);
        }
        else {
            switch($fetchby){
                case MYSQLI_ASSOC: {
                    if($result && $row = $result->fetch_assoc())  {
                        return $row;
                    }
                    break;
                }
                case MYSQLI_NUM: {
                    if($result && $row = $result->fetch_array(MYSQLI_NUM))  {
                        return $row;
                    }
                    break;
                }
                case MYSQLI_BOTH:
                default: {
                    if($result && $row = $result->fetch_array())  {
                        return $row;
                    }
                    break;
                }
            }
            if($result != null)  {
                $result->free();
            }
            return false;
        }
    }
    
    /**
     *
     * @param mysqli_result $result
     * @return mixed 
     */
    function get_num_rows($result = null)   {
        if($result == null){
            $result = $this->_result;
        }
        if(!$result) return false;
        return $result->num_rows;
    }
    
    /**
     *
     * @return int 
     */
    function get_last_insert_id(){
        return $this->insert_id;
    }
    
    /**
     *
     * @param mysqli_result $result
     * @return bool
     */
    function close_result($result = null)   {
        if($result == null){
            $result = $this->_result;
        }
        if(!$result) return false;
        return $result->close();
    }
    
    /**
     *
     * @return string
     */
    function get_last_error($show_error = true)   {
        $error = $this->error;
        if($show_error){
            $error .= "<pre>\nQuery:\n" . $this->_last_query . "</pre>";
        }
        return $error;
    }
    
    function start_transaction(){

        $this->autocommit(FALSE);
        /* @var $query mysqli_result */
        $query = $this->query("START TRANSACTION");
        
    }
    
    /**
     *
     * @return bool
     */
    function rollback(){
        $rb = parent::rollback();
        return $rb;
    }
    
    /**
     *
     * @return bool
     */
    function commit(){
        $cm = parent::commit();
        return $cm;
    }
        
}
