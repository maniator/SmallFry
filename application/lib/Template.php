<?php
namespace SmallFry\lib; 
/**
 * Description of Template
 *
 * @author nlubin
 */
class Template {
    
    protected $templateVariables = array();
    protected $CONFIG;
    
    /**
     * @param \stdClass $PATH
     * @param SessionManager $SESSION
     * @param AppController $CONTROLLER
     * @param Config $CONFIG 
     */
    public function __construct(\stdClass $PATH, SessionManager $SESSION, AppController $CONTROLLER, Config $CONFIG) {
        $this->CONFIG = $CONFIG;
        $this->_path = $PATH;
        $this->session = $SESSION;
        $this->_controller = $CONTROLLER;
        $this->_controller->setTemplate($this);
        $this->_controller->init();
    }
    
    /**
     * @param array $render_args 
     */
    public function renderTemplate($render_args){        
        //LOAD VIEW
        ob_start();
        $this->loadView();
        //END LOAD VIEW
        
        //LOAD TEMPLATE
        $main = ob_get_clean();
        $this->CONFIG->set('main', $main);
        $this->loadTemplate($render_args);
        //END LOAD TEMPLATE
        
        //DISPLAY DEBUG LOG
        DebugLogger::destruct();
        //END DEBUG LOG
    }
    
    /**
     * @param array $render_args 
     */
    private function loadTemplate($render_args){
        
        $page_title = $this->get('title') ? $this->get('title') : $this->CONFIG->get('DEFAULT_TITLE');
        $viewLocal = $this->CONFIG->get('VIEW_LOCAL');
        $viewLocal = $viewLocal ?: "view";
        //display output
        $template_file = $viewLocal . '/' . $this->CONFIG->get('template') . '.stp';
    
	//FOR CACHE STORAGE
	$cacheName = $viewLocal . '__' . $this->CONFIG->get('view') . '_' . $this->CONFIG->get('method');
	if(count($render_args)){
	    $cacheName .= '--' . (implode('_', $render_args));
	}
	$cacheStorage = new CacheStorage($cacheName, $this->CONFIG->get('USE_CACHE') || $this->get('USE_CACHE'));
	//END CACHE STORAGE SETUP
	
	ob_start();
	
        if(is_file($template_file)){
            include $template_file;
        }
        else {
            include $viewLocal . '/missingfile.stp'; //no such file error
        }
	
	$cacheStorage->store_cache(ob_get_contents());

        ob_end_flush();
    }
    
    private function loadView(){   
        
        $viewLocal = $this->CONFIG->get('VIEW_LOCAL');
        $viewLocal = $viewLocal ?: "view";     

        //Bring the variables to the local scope
        extract($this->getAll(), EXTR_SKIP | EXTR_REFS); //load all variables into local scope (dont overwrite variables in scope)
        
        if($this->CONFIG->get('view')){
            $template_file = $viewLocal . '/' . $this->CONFIG->get('view') . '/' . $this->CONFIG->get('method') . '.stp';
            
            if(is_file($template_file)){
                include $template_file;
            }
            else {
                include $viewLocal . '/missingview.stp'; //no such view error
            }
        }
        else {
            $this->CONFIG->set('template', 'blank');
            include $viewLocal . '/missingfunction.stp'; //no such function error
        }
        
    }
    
    /**
     * @param string $name
     * @return &object
     */
    public function &get($name){
        if(array_key_exists($name, $this->templateVariables)){
            return $this->templateVariables[$name];
        }
        $this->templateVariables[$name] = false;
        return $this->templateVariables[$name];
    }
    
    /**
     * @param string $v
     * @param object $va
     * @return object 
     */
    public function set($v, $va){
        return $this->templateVariables[$v] = $va;
    }
    
    public function setRef($v, &$va){   //set by reference
        return $this->templateVariables[$v] = &$va;
    }
    
    /**
     * @return array 
     */
    public function &getAll(){
        return $this->templateVariables;
    }
    
    /**
     * @param string $str
     * @return string 
     */
    public function camelToWords($str){
        return ucwords(preg_replace('/(?!^)[[:upper:]][[:lower:]]/', ' $0', preg_replace('/(?!^)[[:upper:]]+/', ' $0', $str)));
    }
}
