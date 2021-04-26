<?php 
	//Module: report_error.php
  //Author: kalyptus
  //Date History:
  		//2015.01.06 09:43am
  		//	started creating this object. 

	include '../lib/gdb_connection.php';
  $data = file_get_contents('php://input');
	$json = json_decode($data);
	$stat = "";
	$resp = "";
	$othx = "";
	
	if(!is_null($json)){	
		$connection = gdb_connection::getConnection();
		
		if ($connection->connect_error){
			echo 'Connect Error';
			die('Connect Error: ' . $connection->connect_error);
		} 
		else{
			$query = "SELECT *" .
			        " FROM xxxReplication_Error" .
			        " WHERE sFileName = '" . $json->sFileName . "'" . 
			          " AND sRefernox = '" . $json->sRefernox . "'" . 
			          " AND nErrorNox = " . $json->nErrorNox;
			$result = $connection->query($query);
			
			if(!$result){
				$othx = $query;
			}
			else{
				if($result->num_rows > 0){
					$queryx = "UPDATE xxxReplication_Error" .
									" SET nRptCtrxx = nRptCtrxx + 1" .
									   ", dLastRptx = CURRENT_TIMESTAMP()" .
			        	  " WHERE sFileName = '" . $json->sFileName . "'" . 
			          	  " AND sRefernox = '" . $json->sRefernox . "'" . 
			          	  " AND nErrorNox = " . $json->nErrorNox;									   
				}
				else{
					$queryx = 'INSERT INTO xxxReplication_Error' .
									' SET sBranchCD = "' . $json->sBranchCD . '"' . 
										 ', sFileName = "' . $json->sFileName . '"' . 
			          	   ', sRefernox = "' . $json->sRefernox . '"' . 
			          	   ', nErrorNox = ' . $json->nErrorNox .
			          	   ', sDescript = "' . $json->sDescript . '"' .
			          	   ', dEntryDte = CURRENT_TIMESTAMP()' . 
										 ', nRptCtrxx = 1' .
									   ', dLastRptx = CURRENT_TIMESTAMP()'; 
				}
			
				$resultx = $connection->query($queryx);
				
				$othx = $queryx;
				
				if(!$resultx) 
					$stat = "error";
				else{	
					$stat = "success";
					
					//fetch result of select from above
					$row = $result->fetch_array(MYSQLI_ASSOC);

				  $resp = $row["sStatemnt"]; 
				}
			}
			
			$connection->close();
			
		} //if ($connection->connect_error){
	} //if(!is_null($json)){
	else{
   	  $othx = $data;
	    $stat = "error";
	}
	
	$json = array(
    'sStatusxx' => $stat,
    'sResponse' => $resp,
    'sRemarksx' => $othx
    );
	echo json_encode($json);	

?>
