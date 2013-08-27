<?php
namespace SmallFry\lib; 
/**
 * Description of AppModel
 *
 * @author nlubin
 */
class AppModel extends SQLQuery {
    /**
     *
     * @var string 
     */
    protected $modelName;
    /**
     *
     * @var string 
     */
    protected $modelTable;
    /**
     *
     * @var stdClass 
     */
    protected $posts;
    /**
     *
     * @var array 
     */
    protected $using;
    protected $relationships;

    /**
     *
     * @var Config
     */
    protected $CONFIG;
    /**
     *
     * @var MySQL_Interface
     */
    protected $firstHandle;
    /**
     *
     * @var MySQL_Interface
     */
    protected $secondHandle;

    /**
     * @param Config $CONFIG
     * @param MySQL_Interface $firstHandle
     * @param MySQL_Interface $secondHandle 
     */
    function __construct(Config $CONFIG, MySQL_Interface $firstHandle, MySQL_Interface $secondHandle = null) {
        
        $this->CONFIG = $CONFIG;
        $this->firstHandle = $firstHandle;
        $this->secondHandle = $secondHandle;
        
        $className = explode("\\", get_class($this));
        
        $this->modelName = $className[count($className) - 1];
        $this->modelTable = strtolower(pluralize($this->modelName));

        $this->connect($firstHandle, $secondHandle);
        
        $this->parsePosts();
        $this->setUpUsing();
        $this->init();
    }

    function init() {}
    
    protected final function setUpUsing()   {
        /** Using Models **/
        if(is_array($this->using)){
            foreach($this->using as $usingModel){
		$this->addUseModel($usingModel);
            }
        }
    }
    
    public function setModelName($name)  {
	    $this->modelName = $name;
    }
    
    public final function getModelName()    {
	    return $this->modelName;
    }
    
    public final function addUseModel($usingModel)   {
        $this->$usingModel = &AppModelFactory::buildModel($usingModel, $this->CONFIG, $this->firstHandle, $this->secondHandle);
    }
    
    public final function setModelTable($tableName){
        $this->modelTable = $tableName;
        $this->modelColumns = $this->getColumnNames();
    }
    
    public final function getMySQLObject(){
        return $this->dbHandle;
    }
    
    public final function getPosts(){
        return $this->posts;
    }
    
    public final function getModelColumns($modelName = false){
        if($modelName)  return $this->getColumnNames (true, false, $modelName);
        else    return $this->modelColumns;
    }
    
    public final function getModelTable($withDb = true){
        if($withDb) {
            return sprintf("`%s`.`%s`", $this->dbName, $this->modelTable);
        }
        else    {
            return $this->modelTable;
        }
    }
    
    protected final function parsePosts(){
        
        if(!(!isset($_POST) || count($_POST) == 0)){
            foreach($_POST as $key=>&$post){
                $this->posts[$key] = $this->realEscapeObject($post); //escape all randomness
            }
        }
        $this->posts = (object) $this->posts;
        
    }
    
    protected final function realEscapeObject($q) {
        if(!$this->usePDO)   {
            if(is_array($q)) {
            foreach($q as $k => $v) {
                $q[$k] = $this->realEscapeObject($v); //recurse into array
            }
            }
            elseif(is_string($q))   {
            $q = $this->dbHandle->real_escape_string($q);
            }
            return $q;
        }
        else	{
            return $q;
        }
    }
    
    public final function camelToWords($str){
        return _uncamel($str);
    }

    public function changeField($fieldName, $value, $rowId = null, $idName = "id")   {
        return $this->save(array($fieldName=>$value), $rowId, $idName);
    }

