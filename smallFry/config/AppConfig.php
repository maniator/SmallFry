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

$CONFIG = new Config();

//DATABASE INFO
$CONFIG->set('DB_INFO', array(
    'host' => '',
    'login' => '',
    'password' => '',
    'database' => '',
));
//END DATABASE INFO

//DEFAULT TEMPLATE
$CONFIG->set('APP_NAME', 'SmallFry');
//END DEFAULT TEMPLAT
//
//DEFAULT TEMPLATE
$CONFIG->set('DEFAULT_TEMPLATE', 'default');
//END DEFAULT TEMPLATE

//DEFAULT TITLE
$CONFIG->set('DEFAULT_TITLE', 'SmallFry App');
//END DEFAULT TITLE

//LOGIN SEED
$CONFIG->set('LOGIN_SEED', "ghlkjhgk;hjkiuo");
//END LOGIN SEED

$CONFIG->set('DEFAULT_CONTROLLER', 'AppController'); //Remember to set this!

//DEBUG FLAGS
$CONFIG->set('DEBUG_MODE', false);
$CONFIG->set('DEBUG_QUERIES', false);

//LOAD BOOTSTRAP
$boot = new Bootstrap($CONFIG);
//END BOOTSTRAP LOAD