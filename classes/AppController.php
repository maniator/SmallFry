<?php
/**
 * Description of AppController
 *
 * @author nlubin
 */
class AppController {
    
    /**
     *
     * @var mySQL
     */
    protected $_mysql;
    protected $_page_on,
            $_allowed_pages = array(),
            $_not_allowed_pages = array(
                '__construct', 'get', 'set', 
                'getAll', 'display_page', 'error_page',
                'include_jQuery', 'include_js', '_setHelpers',
                '_validate_posts', '_doValidate', '_make_error'
            );
    protected $app_vars = array();
    var $name = __CLASS__;
    var $helpers = array();
    var $validate = array();
    var $posts = array();
    protected $validator;
    
    public function __construct()   {
        $this->_mysql = Database::getConnection();
        $this->_page_on = App::get('page');
        App::set('view', strtolower($this->name));
        $this->_allowed_pages = get_class_methods($this);
        $this->set('jQuery', $this->include_jQuery());
        $this->setHelpers();

        $this->_getPosts();
        $this->posts = (object) $this->posts;
        if(!isset($_SESSION[App::get('APP_NAME')][strtolower($this->name)])){
            $_SESSION[App::get('APP_NAME')][strtolower($this->name)] = array();
        }
        return;
    }
    
    public function init(){
        
    }
    
    public function get($v){
        return isset($this->app_vars[$v])?$this->app_vars[$v]:false;
    }
    
    protected function set($v, $va){
        return $this->app_vars[$v] = $va;
    }
    
    public function getAll(){
        return $this->app_vars;
    }
    /**
     * Show the current page in the browser
     * @return string 
     */
    public function display_page($args)  {
        App::set('method', $this->_page_on);
        $private_fn = (strpos($this->_page_on, '__') === 0);
        if(in_array($this->_page_on, $this->_allowed_pages) 
                && !in_array($this->_page_on, $this->_not_allowed_pages)
                        && !$private_fn)    {  
            call_user_func_array(array($this, $this->_page_on), $args);
        }
        else    {
            if(App::get('view') == strtolower(__CLASS__) || $private_fn ||
                    in_array($this->_page_on, $this->_not_allowed_pages)){
                header("HTTP/1.1 404 Not Found");
            }
            else {
                App::set('method', '../missingfunction'); //don't even allow trying the page
                return($this->error_page(App::get('view')."/{$this->_page_on} does not exist."));
            }
            exit;
        }
    }
    
    /**
     *
     * @return string 
     */
    function index()    {}
    
    /**
     *
     * @param string $msg
     * @return string 
     */
    protected function error_page($msg = null)    {
        $err = '<span class="error">%s</span>';
        return sprintf($err, $msg);
    }
    
    /**
     *
     * @return string 
     */
    protected function include_jQuery(){
        $ret = '<script type="text/javascript" src="js/jquery-1.6.2.min.js"></script>'.PHP_EOL;
        $ret .= '        <script type="text/javascript" src="js/jquery-ui-1.8.9.custom.min.js"></script>'.PHP_EOL;
        return $ret;
    }
    
    /**
     *
     * @param string $src
     * @return string 
     */
    protected function include_js($src){
        $script = '<script type="text/javascript" src="js/%s.js"></script>'.PHP_EOL;
        return sprintf($script, $src);
    }
    
    protected function setHelpers(){
        $helpers = array();
        foreach($this->helpers as $helper){
            $help = "{$helper}Helper";
            $this->$helper = new $help();
            $helpers[$helper] = $this->$helper;
        }
        self::set('helpers', (object) $helpers);
    }
    
    protected function logout(){
        session_destroy();
        header('Location: '.WEBROOT.'index.php');
        exit;
    }
    
    protected function _validate_form($validate = null, $values = null, $exit = true){
        
        $this->validator = new FormValidator(); //create new validator
        
        if($validate == null){
            $validate = $this->validate;
        }
        
        foreach($validate as $field => $rules){
            foreach($rules as $validate=>$message){
                $this->validator->addValidation($field, $validate, $message);
            }
        }
        
        return $this->_doValidate($values, $exit);
    }
    
    protected function _doValidate($values = null, $exit = true){
        if(!(!isset($_POST) || count($_POST) == 0)){
            //some form was submitted
            if(!$this->validator->ValidateForm($values)){
                $error = '';
                $error_hash = $this->validator->GetErrors();
                foreach($error_hash as $inpname => $inp_err)
                {
                  $error .= "$inp_err<br/>\n";
                }
                return $this->_make_error($error, $exit);                
            }
        }
        return true;
    }
    
    protected function _getPosts(){
                
        if(!(!isset($_POST) || count($_POST) == 0)){
            foreach($_POST as $key=>$post){
                $this->posts[$key] = $post;
            }
        }
        
    }
    
    function __get($var_name){
//        echo $var_name."<br>";
        if(isset($this->posts->$var_name)){
            return $this->posts->$var_name;
        }
        else{
            $this->_make_error($var_name.' is not set');
            exit;
        }
    }
    
    function __call($name, $arguments){
        if($name == 'mysql'){
            return (strlen($this->$arguments[0])==0?"NULL":"'{$this->$arguments[0]}'");
        }
    }
    
    function _make_error($str, $exit = true){
        $return = '<div class="errors">'.$str.'</div>';
        if($exit) exit($return);
        return $return;
    }
}

?>
