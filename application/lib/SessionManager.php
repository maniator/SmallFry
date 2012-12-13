<?php
namespace SmallFry\lib; 
/**
 * Description of SessionManager
 *
 * @author nlubin
 */
class SessionManager {
    
    private $_sessionVariables;
    
    /**
     * @param string $appName 
     */
    function __construct($appName = 'App') {
        if(!isset($_SESSION)){
            session_start();
        }
        $this->appName = $appName;
        $this->loadSession();
    }
    
    private function loadSession(){
        if(!array_key_exists($this->appName, $_SESSION)){
            $_SESSION[$this->appName] = array();
        }
        $this->_sessionVariables = &$_SESSION[$this->appName];  //set by reference
    }
    
    /**
     *
     * @param string $name
     * @param mixed $variable 
     */
    function set($name, $variable){
       $this->_sessionVariables[$name] = $variable;
    }

    /**
     *
     * @param string $name
     * @return mixed 
     */
    function &get($name) {    //get by reference
        if(array_key_exists($name, $this->_sessionVariables)){
            return $this->_sessionVariables[$name];
        }
        $this->set($name, false);
        return $this->_sessionVariables[$name];
    }

    function showSession(){
        return $this->_sessionVariables;
    }
    
    function clear() {
       $this->_sessionVariables = array();
    }
    
    function close()    {
    	session_write_close();
    }
}

