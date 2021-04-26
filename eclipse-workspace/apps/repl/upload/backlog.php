<?php 
	//Module: upload.backlog.php
  //Author: kalyptus
  //Date History:
  		//2014.11.08 01:19pm
  		//	started creating this object. 


	include '../lib/gdb_connection.php';

	$json = NULL;
	
	//make sure that we have a correct branch
	if(isset($_GET["branch"]) && strlen(trim($_GET["branch"])) == 4){ 
		
		$connection = gdb_connection::getConnection();
		if ($connection->connect_error){
			die('Connect Error: ' . $connection->connect_error);
		} 
		else{
			$query = "SELECT" .
											"  sFileName" .
											", sMD5Hashx" .
											", cTranStat" .
						  " FROM xxxIncomingLog" .
						  " WHERE sFileName LIKE '" . $_GET["branch"] . "%'" .
						    " AND cTranStat IN('0')"; 
			if ($result = $connection->query($query)){
				if($result->num_rows > 0){
					$json = json_encode($result->fetch_assoc());
				} 
				
			} //if ($result = $connection->query($query)){
			$connection->close();
		
		} //if ($connection->connect_error){
	} //if(strlen(trim($_GET["branch"])) == 4){

	if($json == null)
			$json = json_encode(array('sFileName' => '', 'sMD5Hashx' => '', 'cTranStat' => '1'));

	echo $json;
?>
