<?php 
	//Module: download.success.php
  //Author: kalyptus
  //Date History:
  		//2014.11.08 01:19pm
  		//	started creating this object. 
	
	include '../lib/gdb_connection.php';

	$json = json_decode(file_get_contents('php://input'));

	if(!is_null($json)){	
		$connection = gdb_connection::getConnection();
		
		if ($connection->connect_error){
			die('Connect Error: ' . $connection->connect_error);
		} 
		else{
			$query = "UPDATE xxxOutGoingDetail" .
						" SET cRecdStat = '2'" . 
						   ", dDownload = '" . date('Y-m-d H:i:s') . "'" . 
						" WHERE sFileName = '" . $json->sFileName . "'";

			if ($connection->query($query) === true){
				echo "success";	
			} //if ($result = $connection->query($query)){
			
			$connection->close();
			
		} //if ($connection->connect_error){
	} //if(!is_null($json)){

?>
