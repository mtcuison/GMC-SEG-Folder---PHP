<?php
	require_once 'include/DB_Connect.php';
	$db = DB();
	
	// json response array
	$response = array("error" => FALSE);
	$json_response = array();

	
	//$stmt = $db->query("SELECT * FROM Branch INNER JOIN Branch_Others ON Branch.sBranchCd = Branch_Others.sBranchCD 
	//WHERE NOT cWareHous = '1'");
	
	$stmt = $db->query("SELECT * FROM Branch WHERE cWareHous in ('0') and sBranchCd like 'M%'");

	//echo $num_rows = $stmt->rowCount();	
	while ($branch = $stmt->fetch()) 
	{  
		// Fetch data of Fname Column and store in array of row_array  		
		$row_array["error"] = FALSE;	
		$row_array["BranchCode"] = $branch["sBranchCd"];
		$row_array["Branch"] = $branch["sBranchNm"];
		$row_array["Address"] = $branch["sAddressx"];
		
		//push the values in the array  	
	
		array_push($json_response,$row_array);  
	} 
	echo json_encode($json_response, JSON_PARTIAL_OUTPUT_ON_ERROR); 	

?>
