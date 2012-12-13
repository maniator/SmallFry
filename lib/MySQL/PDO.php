<?php
namespace SmallFry\lib; 

/**
 * Description of MySQL_PDO
 *
 * @author nlubin
 */
class MySQL_PDO extends \PDO implements MySQL_Interface {
    
    public $_db_name;
    public $debug;
    public $_last_query;
    public $_result;
    
    function __construct($server, $username, $password, $dbname, $debug = false)    {
        parent::__construct("mysql:host={$server};dbname={$dbname}", $username, $password, array(
    	    \PDO::ATTR_STATEMENT_CLASS => array('SmallFry\lib\PDOStmt_Extended', array($this)),
    	    \PDO::ATTR_EMULATE_PREPARES => FALSE,
    	));
        $this->_db_name = $dbname;
        $this->debug = $debug;
    }
    
    public function getSchemaName() {
    	return $this->_db_name;
    }
    
    public function run_query($query)	{
        if($this->debug) DebugLogger::displayLog($query, true, true);
        $this->_last_query = $query;
        $this->_result = &$this->query($query);
        if($this->_result)  {
            if($this->debug) DebugLogger::displayLog(sprintf("Number of rows: %d ", $this->_result->num_rows), false, true);
            return $this->_result;
        }
        else    {
            return false;
        }
    }
    
    public function get_row($result = null, $fetchby = MYSQLI_BOTH){
    	$fetch = $result->fetch();
    	return $fetch;
    }
    public function get_bound_row(&$result, $fetchby){
	    return $result->fetch();
    }
    public function get_num_rows($result = null){
	    return 0; // must do fetch all and count to get num of rows with PDO
    }
    public function get_last_insert_id(){
	    return $this->lastInsertId();
    }
    public function close_result($result = null){
	    return $result->closeCursor();	
    }
    public function get_last_error($show_error = true){
        $error = $this->errorInfo();
	    $error_message = $error[2]; //error message location for PDO
        if($show_error){
            $error_message .= "<pre>\nQuery:\n" . $this->_last_query . "</pre>";
        }
        return $error_message;
    }
    public function start_transaction(){
    	if(!$this->inTransaction())  {
    	    return $this->beginTransaction();
    	}
    	else	{
    	    return false;
    	}
    }
    
    public function commit()	{
    	if($this->inTransaction())  {
    	    return parent::commit();
    	}
    	else	{
    	    return false;
    	}
    }
    
    public function __get($var)	{
	if($var === 'error')	{   //to handle the `error` variable
	    $error = $this->errorInfo();
	    return $error[2];
	}
	elseif($var === 'insert_id')	{   //get last insert id
	    return $this->lastInsertId();
	}
	else return $this->$var;
    }
}

class PDOStmt_Extended extends \PDOStatement implements stmt_Extended	{
    public $dbh;
    protected function __construct($dbh) {
        $this->dbh = $dbh;
    }

    public function bind_param()    {
    	$prepareOptions = func_get_args();
    	$opts = str_split($prepareOptions[0]);
    	unset($prepareOptions[0]);
    	foreach($prepareOptions as $key=>$option)	{
    	    switch ($opts[$key-1]):
    		case "i":
    		    $this->bindValue($key, $option, \PDO::PARAM_INT);
    		    break;
    		default:
    		    $this->bindValue($key, $option, \PDO::PARAM_STR);
    		    break;
    	    endswitch;
    	}
    	return true;
    }
    
    public function close(){
	    $this->closeCursor();
    }
    
    public function __get($var)	{
    	if($var === 'error')	{   //to handle the `error` variable
    	    $error = $this->errorInfo();
    	    return $error[2];
    	}
    	elseif($var === 'insert_id')	{   //get last insert id
    	    return $this->dbh->lastInsertId();
    	}
    	elseif($var === 'affected_rows')	{   //get number of affected rows
    	    return $this->rowCount();
    	}
    	else return $this->$var;
    }
}