<?php
namespace SmallFry\lib; 
/**
 * Description of SQLQuery
 *
 * @author nlubin
 */
class SQLQuery {

    /** @var MySQL_Interface **/
    protected $dbHandle;
    protected $dbName;
    protected $dbs = array(
        'primary' => null, 
        'secondary' => null,
    );

    protected $mysqlResult = null;
    protected $modelTable = null;
    /** @var Config **/
    protected $CONFIG;
    /** @var MySQL_Interface **/
    protected $firstHandle;
    /** @var MySQL_Interface **/
    protected $secondHandle;

    protected $usePDO = false;

    /** Connects to database **/    
    function connect(MySQL_Interface $firstHandle, MySQL_Interface $secondHandle = null)   {
        $this->firstHandle = $firstHandle;
        $this->secondHandle = $secondHandle;
        $this->useSecondaryHandle(false);
    
	    $this->usePDO = $this->CONFIG->get('DB_NEW');
    }
        
    function useSecondaryHandle($secondary = true){
        
        if($secondary)  {
            $this->dbName = $this->secondHandle->getSchemaName();
            $this->dbHandle = $this->secondHandle;
        }
        else    {
            $this->dbName = $this->firstHandle->getSchemaName();
            $this->dbHandle = $this->firstHandle;
        }
        
        $this->modelColumns = $this->getColumnNames();
        
        $this->printDebug($secondary);

    }

    function printDebug($secondary)   {
        DebugLogger::displayLog(get_class($this). " Secondary? " . ($secondary ? "yes" : "no"));
        DebugLogger::displayLog("This Table: ". $this->modelTable);
	    $this->usingPDO = $this->CONFIG->get('DB_NEW');
        if ($result = $this->dbHandle->query("SELECT DATABASE() as db")) {
	    if($this->usingPDO)    {
		    $row = $result->fetch(\PDO::FETCH_BOTH);
	    }
	    else	{
		    $row = $result->fetch_row();
	    }
            DebugLogger::displayLog(sprintf("Default database is %s.", $row[0]));
            $result->close();
        }

        DebugLogger::displayLog($this->modelColumns, true);
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
        return $this->select(); //empty select() is the same as a select all
    }

    function selectById($id, $idName = 'id', $single = 0, $type = "i") {
        return $this->select(array(
            'conditions'=>array(
                sprintf("`%s` = ?", $idName)
            )
        ), $single, array(
            $type, $id
        ));
    }
    
