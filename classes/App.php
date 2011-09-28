<?php
/**
 * Description of App
 *
 * @author nlubin
 */
class App {
    /**
     * Holds all of the app variables
     * @var array
     */
    private static $app_vars = array();
    /**
     * Will be an App object
     * @var App
     */
    private static $app = null;
    
    /**
     * Get a single app_vars variable
     * @param string $v
     * @return mixed 
     */
    public static function get($v){
        return isset(self::$app_vars[$v])?self::$app_vars[$v]:false;
    }
    
    /**
     * Get all app_vars variables
     * @return array app_vars
     */
    public static function getAll(){
        return self::$app_vars;
    }
    
    /**
     * Set an app_vars variable
     * 
     * @param string $v
     * @param mixed $va
     * @return mixed
     */
    public static function set($v, $va){
        if(self::$app == null){ //create App on first set. if not, the app does not exist
            self::$app = new self();
        }
        return self::$app_vars[$v] = $va;
    }
    
    /**
     * Clean up the app_vars variable
     */
    public static function clean(){
        self::$app_vars = array();
    }
    
    public function __construct() {
        $this->_connection = Database::getConnection();
    }
    
    private function render_template(){
        $rPath = $this->read_path();
        foreach($rPath as $key=>$value){
            $$key = $value;
        }
        unset($rPath);

        ob_start();

        App::set('page_title',App::get('DEFAULT_TITLE'));
        App::set('template',App::get('DEFAULT_TEMPLATE'));
        App::set('page',$page);

        //LOGIN
        if(!isset($_SESSION['LOGIN']) || $_SESSION['LOGIN'] == false){
            Login::check_login();
        }
        else {
            $modFolders = array('images', 'js', 'css');
            
            //load controller
            if(strlen($controller) == 0) $controller = App::get('DEFAULT_CONTROLLER');
            
            if(count(array_intersect($path_info, $modFolders)) == 0){ //load it only if it is not in one of those folders
                $controllerName = "{$controller}Controller";
                $app_controller = $this->create_controller($controllerName, $args); 
            }
            else {  //fake mod-rewrite
                $this->rewrite($path_info);
            }
        }
        
        $main = ob_get_clean();
        App::set('main', $main);
        //LOAD VIEW
        ob_start();
        $this->load_view($controllerName, 0);
        //END LOAD VIEW
        
        //LOAD TEMPLATE
        $main = ob_get_clean();
        App::set('main', $main);
        $this->load_template($controllerName, $controllerName::get('jQuery'));
        //END LOAD TEMPLATE
    }
    
    
    private function read_path(){
        $path = isset($_SERVER["PATH_INFO"])?$_SERVER["PATH_INFO"]:'/'.App::get('DEFAULT_CONTROLLER');
        $path_info = explode("/",$path);
        $page = (isset($path_info[2]) && strlen($path_info[2]) > 0)?$path_info[2]:'index';
        list($page, $temp) = explode('.', $page) + array('index', null);
        $args = array_slice($path_info, 3);
        $controller = isset($path_info[1])?$path_info[1]:App::get('DEFAULT_CONTROLLER');
        return array(
            'path_info'=>$path_info,
            'page'=>$page,
            'args'=>$args,
            'controller'=>$controller
        );
    }
    
    private function create_controller($controllerName, $args = array()){
        if (class_exists($controllerName)) {  
            $app_controller  = new $controllerName(); 
        } else {
            //show nothing 
            header("HTTP/1.1 404 Not Found");
            exit;
        }
        echo $app_controller->display_page($args);
        return $app_controller;
    }
    
    private function load_template($controllerName, $jQuery = null){
        
        $page_title = $controllerName::get('title')?$controllerName::get('title'):App::get('DEFAULT_TITLE');
        //display output
        $cwd = dirname(__FILE__);
        $template_file = $cwd.'/../view/'.App::get('template').'.stp';
        if(is_file($template_file)){
            include $template_file;
        }
        else {
            include $cwd.'/../view/missingfile.stp'; //no such file error
        }
    }
    
    private function load_view($controllerName, $saveIndex){
        
        //Bring the variables to the global scope
        $vars = $controllerName::getAll();
        foreach($vars as $key=>$variable){
            $$key = $variable;
        }
        $cwd = dirname(__FILE__);
        if(App::get('view')){
            $template_file = $cwd.'/../view/'.App::get('view').'/'.App::get('method').'.stp';
            if(is_file($template_file)){
                include $template_file;
                if(App::get('saveTable')){ //create downloadable table in session
                    $_SESSION[App::get('APP_NAME')][strtolower($controller)][$saveIndex]['table'] = base64_encode(App::get('saveTable'));
                    $_SESSION[App::get('APP_NAME')][strtolower($controller)][$saveIndex]['savename'] = App::get('saveName');
                    if(isset($page)){
                        $_SESSION[App::get('APP_NAME')][strtolower($controller)][$saveIndex]['page'] = $page + 1;
                    }
                }
            }
            else {
                include $cwd.'/../view/missingview.stp'; //no such view error
            }
        }
        else {
            App::set('template', 'blank');
            include $cwd.'/../view/missingfunction.stp'; //no such function error
        }
    }


    private function rewrite($path_info){
        $rewrite = $path_info[count($path_info) - 2];
        $file_name = $path_info[count($path_info) - 1];

        $file = WEBROOT.$rewrite."/".$file_name;
//                echo $file; 
        header('Location: '.$file);
        exit;
    }
    
    public function __destruct() {
        $this->render_template();
    }
}

?>
