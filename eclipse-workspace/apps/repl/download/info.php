<?php 
	//Module: download.info.php
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
											"  b.sFileName" .
											", b.sFileSize" .
											", b.sMD5Hashx" .
											", a.dCreatedx" .
						  " FROM xxxOutGoingDetail b" .
						  		" LEFT JOIN xxxOutGoingMaster a ON a.sBatchNox = b.sBatchNox" .
						  " WHERE b.sBranchCD = '" . $_GET["branch"] . "'" .
						    " AND b.sFileName <> ''" . 
						    " AND b.cRecdStat = '1'" .
						  " ORDER BY b.sBatchNox";
			if ($result = $connection->query($query)){
				if($result->num_rows > 0){
					$json = json_encode($result->fetch_assoc());
				} 
			} //if ($result = $connection->query($query)){
		
			$connection->close();
		
		} //if ($connection->connect_error){
	} //if(strlen(trim($_GET["branch"])) == 4){
	
	if($json == null)
			$json = json_encode(array('sFileName' => '', 'sMD5Hashx' => '', 'dCreatedx' => '1901-01-01'));
	
	echo $json;
?>
