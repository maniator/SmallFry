<?php
/**
 * Description of SQLQuery
 *
 * @author nlubin
 */
class SQLQuery {
    /**
     *
     * @var MySQL 
     */
    protected $dbHandle;
    protected $mysqlResult = null;
    protected $modelTable = null;

    /** Connects to database **/
    function connect($address, $account, $pwd, $name) {
        $this->dbHandle = new MySQL($address, $account, $pwd, $name);
        if ($this->dbHandle) {
            if ($this->dbHandle->select_db($name)) {
                return 1;
            }
            else {
                return 0;
            }
        }
        else {
            return 0;
        }
    }

    /** Disconnects from database **/
    function disconnect() {
        if (@$this->dbHandle->close() != 0) {
            return 1;
        }  else {
            return 0;
        }
    }

    function selectAll() {
        $select_query = "SELECT * FROM `%s`;";
    	$select_query = sprintf($select_query, $this->modelTable);
    	return $this->queryit($select_query);
    }

    function selectById($id, $idName = 'id') {
        $select_query = "SELECT * FROM `%s` WHERE `%s` = '%s';";
        $select_query = sprintf($select_query, $this->modelTable, $idName, $this->dbHandle->real_escape_string($id));
    	return $this->queryit($select_query, 1);
    }

    function select($options){
        $select_query = "SELECT * FROM %s %s %s";
        $where_section = array();
        $order_section = array();
        extract($options, EXTR_PREFIX_ALL, 'opts_');   //do not overwrite current variables
        if(isset($opts_conditions) && is_array($opts_conditions)){
            $where_section = $ops_conditions;
        }
        if(isset($opts_orderby) && is_array($opts_orderby)){
            $order_section = $opts_orderby;
        }
        $where = "";
        $order = "";
        if(count($where_section) > 0){
            $where .= "WHERE ". implode(" AND ", $where_section);
        }
        if(count($order_section) > 0){
            $order .= "ORDER BY ". implode(", ", $order_section);
        }
        $select_query = sprintf($select_query, $this->modelTable, $where, $order);
        return $this->queryit($select_query);
    }
    
    /** Custom SQL Query **/
    function queryit($query, $singleResult = 0) {

        $this->mysqlResult = $this->dbHandle->run_query($query);
        if($this->mysqlResult)  {
            if(preg_match("/select/i",$query) || preg_match("/show/i",$query)) {
                $result = array();
                $table = array();
                $field = array();
                $tempResults = array();

                while ($row = $this->dbHandle->get_row($this->mysqlResult)) {
                    foreach($row as $key=>$field)   {
                        $tempResults[$key] = $field;
                    }
                    if ($singleResult == 1) {
                        return $tempResults;
                    }
                    array_push($result,$tempResults);
                }
                return($result);
            }

            return ($this->mysqlResult ? true : false);
        }
        else {
            return false;
        }
    }

    /** Get number of rows **/
    function getNumRows() {
        return $result->num_rows;
    }

    /** Free resources allocated by a query **/
    function freeResult() {
        if($this->mysqlResult != null)
            return $this->mysqlResult->free();
    }

    /** Get error string **/
    function getError() {
        return $this->dbHandle->get_last_error(false);
    }
}
