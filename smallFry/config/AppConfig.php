<?php
//*DEBUG ERROR REPORTING
ini_set('display_errors', 1); 
ini_set('log_errors', 1); 
ini_set('error_log', dirname(__FILE__) . '/error_log.txt'); 
error_reporting(E_ALL);
//END DEBUG ERROR REPORTING*/

function flush_buffers(){
    ob_end_flush();
    ob_flush();
    flush();
    ob_start();
}

include 'smallFry/config/Autoloader.php';

//DATABASE INFO
Config::set('DB_INFO', array(
    'host' => '',
    'login' => '',
    'password' => '',
    'database' => '',
));
//END DATABASE INFO

//DEFAULT TEMPLATE
Config::set('APP_NAME', 'SmallFry');
//END DEFAULT TEMPLAT
//
//DEFAULT TEMPLATE
Config::set('DEFAULT_TEMPLATE', 'default');
//END DEFAULT TEMPLATE

//DEFAULT TITLE
Config::set('DEFAULT_TITLE', 'SmallFry App');
//END DEFAULT TITLE

//LOGIN SEED
Config::set('LOGIN_SEED', "ghlkjhgk;hjkiuo");
//END LOGIN SEED

Config::set('DEFAULT_CONTROLLER', 'AppController'); //Remember to set this!


//LOAD BOOTSTRAP
$boot = new Bootstrap();
//END BOOTSTRAP LOAD