<?php
namespace SmallFry\helper;

/**
 * Description of Helper
 *
 * @author nlubin
 */
class Helper {
    
    protected function camelToWords($str){
        return ucwords(preg_replace('/(?!^)[[:upper:]][[:lower:]]/', ' $0', preg_replace('/(?!^)[[:upper:]]+/', ' $0', $str)));
    }
}

?>