    public function save($values, $rowId = null, $idName = "id", $returnErrors = false)   {
        if(!$this->usePDO)   {
            echo "You cannot use the save function without PDO!";
            exit;
        }
        $set = array();
        $prepare = array();
        foreach($values as $fieldName=>$value)  {
            $columns = $this->getColumnNames(false);
            if(in_array($fieldName, $columns)){
                if(is_array($value) && count($value) === 2)    {
                    $set[] = sprintf("`%s` = %s", $fieldName, $value[0]);
                    $prepare[] = $value[1];
                }
                elseif(!is_array($value))    {
                    $set[] = sprintf("`%s` = ?", $fieldName);
                    $prepare[] = $value;
                }
            }
            else    {
                exit("`$fieldName` does not exist.");
            }
        }
        if(is_bool($rowId)) {
            //this allows for less parameters when doing an insert
            $returnErrors = $rowId;
            $rowId = null;
        }
        if($rowId === null) {
            $saveQuery = sprintf("
                INSERT INTO %s
                SET %s
            ", $this->getModelTable(), implode(", \n", $set));
        }
        else {
            $saveQuery = sprintf("
                UPDATE %s
                SET %s
                WHERE `%s` = ?
            ", $this->getModelTable(), implode(", \n", $set), $idName);
            $prepare[] = $rowId;
        }

        $statement = $this->dbHandle->prepare($saveQuery);

        if($statement){
            if(!$statement->execute($prepare)){
                if($returnErrors)   {
                    return "Row statement save error: {$statement->error}";
                }
                else    {
                    return "Some statement error happened";
                }
            }
            $this->last_insert_id = $this->dbHandle->insert_id;
            $statement->close();
        }
        else {
            if($returnErrors)   {
                return "Row save error: {$this->dbHandle->error}";
            }
            else    {
                return "Some error happened";
            }
        }
        return true;
    }

    public function __call($name, $args)    {
        //find all
        $findAllFormat = "findAllBy%s";
        list($findAll) = sscanf($name, $findAllFormat);
        //find one
        $findOneFormat = "findOneBy%s";
        list($findOne) = sscanf($name, $findOneFormat);

        //get all models
        $column_names = $this->getColumnNames(false);
        $models = \SmallFry\lib\AppModelFactory::allModels();

        $conditionList = "";
        $single = 0;
        if($findAll !== null)   {
            $conditionList = $findAll;
        }
        elseif($findOne !== null)   {
            $conditionList = $findOne;
            $single = 1;
        }

        $andFields = explode("And", $conditionList); //($conditionList, $andFormat);
        $conditions = array();
        $prepare = array('');
        foreach($andFields as $field)  {
            if(!is_null($field))    {
                list($modelName, $fieldName) = explode("__", $field) + array(null, null);
                $isset = false;
                if(!is_null($fieldName)) {
                    list($asModel, $modelName) = explode("_", $modelName) + array(null, null);
                    if(substr($fieldName, 0, 1) === "_")    {
                        $fieldName = substr($fieldName, 1);
                    }
                    if(is_null($modelName)) {
                        $modelName = $asModel;
                    }
                    foreach($models as $model)  {
                        if($modelName === $model->modelName)    {
                            $column_names = $model->getColumnNames(false);
                            if(in_array(trim($fieldName), $column_names))   {
                                $conditions[] = sprintf("%s.%s = ?", $asModel, $fieldName);
                                $prepare[0] .= "s"; $prepare[] = array_shift($args);
                                $isset = true;
                                break;
                            }
                        }
                    }
                }
                else    {
                    $fieldName = $field;
                    $column_names = $this->getColumnNames(false);
                    if(in_array($fieldName, $column_names))   {
                        $conditions[] = sprintf("%s.%s = ?", $this->modelName, $fieldName);
                        $prepare[0] .= "s"; $prepare[] = array_shift($args);
                        $isset = true;
                    }
                }
                if(!$isset) {
                    return "Error! `{$fieldName}` is not in any Model";
                    exit;
                }
            }
        }

        return $this->findAll($conditions, $prepare, $single);
    }

    public function findAll($conditions = array(), $prepare = array(), $single = 0)   {
        if(is_array($this->relationships) && count($this->relationships))   {
            $buildJoins = array();
            $manyToMany = array();
            foreach($this->relationships as $modelType => $relationship)  {
                $model = isset($relationship['model']) ? $relationship['model'] : $modelType;
                $type = isset($relationship['type']) ? $relationship['type'] : 'OneToOne';
                switch($type)   {
                    case 'OneToOne':    {
                        $join = array();
                        $join['model'] = $model;
                        $join['asTable'] = $modelType;
                        $join['on'] = array();
                        if(isset($relationship['conditions']))  {
                            foreach($relationship['conditions'] as $index => $value)    {
                                if(is_numeric($index)) $index = $value; //index and value are the same;
                                $join['on'][] = sprintf(
                                    "%s.%s = %s.%s",
                                    $this->modelName, $index, $modelType, $value
                                );
                            }
                        }
                        $buildJoins[] = $join;
                        break;
                    }
                    default: {
                        //one to many or many to many
                        $manyToMany[$modelType] =  array(
                            'conditions' => isset($relationship['conditions']) ?
                                                $relationship['conditions'] : array()
                        );
                        $through = $relationship['through'];
                        $firstJoin = array(
                            'asTable' => "{$through}_{$modelType}",
                            'model' => $through,
                            'on' => array(),
                        );
                        if(isset($relationship['conditions']))  {
                            $value = array_shift($relationship['conditions']);
                            $firstJoin['on'][] = sprintf("%s.%s = %s.%s", $this->modelName, $value,
                                $firstJoin['asTable'], $value);
                        }
                        $manyToMany[$modelType][] = $firstJoin;
                        $secondJoin = array(
                            'asTable' => $modelType,
                            'model' => $model,
                            'on' => array(),
                        );
                        if(isset($relationship['conditions']))  {
                            $value = array_shift($relationship['conditions']);
                            $secondJoin['on'][] = sprintf("%s.%s = %s.%s", $secondJoin['asTable'], $value,
                                $firstJoin['asTable'], $value);
                        }
                        $manyToMany[$modelType][] = $secondJoin;
                    }
                }
            }

            $select = $this->prepared_select(array(
                'conditions' => $conditions,
                'join' => $buildJoins
            ), $prepare, $single);

            if(!is_array($select))  {
                return $select;
                exit;
            }

            foreach($manyToMany as $modelType => $joins)    {
                $conditions = array_shift($joins);
                $joinCondition = array_shift($conditions);
                if($single === 1)   {
                    $select[$modelType] = $this->prepared_select(array(
                        'fields' => array("{$modelType}.*"),
                        'conditions' => array(
                            sprintf("%s.%s = ?", $this->modelName, $joinCondition)
                        ),
                        'join' => $joins
                    ), array("s", $select[$this->modelName][$joinCondition]));
                }
                else    {
                    foreach($select as &$row)    {
                        $row[$modelType] = $this->prepared_select(array(
                            'fields' => array("{$modelType}.*"),
                            'conditions' => array(
                                sprintf("%s.%s = ?", $this->modelName, $joinCondition)
                            ),
                            'join' => $joins
                        ), array("s", $row[$this->modelName][$joinCondition]));
                    }
                }
            }

            return $select;
        }
        else    {
            return $this->prepared_select(array(
                'conditions' => $conditions,
            ), $prepare, $single);
        }
    }

    public function __destruct()   {

    }

}