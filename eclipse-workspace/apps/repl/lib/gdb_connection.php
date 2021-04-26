<?php 
	//Module: lib.gdb_connection.php
  //Author: kalyptus
  //Date History:
  		//2014.11.08 01:19pm
  		//	started creating this object.
  		
class gdb_connection
{
	private static $_mysqlUser = 'sa';
	private static $_mysqlPass = 'Atsp,imrtptd';
	private static $_mysqlDb = 'GGC_ISysDBF';
	private static $_hostName = 'localhost';

	protected static $_connection = NULL;

	private function __construct(){}

	public static function getConnection() {
		if (!self::$_connection) {
			self::$_connection = new mysqli(self::$_hostName, self::$_mysqlUser, self::$_mysqlPass, self::$_mysqlDb);
	
			if (self::$_connection->connect_error) {
				die('Connect Error: ' . self::$_connection->connect_error);
			}
		}
		return self::$_connection;
	}
	
}
?>