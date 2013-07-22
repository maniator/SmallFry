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
            $set[] = sprintf("`%s` = ?", $fieldName);
            $prepare[] = $value;
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
                    var_dump($statement);
                    return "Row statement save error: {$statement->error}";
                }
                else    {
                    return false;
                }
            }
            $statement->close();
        }
        else {
            if($returnErrors)   {
                return "Row save error: {$this->dbHandle->error}";
            }
            else    {
                return false;
            }
        }
        return true;
    }

    public function __destruct()   {

    }

}