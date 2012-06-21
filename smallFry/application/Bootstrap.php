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
        $path = isset($_SERVER["PATH_INFO"])?$_SERVER["PATH_INFO"]:'/'.$this->CONFIG->get('DEFAULT_CONTROLLER');

        $path_info = explode("/",$path);
        $page = (isset($path_info[2]) && strlen($path_info[2]) > 0)?$path_info[2]:'index';
        list($page, $temp) = explode('.', $page) + array('index', null);
        $args = array_slice($path_info, 3);
        $controller = $path_info[1] ?: $this->CONFIG->get('DEFAULT_CONTROLLER');
        
        $this->_session->set('modelRedirect', array(
            'model' => $controller,
            'fn' => $page
        )); //for redirect after login
        
        return (object) array(
            'path_info'=>$path_info,
            'page'=>$page,
            'args'=>$args,
            'controller'=>$controller
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
        if(strlen($this->_path->controller) == 0) $this->_path->controller = $this->CONFIG->get('DEFAULT_CONTROLLER');
        
        if(count(array_intersect($this->_path->path_info, $modFolders)) == 0){ //load it only if it is not in one of those folders
            $controllerName = "{$this->_path->controller}Controller";
            return $this->create_controller($controllerName); 
        }
        else {  //fake mod-rewrite
            $this->rewrite($this->_path->path_info);
        }
        //END LOAD CONTROLLER
    }
   
    /**
     * @return AppController
     * @assert (AppController)
     */
    private function create_controller($controllerName) {
        if (class_exists($controllerName) && is_subclass_of($controllerName, 'AppController')) {  
            $app_controller  = new $controllerName($this->_session, $this->CONFIG); 
        } else {
            //show nothing 
            header("HTTP/1.1 404 Not Found");
            exit;
        }
        return $app_controller;
    }
    
    /**
     *
     * @param array $path_info 
     */
    private function rewrite($path_info){
        $rewrite = $path_info[count($path_info) - 2];
        $file_name = $path_info[count($path_info) - 1];

        $file = DOCROOT."webroot/".$rewrite."/".$file_name;
        if(is_file($file)){ //if the file is a real file
            include DOCROOT.'/smallFry/functions/mime_type.php'; // needed for setups without `mime_content_type`
            header('Content-type: '.mime_content_type($file));
            readfile($file);
        }
        else {
            header("HTTP/1.1 404 Not Found");
        }
        exit;
    }
}
