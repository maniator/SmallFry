<?php
namespace SmallFry\lib;
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
    protected $CONFIG;
    
    /**
     *
     * @param SessionManager $SESSION
     * @param Config $CONFIG
     * @param MySQL_Interface $firstHandle
     * @param MySQL_Interface $secondHandle 
     */
    public function __construct(SessionManager $SESSION, Config $CONFIG, AppModel $model, $modelCallback = null) {
        $this->CONFIG = $CONFIG;
        $this->session = $SESSION;
        if(isset($this->modelName)) {	//if the model is different than the controller name
	        if($modelCallback !== null && is_callable($modelCallback))  {
		        $model = $modelCallback($this->modelName);	//get real model
	        }
	    }

        $modelName = $model->getModelName();
        $this->$modelName = $model;
        /* Get all posts */
        $this->posts = $model->getPosts();

        $view = isset($this->viewName) ? $this->viewName : $modelName;
        $this->CONFIG->set('view', strtolower($view));
        $this->setHelpers();
        
        if(!$this->session->get(strtolower($modelName))){
            $this->session->set(strtolower($modelName), array());
        }
    }
    
    private function getPublicMethods(){
        $methods = array();
        $r = new \ReflectionObject($this);
        $r_methods = $r->getMethods(\ReflectionMethod::IS_PUBLIC);
        $notAllowedMethods = array("__construct", "init", "__destruct"); //list of methods that CANNOT be a view and are `keywords`
        foreach($r_methods as $method){
            if($method->class !== 'SmallFry\lib\AppController' && !in_array($method->name, $notAllowedMethods)){ 
                //get only public methods from extended class
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
        $helpers = array();
        foreach($this->helpers as $helper){
            $help = "{$helper}Helper";
            if(isset($this->$help)) {
                $helpers[$helper] = $this->$help;
            }
        }
        $this->template->set('helpers', (object) $helpers);
    }
    
    /**
     * Function to run before the constructor's view function
     */
    public function init(){} //function to run right after constructor
    
    
    public function setPage($pageName)	{
	$this->page = $pageName;
    }
    /**
     * Show the current page in the browser
     *
     * @param array $args
     * @return string 
     */
    public function displayPage($args)  {
        $this->CONFIG->set('method', $this->page);
        $public_methods = $this->getPublicMethods();
        if(in_array($this->page, $public_methods))    {
            call_user_func_array(array($this, $this->page), $args);
        }
        else    {
	    throw new \Exception("{$this->name}/{$this->page} does not exist.");
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
        foreach($this->helpers as $helper){
            $help = "{$helper}Helper";
            $nameSpacedHelper = "SmallFry\\helper\\$help";
            if(class_exists($nameSpacedHelper) && is_subclass_of($nameSpacedHelper, 'SmallFry\\helper\\Helper')){
                $this->$help = new $nameSpacedHelper();
            }
        }
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

