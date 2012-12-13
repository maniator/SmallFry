<?php
namespace SmallFry\lib; 
/**
 * Description of DebugLogger
 *
 * @author nlubin
 */
class DebugLogger {
    
    protected static $logBool;
    protected static $logger;
    public static $logTemplate = "<div>DEBUG [%s:%d]: %s </div> \n";
    public static $logs = '';
    public static $echo = false;
    private static $CONFIG;
    private static $SESSION;
    
    public static function init(){  
        self::$CONFIG = new Config();
        self::$SESSION = new SessionManager('DEBUG_CONSOLE');
        self::$logBool = self::$CONFIG->get('DEBUG_MODE');
        self::$logger = new Log($_SERVER['AUTH_USER'], 2, \SmallFry\Config\DOCROOT, self::$CONFIG->get('APP_NAME'));
    }
    
    public static function displayLog($msg, $fixed = false, $forceLog = false)  {
        if(self::$logBool || $forceLog)  {
            $bt = debug_backtrace();
            $caller = array_shift($bt); //to line numbers and file names
            if($fixed)  {
                $msg = "<pre style='padding-left: 15px;'>" . print_r($msg, true) . "</pre>";
            }
            self::$logs .= sprintf(self::$logTemplate, $caller['file'], $caller['line'], $msg);
            if(self::$echo) echo $msg; self::$echo = false;
            if(strlen(self::$logs)) {   //only print if there are log messages
                self::$SESSION->set("DEBUG_LOG_INFO", sprintf("<div class='debug'>%s</div>", self::$logs));
            }
        }
    }
    
    public static function log($msg, $echo = false, $exit = false)    {
        self::$logger->logToFile($msg);
        if($echo)   echo $msg;
        if($exit)   exit;
    }
    
    public static function destruct()    {
        if(strlen(self::$logs)) {   //only print if there are log messages
            self::$SESSION->set("DEBUG_LOG_INFO", sprintf("<div class='debug'>%s</div>", self::$logs));
        }
    }
}

DebugLogger::init();

?>
