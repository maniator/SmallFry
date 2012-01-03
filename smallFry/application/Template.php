<?php
/**
 * Description of Template
 *
 * @author nlubin
 */
class Template {
    
    protected $template_vars = array();
    
    public function __construct(stdClass $PATH, SessionManager $SESSION, AppController $CONTROLLER) {
        $this->_path = $PATH;
        $this->_session = $SESSION;
        $this->_controller = $CONTROLLER;
        $this->_controller->setTemplate($this);
        $this->_controller->init();
    }
    
    private function render_template(){        
        //LOAD VIEW
        ob_start();
        $this->load_view();
        //END LOAD VIEW
        
        //LOAD TEMPLATE
        $main = ob_get_clean();
        Config::set('main', $main);
        $this->load_template();
        //END LOAD TEMPLATE
    }
    
    
    private function load_template(){
        
        $page_title = $this->get('title')?$this->get('title'):Config::get('DEFAULT_TITLE');
        //display output
        $cwd = dirname(__FILE__);
        $template_file = $cwd.'/../../view/'.Config::get('template').'.stp';

        if(is_file($template_file)){
            include $template_file;
        }
        else {
            include $cwd.'/../../view/missingfile.stp'; //no such file error
        }
    }
    
    private function load_view(){        
        
        //Bring the variables to the global scope
        extract($this->getAll()); //load all variables into local scope
        $cwd = dirname(__FILE__);
        if(Config::get('view')){
            $template_file = $cwd.'/../../view/'.Config::get('view').'/'.Config::get('method').'.stp';
            if(is_file($template_file)){
                include $template_file;
            }
            else {
                include $cwd.'/../../view/missingview.stp'; //no such view error
            }
        }
        else {
            Config::set('template', 'blank');
            include $cwd.'/../../view/missingfunction.stp'; //no such function error
        }
    }
    
    public function get($v){
        return isset($this->template_vars[$v])?$this->template_vars[$v]:false;
    }
    
    public function set($v, $va){
        return $this->template_vars[$v] = $va;
    }
    
    public function getAll(){
        return $this->template_vars;
    }
    
    
    public function __destruct() {
        $this->render_template();
    }
}

