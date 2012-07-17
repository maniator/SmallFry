<?php
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
    
    function __construct(Config $CONFIG) {
        $this->CONFIG = $CONFIG;
        $this->CONFIG->set('page_title', $this->CONFIG->get('DEFAULT_TITLE'));
        $this->CONFIG->set('template', $this->CONFIG->get('DEFAULT_TEMPLATE'));
        $this->_session = new SessionManager($this->CONFIG->get('APP_NAME'));
        $this->_path = $this->readPath();
        $this->_controller = $this->loadController();
        $this->_template = new Template($this->_path, $this->_session, $this->_controller, $this->CONFIG); //has destructor that controls it
        $this->_controller->displayPage($this->_path->args);   //run the page for the controller
        $this->_template->renderTemplate(); //only render template after all is said and done
    }
    
    /**
     *
     * @return stdClass
     */
    private function readPath(){
        $default_controller = $this->CONFIG->get('DEFAULT_CONTROLLER');
        
        $_SERVER["PATH_INFO"] = !isset($_SERVER["PATH_INFO"]) ? "/" : $_SERVER["PATH_INFO"];
        $path = $_SERVER["PATH_INFO"] ?: '/'.$default_controller;

        $path_info = explode("/",$path);
        $page = isset($path_info[2]) ? $path_info[2] : "index";
        list($page, $temp) = explode('.', $page) + array("index", null);
        $model = $path_info[1] ?: $default_controller;
        
        return (object) array(
            'path_info' => $path_info,
            'page' => $page,
            'args' => array_slice($path_info, 3),
            'route_args' => array_slice($path_info, 2),   //if is a route, ignore page
            'model' => $model
        );
    }
    
    /**
     * @return AppController
     */
    private function loadController(){
        $this->CONFIG->set('page', $this->_path->page);

        //LOAD CONTROLLER
        $modFolders = array('images', 'js', 'css');

        //load controller
        if(strlen($this->_path->model) == 0) $this->_path->model = $this->CONFIG->get('DEFAULT_CONTROLLER');
        
        //find if is in modFolders:
        $folderIntersect = array_intersect($this->_path->path_info, $modFolders);
        
        if(count($folderIntersect) == 0){ //load it only if it is not in one of those folders
            $controllerName = "{$this->_path->model}Controller";
            $app_controller = $this->create_controller($controllerName); 
            if(!$app_controller)    {
                $route = $this->CONFIG->getRoute($this->_path->model);
                if($route)  {   //try to find route
                    $this->_path->page = $route->page;
                    $this->CONFIG->set('page', $route->page);   //reset the page name
                    $this->_path->args = count($route->args) ? $route->args : $this->_path->route_args;
                    $app_controller = $this->create_controller($route->controller);
                }
                else    {
                    //show nothing 
                    header("HTTP/1.1 404 Not Found");
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
   
    /**
     * @return AppController
     * @assert (AppController)
     */
    private function create_controller($controllerName) {
        
        //DB CONN
        $firstHandle = null;
        $secondHandle = null;
        //Primary db connection
        $database_info = $this->CONFIG->get('DB_INFO');
        if($database_info)  {
            $firstHandle = new MySQL($database_info['host'], $database_info['login'], 
                                   $database_info['password'], $database_info['database'], $this->CONFIG->get('DEBUG_QUERIES'));
        }
        else    {
            exit("DO NOT HAVE DB INFO SET");
        }
        
        //Secondary db connection
        $database_info = $this->CONFIG->get('SECONDARY_DB_INFO');
        if($database_info)  {
            $secondHandle = new MySQL($database_info['host'], $database_info['login'], 
                                   $database_info['password'], $database_info['database'], $this->CONFIG->get('DEBUG_QUERIES'));
        }
        //END DB CONN
                
        if (class_exists($controllerName) && is_subclass_of($controllerName, 'AppController')) {  
            $app_controller  = new $controllerName($this->_session, $this->CONFIG, $firstHandle, $secondHandle); 
        } else {
            return false;
        }
        return $app_controller;
    }
    
    /**
     *
     * @param array $path_info 
     */
    private function rewrite(array $path_info, array $folderIntersect){
        
        $find_path = array_keys($folderIntersect);
        $find_length = count($find_path) - 1;
        $file_name = implode("/",array_slice($path_info, $find_path[$find_length]));

        $file = DOCROOT."webroot/".$file_name;
        
        if(is_file($file)){ //if the file is a real file
            include BASEROOT.'/functions/mime_type.php'; // needed for setups without `mime_content_type`
            header('Content-type: '.mime_content_type($file));
            readfile($file);
        }
        else {
            header("HTTP/1.1 404 Not Found");
        }
        exit;
    }
}
