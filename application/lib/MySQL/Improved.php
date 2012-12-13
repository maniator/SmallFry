<?php
namespace SmallFry\lib; 
/**
 * Description of MySQL
 *
 * @author nlubin
 */
class MySQL_Improved extends \mysqli implements MySQL_Interface  {
    
    private $_result;    
    private $_last_query = null;
    private $_db_name;
    /**
     * Start a mysqli connection
     * @param string $server
     * @param string $username
     * @param string $password
     * @param string $dbname 
     */
    function __construct($server, $username, $password, $dbname, $debug = false)    {
        parent::__construct($server, $username, $password, $dbname);
        $this->_db_name = $dbname;
        if (mysqli_connect_errno()) {
            printf("Connect failed: %s\n", mysqli_connect_error());
            exit();
        }
        
        $this->debug = $debug;
    }
    
    function getSchemaName(){
        return $this->_db_name;
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
        
        if($result instanceof \mysqli_stmt){
            return $this->get_bound_row($result, $fetchby);
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
    
    function get_bound_row(&$result, $fetchby)    {
        if($result && $row = $result->fetch_assoc())  {
            return $row;
        }
        return false;
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
    

    public function prepare($query)
    {
        if($this->debug) DebugLogger::displayLog($query, true, true);
        $stmt = new mysqliStmt_Extended($this, $query, $this->debug);
        return $stmt;
    }
        
}

class mysqliStmt_Extended extends \mysqli_stmt implements stmt_extended
{
    protected $varsBound = false;
    protected $results;
    protected $debug = false;

    public function __construct($link, $query, $debug = false)
    {
        parent::__construct($link, $query);
        $this->debug = $debug;
    }
    
    public function log_bind_param($params)    {
        $prepare = array();
        foreach($params as $key=>$option)   {
            $prepare[] = &$params[$key];    //set as by reference
        }
        if($this->debug) DebugLogger::displayLog("bind_param " . print_r($prepare, true), true, true);
        call_user_func_array(array($this, "bind_param"), $prepare);
    }

    public function fetch_assoc()
    {
        // checks to see if the variables have been bound, this is so that when
        //  using a while ($row = $this->stmt->fetch_assoc()) loop the following
        // code is only executed the first time
        if (!$this->varsBound) {
            $meta = $this->result_metadata();
            if($meta)   {
                while ($column = $meta->fetch_field()) {
                    // this is to stop a syntax error if a column name has a space in
                    $columnName = str_replace(' ', '_', $column->name);
                    $bindVarArray[] = &$this->results[$columnName];
                }
                call_user_func_array(array($this, 'bind_result'), $bindVarArray);
            }
            $this->varsBound = true;
        }
        $fetch = $this->fetch();
        
        if ($fetch != null) {
            foreach ($this->results as $k => $v) {
                $results[$k] = $v;
            }
            
            return $results;
        } else {
            return null;
        }
    }
}