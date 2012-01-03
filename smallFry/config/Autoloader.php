<?php
date_default_timezone_set('America/New_York');

//DEFINE ROOTS
define('WEBROOT', 'http://localhost/');
define('DOCROOT', '/var/www/html/');
define('SCRIPTROOT', WEBROOT."webroot/");
//END DEFINE ROOTS

set_include_path(DOCROOT);

//AUTOLOADER
function class_autoloader($class) {
    
   $include_path = get_include_path();
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
//           $theInclude = $dir.$folder.$directory.$theClass;
           $theInclude = $include_path.$folder.$directory.$theClass;
           if (file_exists($theInclude) && include_once($theInclude)) {
              return TRUE;
           } 
       }
   }
   
  return FALSE;
}

spl_autoload_register('class_autoloader');
//END AUTOLOADER