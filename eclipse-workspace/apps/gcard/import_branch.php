<?php
	require_once 'include/DB_Connect.php';
	$db = DB();
	
	// json response array
	$response = array("error" => FALSE);
	$json_response = array();

	$stmt = $db->query("SELECT a.*, b.* FROM Branch a INNER JOIN Branch_Others b ON a.sBranchCd = b.sBranchCD 
	WHERE NOT a.cWareHous = '1' AND a.cRecdStat = '1' AND b.cDivision IN ('0', '1')");

	//echo $num_rows = $stmt->rowCount();	
	$row_array = array();
	while ($branch = $stmt->fetch()) 
	{  
		// Fetch data of Fname Column and store in array of row_array  		
		$row_array["error"] = FALSE;	
		$row_array["Code"] = $branch["sBranchCd"];
		$row_array["Branch_Name"] = $branch["sBranchNm"];
		$row_array["Description"] = $branch["sDescript"];
		$row_array["Address"] = $branch["sAddressx"];
		$row_array["TelNo"] = $branch["sTelNumbr"];
		$row_array["ContactNo"] = $branch["sContactx"];
		$row_array["EmailAd"] = $branch["sEMailAdd"];
		
		//push the values in the array  	
	
		array_push($json_response,$row_array);  
	}
 
	echo json_encode($json_response, JSON_PARTIAL_OUTPUT_ON_ERROR); 	
	echo json_last_error_msg();

?>
