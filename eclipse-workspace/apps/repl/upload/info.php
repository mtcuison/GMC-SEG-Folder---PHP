<?php 
	//Module: upload.info.php
  //Author: kalyptus
  //Date History:
  		//2014.11.08 01:19pm
  		//	started creating this object. 

	include '../lib/gdb_connection.php';
  $data = file_get_contents('php://input');
	$json = json_decode($data);
	if(!is_null($json)){	
		$connection = gdb_connection::getConnection();
		
		if ($connection->connect_error){
			echo 'Connect Error';
			die('Connect Error: ' . $connection->connect_error);
		} 
		else{
			$query = "INSERT INTO xxxIncomingLog" .
							" SET sFileName = '" . $json->sFileName . "'" .
							   ", sLogFromx = '" . $json->sLogFromx . "'" .
							   ", sLogThrux = '" . $json->sLogThrux . "'" .
							   ", sFileSize = '" . $json->sFileSize . "'" .
							   ", sMD5Hashx = '" . $json->sMD5Hashx . "'" .
							   ", dCreatedx = '" . date('Y-m-d H:i:s') . "'" . 
							   ", cTranStat = '0'" .
							" ON DUPLICATE KEY UPDATE sMD5Hashx = '" . $json->sMD5Hashx . "'";

			if ($connection->query($query) === true){
				echo "success";	
			} //if ($result = $connection->query($query)){
			else{
			   echo $query;
			}
			
			$connection->close();
			
		} //if ($connection->connect_error){
	} //if(!is_null($json)){
	else{
   	   echo $data;
	}


?>
