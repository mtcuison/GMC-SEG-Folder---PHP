<?php
	require_once 'include/DB_Connect.php';
	$db = DB();
		
	$gcard_number = $_POST['gcard_number'];
	$gcard_number = htmlspecialchars($gcard_number);
	
	$response = array("error" => FALSE);
	$json_response = array();
		
	$stmt = $db->prepare("Select sApplicNo from G_Card_Master where sCardNmbr = ? ");
	$stmt->bindParam(1, $gcard_number);
	$stmt->execute();
		
	while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
		$applicNo = $row["sApplicNo"];		
	}
	
	$stmt = $db->prepare("Select sSerialID  from G_Card_Application where sTransNox = ?");
	$stmt->bindParam(1, $applicNo);
	$stmt->execute();
	
	while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
		$serialID = $row["sSerialID"];
			
	}
	
	$stmt = $db->prepare("Select sCRNoxxxx  from MC_Registration where sSerialID = ?");
	$stmt->bindParam(1, $serialID);
	$stmt->execute();
	
	while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
		$crNox = $row["sCRNoxxxx"];
			
	}
	
	//ORSTAT: 0 NON_ORCR
	//ORSTAT: 1 ORCR
	
	$non_orcr = "0";
	$or_cr = "1";
	if (empty($crNox)){
		//orcr is no yet available		
		$row_array["error"] = FALSE;
		$row_array["Id"]= "";
		$row_array["Last_service"] = "";
		$row_array["Milage"] = "";
		$row_array["Next_service"] = "";
		$row_array["orStat"] = $non_orcr;
		array_push($json_response,$row_array);	
		
		echo json_encode($json_response); 	
	}else{
		//proceed to importing service
		$stmt = $db->prepare("SELECT * FROM Hotline_Reminder_Source where sSerialID = ? ");
		$stmt->bindParam(1, $serialID);
		$stmt->execute();
		
		while($service = $stmt->fetch(PDO::FETCH_ASSOC)){
			$row_array["error"] = FALSE;
			$row_array["Id"] = $service["sSerialID"];
			$row_array["Last_service"] = $service["dLastSrvc"];
			$row_array["Milage"] = $service["nMilagexx"];
			$row_array["Next_service"] = $service["dNxtRmndS"];
			$row_array["orStat"] = $or_cr;
			array_push($json_response,$row_array);			
		}
		echo json_encode($json_response); 	
	}
	
	//echo $applicNo. '<br>'. $serialID. '<br>'. $crNox; 
	
	
?>