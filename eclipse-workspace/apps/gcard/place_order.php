<?php
	require_once 'include/DB_Connect.php';
	$db = DB();

	if (isset($_POST['gcard_number'])) {
		$stmt= $db->query("SET Names utf8");
		
		$gcard_number = $_POST['gcard_number'];	
		$gcard_number = htmlspecialchars($gcard_number);
		
		$mobile = $_POST['mobile'];	
		$mobile = htmlspecialchars($mobile);
		
		$json_response = array();

		$stmt = $db->prepare("Select * from G_Card_Master where sCardNmbr = ? ");
		$stmt->bindParam(1, $gcard_number);
		$stmt->execute();	
		
			while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
				$cardNox = $row["sGCardNox"];	
				$clientID = $row["sClientID"];	
			}		
		date_default_timezone_set('Asia/Manila');		
		$dPlaceOrdr = date("Y-m-d h:i:s");			
		$dPickupOrdr = date("Y-m-d h:i:s", strtotime('+15 days'));			
		
		$stmt = $db->prepare("Update G_Card_Order_Redeem Set dPlacOrdr = ?, dPickupxx = ?, cPlcOrder = ?, cTranStat = ?  where sGCardNox = ? AND cTranStat= ? ");
		
		
		//cPlcOrder:
		//0: not placed
		//1: order is placed
		//2: order removed from the list/ cancel
		
		//cTranStat:
		//0: not claimed
		//1: claimed
		//2: customer decides to cancel the order		
		
		$cPlacedOrder = "0";
		$cTranStat = '1';
		
		$tobeplace_cTranStat = '0';		
		
		$stmt->bindParam(1, $dPlaceOrdr);
		$stmt->bindParam(2, $dPickupOrdr);
		$stmt->bindParam(3, $cPlacedOrder);	
		$stmt->bindParam(4, $cTranStat);	
		$stmt->bindParam(5, $cardNox);	
		$stmt->bindParam(6, $tobeplace_cTranStat);	
		
		if($stmt->execute()){
			
			$stmt1 = $db->prepare("Update Client_Master Set sMobileNo = ? where sClientID = ?");
			$stmt1->bindParam(1, $mobile);
			$stmt1->bindParam(2, $clientID);
			//$stmt1->execute();
			
			$response["error"] = False;
			echo json_encode($response);
			
		}else{
			$response["error"] = TRUE;
			$response["error_msg"] = "Please try again!";
			echo json_encode($response, JSON_PARTIAL_OUTPUT_ON_ERROR);
		}
	}		
	else{
		
		// required post params is missing
		$response["error"] = TRUE;
		$response["error_msg"] = "Required parameters gcard_number or password is missing!";
		echo json_encode($response);
	}
	
?>