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
    
    function __construct($USEDBY = null, $DB_INFO = null) {
        $database_info = ($DB_INFO === null)?Config::get('DB_INFO'):$DB_INFO;
        $this->connect($database_info['host'], $database_info['login'], 
                               $database_info['password'], $database_info['database']);
        $this->modelName = get_class($this);
        $this->modelTable = strtolower(Pluralize::pluralize($this->modelName));
        $this->modelColumns = $this->getColumnNames();
        $this->usedBy = $USEDBY; 
        $this->parsePosts();
        $this->setUpUsing();
        $this->init();
    }

    function init() {}

    
    protected function setUpUsing(){
        /** Using Models **/
        if(is_array($this->using)){
            foreach($this->using as $usingModel){
                if(is_array($this->usedBy) && $usingModel === $this->usedBy['name']){
                    $this->$usingModel = $this->usedBy['model'];    //use existing object
                }
                elseif(class_exists($usingModel) 
                        && is_subclass_of($usingModel, 'AppModel')){
                    /**
                     * @var AppModel $usingModel
                     */
                    $this->$usingModel = new $usingModel(array(
                        'name' => $this->modelName, 
                        'model' => $this
                    ));
                }
            }
        }
    }
    function setModelTable($tableName){
        $this->modelTable = $tableName;
        $this->modelColumns = $this->getColumnNames();
    }
    
    function getMySQLObject(){
        return $this->dbHandle;
    }
    
    public function getPosts(){
        return $this->posts;
    }
    
    public function getModelColumns(){
//        echo "CLASS (getting columns): ".get_class($this).PHP_EOL;
        return $this->modelColumns;
    }
    
    public function getModelTable(){
//        echo "CLASS (getting table): ".get_class($this).PHP_EOL;
//        echo "CLASS (model table): ".$this->modelTable.PHP_EOL;
        return $this->modelTable;
    }
    
    protected function parsePosts(){
        
        if(!(!isset($_POST) || count($_POST) == 0)){
            foreach($_POST as $key=>&$post){
                $this->posts[$key] = $this->realEscapeObject($post); //escape all randomness
            }
        }
        $this->posts = (object) $this->posts;
        
    }
    
    protected function realEscapeObject($q) {
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
    
    public function camelToWords($str){
        return ucwords(preg_replace('/(?!^)[[:upper:]][[:lower:]]/', ' $0', preg_replace('/(?!^)[[:upper:]]+/', ' $0', $str)));
    }
    
    function __destruct() {}

}