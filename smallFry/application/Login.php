<?php

/**
 * Description of login
 *
 * @author nlubin
 */
class Login {
    
    private static $_ms, $_display = '';
    
    public function __construct(SessionManager $SESSION) {
        $this->_session = $SESSION;
    }
    
    public function check_login(){
        $this->_session->set('loggedin', true);
        $this->_session->set('LOGIN', true);
        header("Location: index.php");
    }
 
}

?>