    function createSelectQuery($options = array()) {
        $select_query = "SELECT %s \nFROM %s as `%s` %s %s %s %s \n%s;";
        $where_section = array();
        $order_section = array();
        $group_section = array();
        $join_section = array();
        $join_query = "";
        $extra = "";
        $columns = implode(", \n    ", $this->modelColumns);
        $import = extract($options, EXTR_PREFIX_ALL, 'opts');   //do not overwrite current variables
        if(isset($opts_conditions) && is_array($opts_conditions)){
            $where_section = $opts_conditions;
        }
        if(isset($opts_orderby) && is_array($opts_orderby)){
            $order_section = $opts_orderby;
        }
        if(isset($opts_groupby) && is_array($opts_groupby)){
            $group_section = $opts_groupby;
        }

        if(isset($opts_join) && is_array($opts_join)){
            $join_section = $opts_join;
        }
        $where = $order = $group = "";
        if(count($where_section) > 0){
            $where .= "\nWHERE ";
            $firstWhere = true;
            foreach($where_section as $whereS)    {
                $key = "AND";
                $condition = $whereS;
                if(is_array($whereS))   {
                    $key = isset($whereS['type']) ? $whereS['type'] : "AND";
                    $condition = $whereS['condition'];
                }
                $where .= sprintf(" %s %s\n", ($firstWhere ? "" : $key), $condition);
                $firstWhere = false;
            } 
        }
        if(count($order_section) > 0){
            $order .= "\nORDER BY ". implode(", ", $order_section);
        }
        if(count($group_section) > 0){
            $group .= "\nGROUP BY ". implode(", ", $group_section);
        }
        if(count($join_section) > 0){
            foreach($join_section as $join){
                if(isset($join['model']) && isset($join['on']) && ($model_obj = $this->getModelObject($join['model']))){
                    $join_cols = $model_obj->getModelColumns((isset($join['asTable'])? $join['asTable'] : false));
                    $columns .= ", \n    ".implode(", \n    ", $join_cols);
                    $joinOn = $join['on'];
                    if(is_array($joinOn)){
                        $firstOn = true;
                        $joinCondition = "";
                        foreach($joinOn as $onS)    {
                            $key = "AND";
                            $condition = $onS;
                            if(is_array($onS))   {
                                $key = isset($onS['type']) ? $onS['type'] : "AND";
                                $condition = $onS['condition'];
                            }
                            $joinCondition .= sprintf(" %s %s\n", ($firstOn ? "" : $key), $condition);
                            $firstOn = false;
                        } 
                        $joinOn = $joinCondition;
                    }
                    $asTable = (isset($join['asTable'])? $join['asTable'] : $join['model']);
                    $join_query .= sprintf("\nLEFT JOIN %s as `%s` ON %s", $model_obj->getModelTable(), $asTable, $joinOn);
                }
            } 
        }
        if(isset($opts_limit)){
            $extra .= sprintf("LIMIT %d\n", $opts_limit);
            if(isset($opts_offset)){
                $extra .= sprintf("OFFSET %d\n", $opts_offset);
            }
        }
        //Custom Fields
        if(isset($opts_fields) && is_array($opts_fields) && count($opts_fields) > 0){
            foreach($opts_fields as $key=>&$fieldName){
                if(trim($fieldName) == "*") {
                    //add all columns from this model:
                    $fieldName = implode(", \n    ", $this->modelColumns);
                    continue;
                }
                list($modelName, $fName) = explode(".",$fieldName) + array("a", "b");
                if($fName == "*")   {
                    $modelObject = $this->getModelObject($modelName, true);
                    if($modelObject && $modelObject->getModelTable(false) !== 'appmodels')    {
                        $cols = $modelObject->getModelColumns();
                        $fieldName = implode(", \n    ", $cols);
                        continue;
                    }
                    else    {
                        $foundit = false;
                        foreach($join_section as $join){
                            if(isset($join['asTable']) && $modelName == $join['asTable'])   {
                                $modelObject = $this->getModelObject($join['model']);
                                if($modelObject)    {
                                    $cols = $modelObject->getColumnNames(true, false, $join['asTable']);
                                    $fieldName = implode(", \n    ", $cols);
                                    $foundit = true;
                                    break;
                                }
                            }
                        }
                        if($foundit)    {
                            continue;
                        }
                    }
                }
                if(is_numeric($key)){
                    $fieldName = "$fieldName as `$fieldName`";
                }
                else{
                    $fieldName = "$fieldName as `$key`";
                }
            }
            $columns = implode(", \n    ", $opts_fields);
        }
        $select_query = sprintf($select_query, $columns, $this->getModelTable(), $this->modelName, $join_query, $where, $group, $order, $extra);
        $this->lastQuery = $select_query;
        return $select_query;
    }

    function select($options = array(), $single = 0, $prepareOptions = array()) {
        if($single === 1)   {
            $options["limit"] = 1;
        }
        $select_query = $this->createSelectQuery($options);
        $statement = $this->dbHandle->prepare($select_query);
        if($statement)  {
            if(count($prepareOptions) > 1)  {   //make sure at least one thing needs to be bound
                if($this->usePDO)   {

                    $opts = str_split($prepareOptions[0]);
                    unset($prepareOptions[0]);
                    $count = 0;
                    foreach($prepareOptions as $key=>$option)	{
                        switch ($opts[$count++]):
                            case "i":
                            $statement->bindValue($key, $option, \PDO::PARAM_INT);
                            break;
                            default:
                            $statement->bindValue($key, $option, \PDO::PARAM_STR);
                            break;
                        endswitch;
                    }
                }
                else	{
                    call_user_func_array(array($statement, 'log_bind_param'), array($prepareOptions));  //passed by reference
                }
            }

            $return = $this->run_prepared_query($statement, $single);
	        return $return;
        }
        else    {
            return $this->getError();
        }
               
    }
    
