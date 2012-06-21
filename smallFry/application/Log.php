<?php
/**
 * Description of Log
 *
 * @author nlubin
 */
class Log {
    private $_time;
    private $_logFile;
    private $_fileWriter;
    private $_user;
    private $_lvls = array('CRITICAL', 'ERROR', 'INFO', 'DEBUG');
    private $_eol = PHP_EOL;

    function  __construct($user = "NO USER", $logLevel = 2, $dirLevel = '') {
        $this->_user = $user;
        $this->_logLevel = $logLevel;
        $this->_dirLevel = $dirLevel;
    }

    function startLog($appName = "SmallFry") {
        $file = $this->_dirLevel."logs\\".date("Y_m_d");

        if(!is_dir($file)){
            mkdir($file);
        }

        $logs = scandir($file);
        $this->_logFile = $file."\\".$appName.".log";
        if(in_array($appName.".log",$logs)){
            $this->_fileWriter = fopen($this->_logFile, 'a') or die("can't append to {$this->_logFile} ".  print_r(error_get_last(), true));
        }
        else {
            $this->_fileWriter = fopen($this->_logFile, 'w') or die("can't create {$this->_logFile}");
        }
    }

    private function setTime($asString = true){
        $micro = explode(" ",microtime());
        $micro = explode(".",$micro[0]);
        $micro = substr($micro[1],0,6);
        $this->_time = date("H:i:s").":{$micro}";
    }

    function logToFile($msg, $lvl = 2){

        $highestLvl = $this->_logLevel;
        $this->setTime();

        while($lvl >= count($this->_lvls)){
            $lvl -= count($this->_lvls);
        }

        if($lvl >= 0){
            $display_level = $this->_lvls[$lvl];
        }

        if($lvl <= $highestLvl){
            fwrite($this->_fileWriter, "[$this->_time {$display_level} $this->_user] $msg $this->_eol") or die("ERROR WRITING TO FILE");
        }

        if($lvl <= 0 && $lvl != -2){
            die("<pre>$msg</pre>");
        }
    }
}