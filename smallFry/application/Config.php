<?php


/**
 * Description of Config
 *
 * @author nlubin
 */
class Config {
    /**
     * Holds all of the app variables
     * @var array
     */
    private static $configVariables = array();
    
    /**
     * Get a single configVariables variable
     * @param string $v
     * @return mixed 
     */
    private static function static_get($v){
        return isset(self::$configVariables[$v]) ? self::$configVariables[$v] : false;
    }
    
    /**
     * Get all configVariables variables
     * @return array configVariables
     */
    private static function static_getAll(){
        return self::$configVariables;
    }
    
    /**
     * Set an configVariables variable
     * 
     * @param string $v
     * @param mixed $va
     * @return mixed
     */
    private static function static_set($v, $va){
        return self::$configVariables[$v] = $va;
    }
    
    /**
     * Clean up the configVariables variable
     */
    private static function static_clean(){
        self::$configVariables = array();
    }  
    
    public function get($v){  
        return self::static_get($v);
    }
    
    public function set($v, $va)    {
        return self::static_set($v, $va);
    }
    
    public function getAll()    {
        return self::static_getAll();
    }
    
    public function clean() {
        return self::static_clean();
    }
}
