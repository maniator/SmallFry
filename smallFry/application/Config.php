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
    public static function get($v){
        return isset(self::$configVariables[$v]) ? self::$configVariables[$v] : false;
    }
    
    /**
     * Get all configVariables variables
     * @return array configVariables
     */
    public static function getAll(){
        return self::$configVariables;
    }
    
    /**
     * Set an configVariables variable
     * 
     * @param string $v
     * @param mixed $va
     * @return mixed
     */
    public static function set($v, $va){
        return self::$configVariables[$v] = $va;
    }
    
    /**
     * Clean up the configVariables variable
     */
    public static function clean(){
        self::$configVariables = array();
    }
}
