<?php
//*DEBUG ERROR REPORTING
ini_set('display_errors', 1); 
ini_set('log_errors', 1); 
ini_set('error_log', dirname(__FILE__) . '/error_log.txt'); 
error_reporting(E_ALL);
//END DEBUG ERROR REPORTING*/

include './smallFry/config/Autoloader.php';

//DEFAULT TEMPLATE
Config::set('APP_NAME', 'SmallVC');
//END DEFAULT TEMPLAT
//
//DEFAULT TEMPLATE
Config::set('DEFAULT_TEMPLATE', 'default');
//END DEFAULT TEMPLATE

//DEFAULT TITLE
Config::set('DEFAULT_TITLE', 'Small-VC');
//END DEFAULT TITLE

//LOGIN SEED
Config::set('LOGIN_SEED', "lijfg98u5;jfd7hyf");
//END LOGIN SEED

Config::set('DEFAULT_CONTROLLER', 'AppController');


//LOAD BOOTSTRAP
$boot = new Bootstrap();
//END BOOTSTRAP LOAD