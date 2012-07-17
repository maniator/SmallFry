<?php
//echo "<pre>";
date_default_timezone_set('America/New_York');

//DEFINE ROOTS
define('WEBROOT', 'http://localhost/');
define('INDEX', 'index.php/');
define('DOCROOT', '/var/www/html/');
define('BASEROOT', 'C:\\inetpub\\web_base\\');
define('SCRIPTROOT', WEBROOT."webroot/");
//END DEFINE ROOTS

set_include_path(get_include_path() . PATH_SEPARATOR . BASEROOT . PATH_SEPARATOR . DOCROOT);

//AUTOLOADER
function class_autoloader($class) {
    
   $include_path = explode(PATH_SEPARATOR, get_include_path());
   // convert '_' to '/'
   $folderedClass = str_replace('_', '/', implode("_",array_reverse(explode("_",$class))));
   // presumes classes are in './classes'
   $folders = array(
     '/', '/smallFry/'
   );
   $directories = array(
     'config', 'application', 'controller', 'model' , 'helper', ''
   );
   $theClass = '/' . $folderedClass . '.php';
   
   foreach($folders as $folder){
       foreach($directories as $directory){
//           $theInclude = $folder.$directory.$theClass;
           foreach($include_path as $includePath)   {
               $theInclude = $includePath.$folder.$directory.$theClass;
//               echo "SEARCHING FOR $theInclude <br/>";
               if (file_exists($theInclude) && include_once($theInclude)) {
                  return TRUE;
               } 
           }
       }
   }
   
  return FALSE;
}

spl_autoload_register('class_autoloader');
//END AUTOLOADER
