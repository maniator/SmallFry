<?php
namespace SmallFry\lib; 

/**
 * Description of MySQL_Interface
 *
 * @author nlubin
 */
interface MySQL_Interface {
    
    /**
     * @return string
     */
    public function getSchemaName();
    public function run_query($query);
    public function get_row($result = null, $fetchby = MYSQLI_BOTH);
    public function get_bound_row(&$result, $fetchby);
    public function get_num_rows($result = null);
    public function get_last_insert_id();
    public function close_result($result = null);
    public function get_last_error($show_error = true);
    public function start_transaction();
    public function rollback();
    public function commit();
    public function prepare($query);
}

interface stmt_Extended {} //interface so that both PDO and mysqli classes work fully