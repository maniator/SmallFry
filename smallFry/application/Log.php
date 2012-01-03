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

    function  __construct($user = "NO USER", $logLevel = 2, $dirLevel = '', $logType = 'overnight') {
        $this->_user = $user;
        $this->_logLevel = $logLevel;
        $this->_logType = $logType;
        $this->_dirLevel = $dirLevel;
        $this->startLog();
    }

    function startLog() {
        $file = $this->_dirLevel."logs\\".date("Y_m_d");
//        echo $file.PHP_EOL;
        if(!is_dir($file)){
//            echo 'IN !is_dir'.PHP_EOL;
            mkdir($file);
        }
//        echo 'past is_dir'.PHP_EOL;

        $logs = scandir($file);
        $this->_logFile = $file."\SmallFry_{$this->_logType}.log";
//        echo $this->_logFile.PHP_EOL;
        if(in_array("SmallFry_{$this->_logType}.log",$logs)){
//            echo 'in_array'.PHP_EOL;
            $this->_fileWriter = fopen($this->_logFile, 'a') or die("can't append to {$this->_logFile} ".  print_r(error_get_last(), true));
        }
        else {
//            echo 'not in_array '.PHP_EOL;
            $this->_fileWriter = fopen($this->_logFile, 'w') or die("can't create {$this->_logFile}");
        }
//        echo 'returning'.PHP_EOL.$this->_fileWriter.PHP_EOL;
    }

    private function setTime($asString = true){
        $micro = explode(" ",microtime());
        $micro = explode(".",$micro[0]);
        $micro = substr($micro[1],0,6);
        $this->_time = date("H:i:s").":{$micro}";
    }

    function logToFile($msg, $lvl = 2){
//        echo 'logging to file: '.$msg.PHP_EOL;
        $highestLvl = $this->_logLevel;
        $this->setTime();

        while($lvl >= count($this->_lvls)){
            $lvl -= count($this->_lvls);
        }

        if($lvl >= 0){
            $display_level = $this->_lvls[$lvl];
        }

        if($lvl <= $highestLvl){
//            echo 'ACTUALLY LOGGING'.PHP_EOL. $this->_fileWriter.PHP_EOL;
            fwrite($this->_fileWriter, "[$this->_time {$display_level} $this->_user] $msg $this->_eol") or die("ERROR WRITING TO FILE");
        }
//        echo 'OUT OF LOG'.PHP_EOL;
        if($lvl <= 0 && $lvl != -2){
            die("<pre>$msg</pre>");
        }
    }
}
