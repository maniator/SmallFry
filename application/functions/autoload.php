<?php
date_default_timezone_set('America/New_York');

set_include_path(get_include_path() . PATH_SEPARATOR . \SmallFry\Config\BASEROOT . PATH_SEPARATOR . \SmallFry\Config\DOCROOT);

//AUTOLOADER
function autoload($className)
{
    $include_path = explode(PATH_SEPARATOR, get_include_path());
    $className = ltrim($className, '\\');
    $fileName  = '';
    $namespace = '';
    if ($lastNsPos = strripos($className, '\\')) {
        $fullNamespace = substr($className, 0, $lastNsPos);
        $firstVnPos = strpos ($fullNamespace, '\\');
        $vendor = substr($fullNamespace, 0, $firstVnPos);
        $namespace = substr($fullNamespace, $firstVnPos + 1);
        $className = substr($className, $lastNsPos + 1);
        $fileName  = str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
    }
    $fileName .= str_replace('_', DIRECTORY_SEPARATOR, $className) . '.php';
    foreach($include_path as $includePath)   {
        if (file_exists($includePath . $fileName)){
            require $includePath . $fileName;
            return true;
        }
    }
    return false;
}
spl_autoload_register('autoload');
//END AUTOLOADER