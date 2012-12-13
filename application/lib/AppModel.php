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
        $this->modelTable = strtolower(Pluralize::pluralize($this->modelName));

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
    
    public final function getModelTable(){
        return sprintf("`%s`.`%s`", $this->dbName, $this->modelTable);
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
	if(!$this->CONFIG->get('DB_NEW'))   {
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
        return ucwords(preg_replace('/(?!^)[[:upper:]][[:lower:]]/', ' $0', preg_replace('/(?!^)[[:upper:]]+/', ' $0', $str)));
    }
    
    function __destruct() {}

}