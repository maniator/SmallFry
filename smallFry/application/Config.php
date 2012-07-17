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
    private $configRoutes = array();
    
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
    
    public function addRoute($location, $model, $page = "index", array $args = array())    {
        $controllerName = "{$model}Controller";
        //Make sure we can make this route
        if(class_exists($controllerName) && is_subclass_of($controllerName, 'AppController'))   {
            $this->configRoutes[strtolower($location)] =  (object)  array(
                "controller" => $controllerName,
                "page" => $page,
                "args" => $args,
            );
        }
        else    {
            echo "$model does not exist. Canot use it for a Route";
            exit;
        }
    }
    
    public function getRoute($location) {
        $location = strtolower($location);
        if(isset($this->configRoutes[$location]))   {
            return $this->configRoutes[$location];
        }
        else    {
            return false;
        }
    }
}
