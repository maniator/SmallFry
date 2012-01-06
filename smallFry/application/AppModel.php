<?php

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
    
    function __construct() {
        $database_info = Config::get('DB_INFO');
        $this->connect($database_info['host'], $database_info['login'], 
                               $database_info['password'], $database_info['database']);
        $this->modelName = get_class($this);
        $this->modelTable = strtolower(Pluralize::pluralize($this->modelName));
        $this->parsePosts();
        $this->init();
    }

    function init() {}

    function getMySQLObject(){
        return $this->dbHandle;
    }
    
    public function getPosts(){
        return $this->posts;
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
    
    function __destruct() {}

}