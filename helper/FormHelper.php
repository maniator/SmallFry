<?php
namespace SmallFry\helper;
/**
 * Description of FormHelper
 *
 * @author nlubin
 */
class FormHelper extends Helper {
    
    private $_action = null, $_method = null, $_id = null, $_class = null;
    private $_form_elements;
    
    function newForm($action = null, $method = 'post', $id = null, $class = null){
        $this->_action = WEBROOT."index.php/".$action;
        $this->_class = $class;
        $this->_id = $id;
        $this->_method = $method;
        $this->_form_elements = array();
    }
    
    function appendSelect($options, $name, $class = null, $id = null, $selected = null, $withLabel = false, $default = "-- Options --"){
        if(is_string($withLabel)){
            $lName = $withLabel;
        }
        else {
            $lName = $name;
        }
        $labelName = $this->camelToWords($lName);
        $label = "<label><span> $labelName: </span> %s </label>";
        
        if($id == null){
            $id = $name;
        }
        $select = "<select id='$id' name='$name' class='$class'><option value=''>$default</option>%s<select>";
        $opt_temp = "<option value='%s'>%s</option>";
        $opt_temp_selected = "<option value='%s' selected>%s</option>";
        $option_str = '';
        foreach($options as $value=>$option){
            if($value == $selected){
                $option_str .= sprintf($opt_temp_selected, $value, $option);
            }
            else {
                $option_str .= sprintf($opt_temp, $value, $option);
            }
        }
        
        $select_box = sprintf($select, $option_str);
        if($withLabel){
            $select_box = sprintf($label, $select_box);
        }
        $this->_form_elements[] = $select_box;
    }
    
    function appendInput($value = 1, $name = 'nothing', $type = 'text', $class = null, $id = null, $withLabel = false){
        if(is_string($withLabel)){
            $lName = $withLabel;
        }
        else {
            $lName = $name;
        }
        $labelName = $this->camelToWords($lName);
        $label = "<label><span> $labelName: </span> %s </label>";
        
        if($id == null){
            $id = $name;
        }
        if($type != 'textarea'){
            $input = "<input type='$type' id='$id' name='$name' class='$class' value='%s'/>";
        }
        else    {
            $input = "<textarea id='$id' name='$name' class='$class'>%s</textarea>";
        }
        if($withLabel && $type != 'hidden'){
            $input = sprintf($label, $input);
        }
        $this->_form_elements[] = sprintf($input, $value);
    }
    
    function appendRadio($type = 'radio', $value = 1, $othervalue = 0, $name = '', $checked = false, $class = null, $id = null, $withLabel = false){
        if(is_string($withLabel)){
            $lName = $withLabel;
        }
        else {
            $lName = $name;
        }
        $labelName = $this->camelToWords($lName);
        $label = "<label><span> $labelName: </span> %s </label>";
        
        if($id == null){
            $id = $name;
        }
        $input = '';
        if($othervalue !== null) {
            $input = "<input type='hidden' name='$name' value='$othervalue'/>";
        }
        $input .= "<input type='$type' id='$id' name='$name' class='$class' value='%s' %s/>";
        if($withLabel && $type != 'hidden'){
            $input = sprintf($label, $input);
        }
        
        $this->_form_elements[] = sprintf($input, $value, ($checked)?'checked':'');
    }
    
    function showForm($after = null){
        $fTemp = "<form action='%s' method='%s' id='%s' class='%s'>\n%s\n%s</form>";
        return sprintf($fTemp, $this->_action, $this->_method, $this->_id, $this->_class, implode("\n", $this->_form_elements), $after);
    }
}

?>
