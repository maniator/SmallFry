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
    private static $app_vars = array();
    
    /**
     * Get a single app_vars variable
     * @param string $v
     * @return mixed 
     */
    public static function get($v){
        return isset(self::$app_vars[$v])?self::$app_vars[$v]:false;
    }
    
    /**
     * Get all app_vars variables
     * @return array app_vars
     */
    public static function getAll(){
        return self::$app_vars;
    }
    
    /**
     * Set an app_vars variable
     * 
     * @param string $v
     * @param mixed $va
     * @return mixed
     */
    public static function set($v, $va){
        return self::$app_vars[$v] = $va;
    }
    
    /**
     * Clean up the app_vars variable
     */
    public static function clean(){
        self::$app_vars = array();
    }
}
