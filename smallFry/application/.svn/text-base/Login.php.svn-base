<?php

/**
 * Description of login
 *
 * @author nlubin
 */
class Login {
    
    private static $_ms, $_display = '';
    private $_login_seed,
            $_error = '', $_login_allowed_time = "4 DAY";
    
    public function __construct() {
        $this->_login_seed = App::get('LOGIN_SEED');
        self::$_ms = Database::getConnection();
    }
    
    function check_for_login(){
        if(isset($_POST) && isset($_POST['submit_login'])){
            return $this->check_submit();
        }
        else {
            return $this->get_login_table();
        }
    }
    
    public static function check_login(){
        $_SESSION[App::get('APP_NAME')]['loggedin'] = true;
        $_SESSION['LOGIN'] = true;
        header("Location: index.php");
    }
    
    private static function __displayError($msg){
	self::$_display .= "<script type='text/javascript'>
                $(document).ready(function(){
                    $('#errBox').dialog({modal:true}).html('$msg');
                })
            </script>";
    }
}

?>
