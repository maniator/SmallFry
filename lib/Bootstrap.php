<?php
/**
 * The main library for the framework
 */
namespace SmallFry\lib; 
class_alias('SmallFry\lib\MySQL_Interface', 'SmallFry\lib\MySQL'); // MySQL class to be deprecated
/**
 * Description of Bootstrap
 *
 * @author nlubin
 */
Class Bootstrap {
    
    /**
     *
     * @var SessionManager
     */
    private $_session;
    /**
     *
     * @var stdClass
     */
    private $_path;
    /**
     *
     * @var AppController
     */
    private $_controller;
    /**
     *
     * @var Template
     */
    private $_template;
    
    /**
     *
     * @var Config
     */
    private $CONFIG;
    
    /**
     * @param Config $CONFIG 
     */
    function __construct(Config $CONFIG) {
        try {
            $this->CONFIG = $CONFIG;
            $this->CONFIG->set('page_title', $this->CONFIG->get('DEFAULT_TITLE'));
            $this->CONFIG->set('template', $this->CONFIG->get('DEFAULT_TEMPLATE'));
            $this->_session = new SessionManager($this->CONFIG->get('APP_NAME'));
            $this->_path = $this->readPath();
            $this->_controller = $this->loadController();
            $this->_template = new Template($this->_path, $this->_session, $this->CONFIG);
            $this->_template->setController($this->_controller);
            $this->_controller->setPage($this->_path->page);
            $this->_controller->displayPage($this->_path->args);   //run the page for the controller
            $this->_template->renderTemplate($this->_path->args); //only render template after all is said and done
        }
        catch (\Exception $e)    {
	        header("HTTP/1.1 404 Not Found");
            exit;
        }
    }
    
    /**
     * @return \stdClass
     */
    private function readPath(){
        $default_controller = $this->CONFIG->get('DEFAULT_CONTROLLER');
        $index = str_replace("/", "", \SmallFry\Config\INDEX);
        
        //use strtok to remove the query string from the end fo the request
        $request_uri = !isset($_SERVER["REQUEST_URI"]) ? "/" : strtok($_SERVER["REQUEST_URI"],'?');;
        $request_uri = str_replace("/" . $index , "/", $request_uri);
        
        $path = $request_uri ?: '/'.$default_controller;
        $path = str_replace("//", "/", $path);
        
        $path_info = explode("/",$path);
        
        $page = isset($path_info[2]) ? $path_info[2] : "index";
        list($page, $temp) = explode('.', $page) + array("index", null);
        $model = $path_info[1] ?: $default_controller;
        
        $obj = (object) array(
            'path_info' => $path_info,
            'page' => $page,
            'args' => array_slice($path_info, 3),
            'route_args' => array_slice($path_info, 2),   //if is a route, ignore page
            'model' => $model
        );
        return $obj;
    }
    
    /**
     * @return AppController
     */
    private function loadController(){
        //LOAD CONTROLLER
        $modFolders = array('images', 'js', 'css');

        //load controller
        if(strlen($this->_path->model) == 0) $this->_path->model = $this->CONFIG->get('DEFAULT_CONTROLLER');
        
        //find if is in modFolders:
        $folderIntersect = array_intersect($this->_path->path_info, $modFolders);
        
        if(count($folderIntersect) == 0){ //load it only if it is not in one of those folders
            $controllerName = "{$this->_path->model}Controller";
            $model = $this->createModel($this->_path->model);
            $self = $this;
            $callback = function($modelName) use (&$model, &$self){
                if(get_class($model) === "SmallFry\lib\AppModel")   { //check if model DNE
                    $newModel = $self->createModel($modelName);
                    return $newModel;
                }
                else	{ //return the original model
                    return $model;
                }
            };
            $app_controller = $this->createController($controllerName, $model, $callback); 
            if(!$app_controller)    {
                $route = $this->CONFIG->getRoute($this->_path->model);
                if($route)  {   //try to find route
                    $this->_path->page = $route->page;
                    $this->_path->args = count($route->args) ? $route->args : $this->_path->route_args;
		            $model = $this->createModel($route->model);
                    $app_controller = $this->createController($route->controller, $model);
                    if(!$app_controller) {
                        //show nothing 
			            throw new \Exception("You cannot create a controller here for this route ({$route->controller}).");
                    }
                }
                else    {
                    //show nothing 
                    throw new \Exception("That controller does not exist ({$controllerName}).");
                    exit;
                }
            }
            return $app_controller;
        }
        else {  //fake mod-rewrite
            $this->rewrite($this->_path->path_info, $folderIntersect);
        }
        //END LOAD CONTROLLER
    }
    
    public function createModel($model)  {
        
        $useOldVersion = !$this->CONFIG->get('DB_NEW');
        $mySQLClass = "SmallFry\lib\MySQL_PDO";
        if($useOldVersion)  {
            $mySQLClass = "SmallFry\lib\MySQL_Improved";
        }
        //DB CONN
        $firstHandle = null;
        $secondHandle = null;
        //Primary db connection
        $database_info = $this->CONFIG->get('DB_INFO');
        if($database_info)  {
	    $firstHandle = new $mySQLClass($database_info['host'], $database_info['login'], 
				   $database_info['password'], $database_info['database'], $this->CONFIG->get('DEBUG_QUERIES'));
        }
        else    {
            exit("DO NOT HAVE DB INFO SET");
        }
        
        //Secondary db connection
        $database_info = $this->CONFIG->get('SECONDARY_DB_INFO');
        if($database_info)  {
            $secondHandle = new $mySQLClass($database_info['host'], $database_info['login'], 
                                   $database_info['password'], $database_info['database'], $this->CONFIG->get('DEBUG_QUERIES'));
        }
        //END DB CONN
	
	    return AppModelFactory::buildModel($model, $this->CONFIG, $firstHandle, $secondHandle);
    }
   
    /**
     * @param string $controllerName
     * @return AppController 
     */
    private function createController($controllerName, $model, $callback = null) {
                
        $nameSpacedController = "SmallFry\\Controller\\$controllerName";
        if (class_exists($nameSpacedController) && is_subclass_of($nameSpacedController, __NAMESPACE__.'\AppController')) {
            $app_controller = new $nameSpacedController($this->_session, $this->CONFIG, $model, $callback);
        } else {
            return false;
        }
	
        return $app_controller;
    }
    
    /**
     * @param array $path_info
     * @param array $folderIntersect 
     */
    private function rewrite(array $path_info, array $folderIntersect){
        
        $find_path = array_keys($folderIntersect);
        $find_length = count($find_path) - 1;
        $file_name = implode(DIRECTORY_SEPARATOR,array_slice($path_info, $find_path[$find_length]));

        $file = \SmallFry\Config\DOCROOT."webroot".DIRECTORY_SEPARATOR.$file_name;

        if(is_file($file)){ //if the file is a real file
            header("Last-Modified: " . date("D, d M Y H:i:s", getlastmod()));
            include \SmallFry\Config\BASEROOT.DIRECTORY_SEPARATOR.'functions'.DIRECTORY_SEPARATOR.'mime_type.php'; // needed for setups without `mime_content_type`
            header('Content-type: ' . mime_content_type($file));
            readfile($file);
        }
        else {
	    throw new \Exception("File does not exist ({$file}).");
        }
        exit;
    }
}