    function prepared_select($options = array(), $prepareOptions = array(), $single = 0){
        return $this->select($options, $single, $prepareOptions);
    }
    
    function getLastQuery(){
        return $this->lastQuery;
    }
    
    function run_prepared_query(stmt_Extended &$statement, $singleResult = 0){
                        
        if($statement)  {
            $statement->execute();
            if(preg_match("/select/i",$this->lastQuery) || preg_match("/show/i",$this->lastQuery)) {
                $result = array();
                $table = array();
                $field = array();
                $tempResults = array();
                while ($row = $this->dbHandle->get_row($statement)) {
                    foreach($row as $key=>$field)   {
                        $fieldSplit = explode('.',$key);
                        if(!is_numeric($key) && count($fieldSplit) > 1){
                            $fieldSplit = explode('.',$key);
                            $modelName = $fieldSplit[0];
                            unset($fieldSplit[0]); $key = implode($fieldSplit);
                            $tempResults[$modelName][$key] = $field;
                        }
                        else {
                            $tempResults['nonmodel'][$key] = $field;
                        }
                    }
                    if ($singleResult == 1) {
                        return $tempResults;
                    }
                    array_push($result, $tempResults);
                }
                $statement->close();
                if($singleResult > 1)   {
                    $result = array_slice($result, 0, $singleResult); //limit results
                }
                return $result;
            }

            return ($statement ? true : false);
        }
        else {
            return false;
        }
    }
    
    /** Custom non-prepared SQL Query **/
    function queryit($query, $singleResult = 0) {
                
        $this->mysqlResult = $this->dbHandle->run_query($query);
        $this->lastQuery = $query;
        
        if($this->mysqlResult)  {
            if(preg_match("/select/i",$query) || preg_match("/show/i",$query)) {
                $result = array();
                $table = array();
                $field = array();
                $tempResults = array();

                while ($row = $this->dbHandle->get_row($this->mysqlResult)) {
                    foreach($row as $key=>$field)   {
                        $fieldSplit = explode('.',$key);
                        if(!is_numeric($key) && count($fieldSplit) > 1){
                            $fieldSplit = explode('.',$key);
                            $modelName = $fieldSplit[0];
                            unset($fieldSplit[0]); $key = implode($fieldSplit);
                            $tempResults[$modelName][$key] = $field;
                        }
                        else {
                            $tempResults['nonmodel'][$key] = $field;
                        }
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
    function getNumRows($result) {
        return $this->dbHandle->get_num_rows($result);
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
    
    function isModelColumn($columnName) {
        $query = sprintf("SHOW COLUMNS FROM %s WHERE Field = ?", sprintf("`%s`.`%s`", $this->dbName, $this->modelTable));
        $columns = array();
        $statement = $this->dbHandle->prepare($query);
        if($statement)  {
            $statement->log_bind_param(array(
                "s", $columnName
            ));

            $return = $this->run_prepared_query($statement, 1);
            return (bool) count($return);
        }
        return false;
    }
    
    function getColumnNames($inQuery = true, $withModel = false, $modelName = false) {
      $query = sprintf("SHOW COLUMNS FROM %s", sprintf("`%s`.`%s`", $this->dbName, $this->modelTable));
      $modelName = $modelName ? $modelName : $this->modelName;
      $columns = array();
      $result = $this->dbHandle->run_query($query);
      if($result){
          while($row = $this->dbHandle->get_row($result, MYSQLI_ASSOC)){
              $columns[] = $row['Field'];
          };
          if($inQuery){
              foreach($columns as &$col){
                  $col = "`{$modelName}`.`$col` as `{$modelName}.$col`";
              }
          }
          elseif($withModel){
              foreach($columns as &$col){
                  $col = "{$modelName}.$col";
              }
          }
      }
      return $columns;
    }
    
    function getModelObject($modelName, $returnIfFalse = false){
        return AppModelFactory::buildModel($modelName, $this->CONFIG, $this->firstHandle, $this->secondHandle, true);
    }
}
