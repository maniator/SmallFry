<?php
namespace SmallFry\helper;
/**
 * Description of TableHelper
 *
 * @author nlubin
 */
class TableHelper extends Helper {
    
    private static $tables = array();
    private $_class = '', $_id = '', $_rows = array(), $_row_pointer = -1;
    private $_row_temp = "<tr id='%s' class='%s'>\n";

    /**
     *
     * @return TableHelper
     */
    static function createNewTable(){
        self::$tables[] = new self();
        return self::$tables[(count(self::$tables) - 1)];
    }
    
    function createTable(){
        return self::createNewTable();
    }
    
    function setClass($class){
        $this->_class = $class;
    }
    
    function setId($id){
        $this->_id = $id;
    }
    
    function newRow($class = '', $id = ''){
        if($this->_row_pointer >= 0){
            $this->_rows[$this->_row_pointer] .= "</tr>";
        }
        $this->_row_pointer++;
        $this->_rows[$this->_row_pointer] = sprintf($this->_row_temp, $id, $class);
    }
    
    function newFullRow($tds, $id = null, $class = null, $headers = false){
        $this->newRow();
        $this->appendCells($tds, $id, $class, $headers);
    }
    
    function newFullHeaderRow($tds, $id = null, $class = null, $headers = true){
        $this->newRow($class, $id);
        $this->appendCells($tds, $id, $class, $headers);
    }
    
    function appendCells($tds, $id = null, $class = null, $headers = false){
        $col = 'd';
        if($headers) $col = 'h';
        $td_tmp = "<t$col id='%s' class='%s'>%s</t$col>";
        $td_tr = "";
        foreach($tds as $td){
            if(!is_array($td))  {
                $td_tr .= sprintf($td_tmp, null, null, $td);
            }
            elseif(count($td) == 2)    {
                $td_tr .= sprintf($td_tmp, null, $td[1], $td[0]);
            }
            elseif(count($td) == 3)    {
                $td_tr .= sprintf($td_tmp, $td[1], $td[2], $td[0]);
            }
            $td_tr .= PHP_EOL;
        }
        $this->_rows[$this->_row_pointer] .= $td_tr;
    }
    
    function appendHeaderCells($tds, $id = null, $class = null){
        return $this->appendCells($tds, $id, $class, true);
    }
    
    function showTable(){
        if($this->_row_pointer >= 0){
            $this->_rows[$this->_row_pointer] .= "</tr>";
        }
        $table = "<table id='%s' class='%s'>\n%s\n</table>\n";
        return sprintf($table, $this->_id, $this->_class, implode("\n", $this->_rows));
    }
}

?>
