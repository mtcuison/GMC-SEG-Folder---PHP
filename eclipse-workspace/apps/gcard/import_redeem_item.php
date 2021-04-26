<?php
	require_once 'include/DB_Connect.php';
	$db = DB();
	
	// json response array
	$response = array("error" => FALSE);
	$json_response = array();

	$sql = "SELECT" .
								"  a.sTransNox" . 
								", b.sPartsIDx" .
								", a.dDateThru" . 
								", a.dDateFrom" .
								", a.sPromDesc" . 
								", a.nPointsxx" .
				" FROM G_Card_Promo_Master a" .
					" INNER JOIN G_Card_Promo_Detail b ON a.sTransNox = b.sTransNox" . 
				" WHERE dDateThru > Now()";	  
	
	$stmt = $db->prepare($sql);
	$stmt->execute();

	while ($redeem = $stmt->fetch(PDO::FETCH_ASSOC)) 
	{  
		// Fetch data of Fname Column and store in array of row_array  		
		$row_array["error"] = FALSE;		
		$row_array["TransNox"] = $redeem["sTransNox"];
		$row_array["Id"] = $redeem["sPartsIDx"];
		$row_array["Date_thru"] = $redeem["dDateThru"];
		$row_array["Date_from"] = $redeem["dDateFrom"];
		$row_array["Description"] = $redeem["sPromDesc"];
		$row_array["Points"] = $redeem["nPointsxx"];
		
		//push the values in the array  	
	
		array_push($json_response,$row_array);  
	} 
	//echo json_encode($json_response, JSON_PARTIAL_OUTPUT_ON_ERROR); 	
	echo json_encode($json_response, JSON_UNESCAPED_UNICODE); 	
	//echo $stmt->rowCount();
?>

