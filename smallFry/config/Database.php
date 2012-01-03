<?php

//DATABASE CONNECT
class Database {
	private static $database_info = array(
		'host' => '',
		'login' => '',
		'password' => '',
		'database' => '',
	);
        
        private static $_mysql = null; 
        
        private static function connect(){
            self::$_mysql =  new MySQL(self::$database_info['host'], self::$database_info['login'], 
                                   self::$database_info['password'], self::$database_info['database']);
        }
        
        /**
         * Return MySQL connection
         * @return MySQL
         */
        public static function getConnection(){
            if(self::$_mysql == null){
                self::connect(); //connect if not connected yet
            }
            return self::$_mysql;
        }
}
//END DATABASE CONNECT
