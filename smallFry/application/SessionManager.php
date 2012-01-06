<?php
/**
 * Description of SessionManager
 *
 * @author nlubin
 */
class SessionManager {
    
    private $_sess_variables;
    private $_false = false;
    
    /**
     *
     * @param string $appName 
     */
    function __construct($appName = 'App') {
        $this->app_name = $appName;
        if(!isset($_SESSION[$this->app_name])){
            $_SESSION[$this->app_name] = array();
        }
        $this->loadSession();
    }
    
    private function loadSession(){
        if(!isset($_SESSION)){
            session_start();
        }
        $this->_sess_variables = $_SESSION[$this->app_name] ?: array();
    }
    
    /**
     *
     * @param string $name
     * @param mixed $variable 
     */
    function set($name, $variable){
       $this->_sess_variables[$name] = $variable;
       $this->saveSession();
    }

    /**
     *
     * @param string $name
     * @return mixed 
     */
    function &get($name) {    //get by reference
        if(array_key_exists($name, $this->_sess_variables)){
            return $this->_sess_variables[$name];
        }
        return $this->_false;
    }

    function clear() {
       $this->_sess_variables = array();
    }
    
    function saveSession(){
        $_SESSION[$this->app_name] = $this->_sess_variables;
    }
    
    function __destruct() {
       $this->saveSession();
    }
}

