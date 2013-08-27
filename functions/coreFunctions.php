<?php
/**
 * Returns uncamelcased string
 * @param $string  string
 * @return string uncamelcased string
 */
function _uncamel($string){
    return ucwords(preg_replace('/(?!^)[[:upper:]][[:lower:]]/', ' $0', preg_replace('/(?!^)[[:upper:]]+/', ' $0', $string)));
}

/**
 * Shorthand for print_r
 * @param mixed $var
 * @param bool $pre
 * @return bool
 */
function _pr($var, $pre = true, $return = false) {
    if($pre) {
        $print = "<pre>%s</pre>";
    }
    else {
        $print = "%s";
    }
    $string = sprintf($print, print_r($var, true));
    if($return) {
        return $string;
    }
    else {
        echo $string;
        return true;
    }
}

use \SmallFry\lib\Pluralize;
function pluralize($str){
    return Pluralize::pluralize($str);
}

function flush_buffers(){
    ob_end_flush();
    ob_flush();
    flush();
    ob_start();
}