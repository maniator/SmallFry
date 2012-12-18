<?php
namespace SmallFry\Config;
use SmallFry\lib\Config as Config;
use SmallFry\lib\Bootstrap as Bootstrap;

//*DEBUG ERROR REPORTING
ini_set('display_errors', 1); 
ini_set('log_errors', 1); 
ini_set('error_log', dirname(__FILE__) . '/error_log.txt'); 
error_reporting(E_ALL);
//END DEBUG ERROR REPORTING*/

//DEFINE ROOTS
define("CONFIG", __NAMESPACE__ . '\\');
define(CONFIG.'WEBROOT', 'http://localhost');
define(CONFIG.'INDEX', 'index.php');
define(CONFIG.'DOCROOT', 'C:\\inetpub\\\appname\\');
define(CONFIG.'BASEROOT', 'C:\\inetpub\\appname\\');
define(CONFIG.'SCRIPTROOT', WEBROOT."webroot/");
//END DEFINE ROOTS

include BASEROOT . 'functions/autoload.php';

$CONFIG = new Config();

//load ini file
$CONFIG->parseIni(DOCROOT . "smallFry/config/config.ini");
//load ini file

//LOAD BOOTSTRAP
$boot = new Bootstrap($CONFIG);
//END BOOTSTRAP LOAD
