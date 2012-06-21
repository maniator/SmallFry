<?php
/**
 * Description of Template
 *
 * @author nlubin
 */
class Template {
    
    protected $templateVariables = array();
    protected $CONFIG;
    
    public function __construct(stdClass $PATH, SessionManager $SESSION, AppController $CONTROLLER, Config $CONFIG) {
        $this->CONFIG = $CONFIG;
        $this->_path = $PATH;
        $this->session = $SESSION;
        $this->_controller = $CONTROLLER;
        $this->_controller->setTemplate($this);
        $this->_controller->init();
    }
    
    public function renderTemplate(){        
        //LOAD VIEW
        ob_start();
        $this->loadView();
        //END LOAD VIEW
        
        //LOAD TEMPLATE
        $main = ob_get_clean();
        $this->CONFIG->set('main', $main);
        $this->loadTemplate();
        //END LOAD TEMPLATE
        
        //DISPLAY DEBUG LOG
        DebugLogger::destruct();
        //END DEBUG LOG
    }
    
    
    private function loadTemplate(){
        
        $page_title = $this->get('title') ? $this->get('title') : $this->CONFIG->get('DEFAULT_TITLE');
        //display output
        $template_file = 'view/' . $this->CONFIG->get('template') . '.stp';

        if(is_file($template_file)){
            include $template_file;
        }
        else {
            include 'view/missingfile.stp'; //no such file error
        }
        
    }
    
    private function loadView(){        

        //Bring the variables to the local scope
        extract($this->getAll(), EXTR_SKIP); //load all variables into local scope (dont overwrite variables in scope)

        if($this->CONFIG->get('view')){
            $template_file = 'view/' . $this->CONFIG->get('view') . '/' . $this->CONFIG->get('method') . '.stp';
            if(is_file($template_file)){
                include $template_file;
            }
            else {
                include 'view/missingview.stp'; //no such view error
            }
        }
        else {
            $this->CONFIG->set('template', 'blank');
            include 'view/missingfunction.stp'; //no such function error
        }
        
    }
    
    public function get($v){
        return isset($this->templateVariables[$v]) ? $this->templateVariables[$v] : false;
    }
    
    public function set($v, $va){
        return $this->templateVariables[$v] = $va;
    }
    
    public function getAll(){
        return $this->templateVariables;
    }
    
    public function camelToWords($str){
        return ucwords(preg_replace('/(?!^)[[:upper:]][[:lower:]]/', ' $0', preg_replace('/(?!^)[[:upper:]]+/', ' $0', $str)));
    }
}
