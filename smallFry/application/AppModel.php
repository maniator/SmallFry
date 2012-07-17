<?php
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
     * @var MySQL
     */
    protected $firstHandle;
    /**
     *
     * @var MySQL
     */
    protected $secondHandle;
    
    function __construct($USEDBY = null, Config $CONFIG, MySQL $firstHandle, MySQL $secondHandle = null) {
        
        $this->CONFIG = $CONFIG;
        $this->firstHandle = $firstHandle;
        $this->secondHandle = $secondHandle;
        
        $this->modelName = get_class($this);
        $this->modelTable = strtolower(Pluralize::pluralize($this->modelName));

        $this->connect($firstHandle, $secondHandle);
        
        $this->usedBy = $USEDBY; 
        $this->parsePosts();
        $this->setUpUsing();
        $this->init();
    }

    function init() {}
    
    public final function getUsedBy(){
        return $this->usedBy;
    }
    
    protected final function setUpUsing()   {
        /** Using Models **/
        if(is_array($this->using)){
            foreach($this->using as $usingModel){
                $used = false;
                if(is_array($this->usedBy)){

                    $usedByModel = $this->usedBy['model']->getUsedBy();
                    
                    if($usingModel === $this->usedBy['name']) {
                        $this->$usingModel = $this->usedBy['model'];    //use existing object
                        $used = true;
                    }
                    elseif(is_array($usedByModel)) {
                        if($usingModel === $usedByModel['name'])    {
                            $this->$usingModel = $usedByModel['model'];    //use existing object
                            $used = true;
                        }
                    }
                }
                
                if(class_exists($usingModel) 
                        && is_subclass_of($usingModel, 'AppModel')
                        && !$used){
                    /**
                     * @var AppModel $usingModel
                     */
                    $this->$usingModel = new $usingModel(array(
                        'name' => $this->modelName, 
                        'model' => $this
                    ), $this->CONFIG, $this->firstHandle, $this->secondHandle);
                }
            }
        }
    }
    
    public function addUseModel($usingModel)   {
        if(class_exists($usingModel) 
            && is_subclass_of($usingModel, 'AppModel')
            && !isset($this->$usingModel)){
        /**
         * @var AppModel $usingModel
         */
        $this->$usingModel = new $usingModel(array(
            'name' => $this->modelName, 
            'model' => $this
        ), $this->CONFIG, $this->firstHandle, $this->secondHandle);
    }
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
    
    public final function camelToWords($str){
        return ucwords(preg_replace('/(?!^)[[:upper:]][[:lower:]]/', ' $0', preg_replace('/(?!^)[[:upper:]]+/', ' $0', $str)));
    }
    
    function __destruct() {}

}