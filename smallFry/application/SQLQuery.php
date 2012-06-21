<?php
/**
 * Description of SQLQuery
 *
 * @author nlubin
 */
class SQLQuery {

    protected $dbHandle;
    protected $dbName;
    protected $dbs = array(
        'primary' => null, 
        'secondary' => null,
    );
    protected static $handle = null;
    protected static $secondaryHandle = null;
    protected $mysqlResult = null;
    protected $modelTable = null;

    /** Connects to database **/
    function connect($address, $account, $pwd, $name, $secondary = false) {

        $handleToUse = &self::$handle;
        if($secondary) {
            $this->dbs['secondary'] = $name;
            $handleToUse = &self::$secondaryHandle;
        }
        else    {
            $this->dbs['primary'] = $name;
        }
        
        if($secondary && $address === null) {
            $handleToUse = &self::$secondaryHandle;
        }
        
        if($handleToUse === null){
            $handleToUse = new MySQL($address, $account, $pwd, $name, $this->CONFIG->get('DEBUG_QUERIES'));
        }
        $this->useSecondaryHandle($secondary);
        if ($handleToUse) {
            if ($handleToUse->select_db($name)) {
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
        
    function useSecondaryHandle($secondary = true){
        if($secondary)  {
            $this->dbName = $this->dbs['secondary'];
            $this->dbHandle = self::$secondaryHandle;
        }
        else    {
            $this->dbName = $this->dbs['primary'];
            $this->dbHandle = self::$handle;
        }
        
        $this->modelColumns = $this->getColumnNames();
        
        $this->printDebug($secondary);

    }
    
    function printDebug($secondary)   {
        DebugLogger::displayLog(get_class($this). " Secondary? " . ($secondary ? "yes" : "no"));
        DebugLogger::displayLog("This Table: ". $this->modelTable);

        if ($result = $this->dbHandle->query("SELECT DATABASE()")) {
            $row = $result->fetch_row();
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

    function selectById($id, $idName = 'id') {
        return $this->select(array(
            'conditions'=>array(
                sprintf("`%s` = '%s'", $idName, $id)
            )
        ));
    }

    function select($options = array()){
        $select_query = "SELECT %s \nFROM %s as `%s` %s %s %s %s;";
        $where_section = array();
        $order_section = array();
        $group_section = array();
        $join_section = array();
        $join_query = "";
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
                        foreach($joinOn as $onS)    {
                            $key = "AND";
                            $condition = $onS;
                            if(is_array($onS))   {
                                $key = isset($onS['type']) ? $onS['type'] : "AND";
                                $condition = $onS['condition'];
                            }
                            $where .= sprintf(" %s %s\n", ($firstOn ? "" : $key), $condition);
                            $firstOn = false;
                        } 
                    }
                    $asTable = (isset($join['asTable'])? $join['asTable'] : $join['model']);
                    $join_query .= sprintf("\nLEFT JOIN %s as `%s` ON %s", $model_obj->getModelTable(), $asTable, $joinOn);
                }
            } 
        }
        //Custom Fields
        if(isset($opts_fields) && is_array($opts_fields) && count($opts_fields) > 0){
            foreach($opts_fields as $key=>&$fieldName){
                if(trim($fieldName) == "*") {   //Remove errant *
                    unset($opts_fields[$key]);
                    continue;
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
        $select_query = sprintf($select_query, $columns, $this->getModelTable(), $this->modelName, $join_query, $where, $group, $order);
        
        return $this->queryit($select_query, 0);
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
    
    function getColumnNames($inQuery = true, $withModel = false, $modelName = false) {
      $query = sprintf("SHOW COLUMNS FROM %s", sprintf("`%s`.`%s`", $this->dbName, $this->modelTable));
      $modelName = $modelName ? $modelName : get_class($this);
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
    
    function getModelObject($modelName){
        if(isset($this->$modelName)){        
            $this->currentModel = $this->$modelName;
        }
        if(!isset($this->$modelName) && isset($this->currentModel->$modelName) ){
            $this->currentModel = $this->currentModel->$modelName;
        }
        return isset($this->currentModel) ? $this->currentModel : false;
    }
}
