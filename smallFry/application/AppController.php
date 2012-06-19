<?php
/**
 * Description of AppController
 *
 * @author nlubin
 */
class AppController {
    
    private $pageOn;
    protected $name = __CLASS__;
    protected $helpers = array();
    protected $validate = array();
    protected $posts = array();
    protected $session;
    protected $validator;
    protected $template;
    
    /**
     *
     * @param SessionManager $SESSION
     */
    public function __construct(SessionManager $SESSION) {
        
        $this->pageOn = Config::get('page');
        $this->session = $SESSION;
        $model_name = $this->name;
        if(class_exists($model_name) && is_subclass_of($model_name, 'AppModel')){
            /**  @var AppModel $this->$model_name */
            $this->$model_name = new $model_name();
        }
        else {
            //default model (no database table chosen)
            $this->$model_name = new AppModel();
        }
        /* Get all posts */
        $this->posts = $this->$model_name->getPosts();
        
        Config::set('view', strtolower($model_name));
        
        if(!$this->session->get(strtolower($model_name))){
            $this->session->set(strtolower($model_name), array());
        }
        
    }
    
    private function getPublicMethods(){
        $methods = array();
        $r = new ReflectionObject($this);
        $r_methods = $r->getMethods(ReflectionMethod::IS_PUBLIC);
        foreach($r_methods as $method){
            if($method->class !== 'AppController'){ //get only public methods from extended class
                $methods[] = $method->name;
            }
        }
        return $methods;
    }
    
    /**
     *
     * @param Template $TEMPLATE 
     */
    public function setTemplate(Template $TEMPLATE){
        $this->template = $TEMPLATE;
        $model_name = $this->name;
        $this->setHelpers();
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
    public function displayPage($args)  {
        Config::set('method', $this->pageOn);
        $public_methods = $this->getPublicMethods();
        if(in_array($this->pageOn, $public_methods))    {  
            call_user_func_array(array($this, $this->pageOn), $args);
        }
        else    {
            if(Config::get('view') == strtolower(__CLASS__) || 
                    !in_array($this->pageOn, $public_methods)){
                header("HTTP/1.1 404 Not Found");
            }
            else {
                Config::set('method', '../missingfunction'); //don't even allow trying the page
                return($this->getErrorPage(Config::get('view')."/{$this->pageOn} does not exist."));
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
    protected function getErrorPage($msg = null)    {
        $err = '<div class="error errors">%s</div>';
        return sprintf($err, $msg);
    }
    
    protected function setHelpers(){
        $helpers = array();
        foreach($this->helpers as $helper){
            $help = "{$helper}Helper";
            if(class_exists($help) && is_subclass_of($help, 'Helper')){
                $this->$helper = new $help();
                $helpers[$helper] = $this->$helper;
            }
        }
        $this->template->set('helpers', (object) $helpers);
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
    protected function validateForm($validate = null, $values = null, $exit = true){
        
        $this->validator = new FormValidator(); //create new validator
        
        if($validate == null){
            $validate = $this->validate;
        }
        
        foreach($validate as $field => $rules){
            foreach($rules as $validate=>$message){
                $this->validator->addValidation($field, $validate, $message);
            }
        }
        
        return $this->doValidate($values, $exit);
    }
    
    protected function doValidate($values = null, $exit = true){
        if(!(!isset($_POST) || count($_POST) == 0)){
            //some form was submitted
            if(!$this->validator->ValidateForm($values)){
                $error = '';
                $error_hash = $this->validator->GetErrors();
                foreach($error_hash as $inpname => $inp_err)
                {
                  $error .= $inp_err.PHP_EOL;
                }
                return $this->makeError($error, $exit);                
            }
        }
        return true;
    }
    
    protected function makeError($str, $exit = true){
        $return = $this->getErrorPage(nl2br($str));
        if($exit) exit($return);
        return $return;
    }
    
    protected function killPage(){ //Throw a 404 for the page
        header("HTTP/1.1 404 Not Found");
        exit;
    }
}

