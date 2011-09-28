<?php
date_default_timezone_set('America/New_York');

//DEFINE ROOTS
define('WEBROOT', 'http://st-ny800-pc24:3535/');
define('DOCROOT', 'C:\\inetpub\\Ops-App\\');
//END DEFINE ROOTS

//AUTOLOADER
function class_autoloader($class) {
   // convert '_' to '/'
   $folderedClass = str_replace('_', '/', implode("_",array_reverse(explode("_",$class))));
   // presumes classes are in './classes'
   $folders = array(
     './', './../', './../../'  
   );
   $directories = array(
     'classes','controller', 'model' , 'helper', ''
   );
   $dir = dirname(__FILE__);
   $theClass = '/' . $folderedClass . '.php';
   
   
   foreach($folders as $folder){
       foreach($directories as $directory){
           $theInclude = $dir.$folder.$directory.$theClass;

           if (file_exists($theInclude) && include_once($theInclude)) {
              return TRUE;
           } 
       }
   }
   
  //trigger_error("The class '$class' or the file '$theClass' failed to spl_autoload ", E_USER_WARNING);

  return FALSE;
}

spl_autoload_register('class_autoloader');
//END AUTOLOADER