<?php 
	//Module: msgcast.status.php
  //Author: kalyptus
  //Date History:
  		//2015.06.08 11:23am
  		//	started creating this object. 

	include '../lib/gdb_connection.php';

	
	$connection = gdb_connection::getConnection();
	if ($connection->connect_error){
		die('Connect Error: ' . $connection->connect_error);
	} 
	else{
		
			$query = "INSERT INTO MsgCast_Delivery_Notification" .
						" SET sTransIDx = '" . $_POST["transid"] . "'" . 
						   ", sMsgIDxxx = '" . $_POST["msgid"] . "'" . 
						   ", sMSISDNxx = '" . $_POST["msisdn"] . "'" . 
						   ", nMsgCount = " . $_POST["msgcount"] .  
						   ", dTransact = '" . $_POST["dateprocessed"] . "'" . 
						   ", sStatusxx = '" . $_POST["status"] . "'" ; 

			if ($connection->query($query) === true){
				echo 200;	
			} //if ($result = $connection->query($query)){
	
		$connection->close();
	
	} //if ($connection->connect_error){
?>
