<?php
	require_once 'include/DB_Connect.php';
	$db = DB();
	
	$json_response = array();
	$json = file_get_contents('php://input');
	
	//current date
	date_default_timezone_set('Asia/Manila');		
	$dateNow = date("Y-m-d");
	
	//decoding of json
	$obj = json_decode($json);
	$mobile_no = $obj->{'mobile_no'};
	
	$stmt = $db->prepare("Select * from G_Card_App_Number where sMobileNo = ? ");
	$stmt->bindParam(1, $mobile_no);
	$stmt->execute();
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	

	if($row > 0){
			//existing na yung mobile no. do nothing
			
	}else{
		$uuid = uniqid('', true);
		$member = '0';
		
		$stmt = $db->prepare('INSERT INTO G_Card_App_Number (sTransNox, dTransact, sMobileNo, cMemberxx) 
		VALUES (:uuid, :dateNow, :mobile_no,  :member)');
		
		if (!empty($mobile_no)) {
			$result = $stmt->execute(array(
				'uuid' => $uuid,
				'dateNow' => $dateNow, 
				'mobile_no' => $mobile_no,				
				'member' => $member				
			));	
		}else{
			
		}		
	}
	
?>