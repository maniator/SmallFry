<?php
/**
 * Description of AppController
 *
 * @author nlubin
 */
class AppController {
    
    protected $_mysql, $_page_on,
            $_allowed_pages = array(), $_not_allowed_pages = array(
                '__construct', 'get', 'set', 'setTemplate',
                'getAll', 'display_page', 'error_page', 'init',
                'include_jQuery', 'include_js', '_setHelpers',
                '_validate_posts', '_doValidate', '_make_error'
            ),
            $name = __CLASS__, $helpers = array(),
            $validate = array(), $posts = array(), $_session,
            $validator, $_template;
    
    
    /**
     *
     * @param SessionManager $SESSION
     * @param  MySQL $MYSQL
     */
    public function __construct(SessionManager $SESSION, MySQL $MYSQL) {
        
        $this->_mysql = $MYSQL;
        $this->_page_on = Config::get('page');
        $this->_session = $SESSION;
        $this->_allowed_pages = get_class_methods($this);
        Config::set('view', strtolower($this->name));
        
        if(!$this->_session->get(strtolower($this->name))){
            $this->_session->set(strtolower($this->name), array());
        }
        
    }
    
    /**
     *
     * @param Template $TEMPLATE 
     */
    public function setTemplate(Template $TEMPLATE){
        $this->_template = $TEMPLATE;
        $this->_setHelpers();
        $this->_getPosts();
    }
    
    /**
     * Function to run before the constructor's view function
     */
    public function init(){} //function to run right after constructor
    
    /**
     * Show the current page in the browser
     *
     * @param array $args
     * @return string 
     */
    public function display_page($args)  {
        Config::set('method', $this->_page_on);
        $private_fn = (strpos($this->_page_on, '__') === 0);
        if(in_array($this->_page_on, $this->_allowed_pages) 
                && !in_array($this->_page_on, $this->_not_allowed_pages)
                        && !$private_fn)    {  
            call_user_func_array(array($this, $this->_page_on), $args);
        }
        else    {
            if(Config::get('view') == strtolower(__CLASS__) || $private_fn ||
                    in_array($this->_page_on, $this->_not_allowed_pages)){
                header("HTTP/1.1 404 Not Found");
            }
            else {
                Config::set('method', '../missingfunction'); //don't even allow trying the page
                return($this->error_page(Config::get('view')."/{$this->_page_on} does not exist."));
            }
            exit;
        }
    }
    
    /**
     *
     * @return string 
     */
    function index() {}
    
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
     * @param string $src
     * @return string 
     */
    protected function include_js($src){
        $script = '<script type="text/javascript" src="js/%s.js"></script>'.PHP_EOL;
        return sprintf($script, $src);
    }
    
    protected function _setHelpers(){
        $helpers = array();
        foreach($this->helpers as $helper){
            $help = "{$helper}Helper";
            $this->$helper = new $help();
            $helpers[$helper] = $this->$helper;
        }
        $this->_template->set('helpers', (object) $helpers);
    }
    
    protected function logout(){
        session_destroy();
        header('Location: '.WEBROOT.'index.php');
        exit;
    }
    
    /**
     *
     * @param array $validate
     * @param array $values
     * @param boolean $exit
     * @return boolean 
     */
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
            foreach($_POST as $key=>&$post){
                $this->posts[$key] = $this->_mres($post); //escape all randomness
            }
        }
        $this->posts = (object) $this->posts;
    }
    
    protected function _mres($q) {
        if(is_array($q)) {
            foreach($q as $k => $v) {
                $q[$k] = $this->_mres($v); //recurse into array
            }
        }
        elseif(is_string($q))   {
            $q = $this->_mysql->real_escape_string($q);
        }
        return $q;
    }
    
    function _make_error($str, $exit = true){
        $return = '<div class="errors">'.$str.'</div>';
        if($exit) exit($return);
        return $return;
    }
}

