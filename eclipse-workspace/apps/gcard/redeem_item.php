<?php

require_once 'include/DB_Connect.php';
$db = DB();

//add unique id for json, change it into transnox eventually
 
	// json response array
	$response = array("error" => FALSE);
	 
	if (isset($_POST['item_quantity'])) {
		// receiving the post params
		$gcard_number = $_POST['gcard_number'];
		$gcard_number = htmlspecialchars($gcard_number);
		
		$stmt = $db->prepare("Select * from G_Card_Master where sCardNmbr = ? ");
		$stmt->bindParam(1, $gcard_number);
		$stmt->execute();			
		
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			$cardNox = $row["sGCardNox"];	
		}
	
		$parts_id  = $_POST['parts_id'];
		$parts_id = htmlspecialchars($parts_id);
		
		$item_quantity = $_POST['item_quantity'];
		$item_quantity = htmlspecialchars($item_quantity);
		
		$item_points = $_POST['item_points'];
		$item_points = htmlspecialchars($item_points);

		$branch_code = $_POST['branch_code'];
		$branch_code = htmlspecialchars($branch_code);

		$order_date = $_POST['order_date'];
		$order_date = htmlspecialchars($order_date);	

		$pickup_date = $_POST['pickup_date'];
		$pickup_date = htmlspecialchars($pickup_date);	
		
		$orderStat = $_POST['orderStat'];
		$orderStat = htmlspecialchars($orderStat);			

		$stmt= $db->query("SET Names utf8");
		$uuid = uniqid('', true);
		$stmt = $db->prepare("INSERT INTO G_Card_Order_Redeem(sTransNox, sGCardNox, sPartsIDx, nItemQtyx, nPointsxx, sBranchCd, cTranStat, cPlcOrder) 
			VALUES(?,?,?,?,?,?,?,?)");
		
		//orderStat "0" means redeem //7days pickup
		//orderStat "1" means order //15 days pickup
		
		//plcOrder "0" not yet placed
		//plcOrder "1" order done placed
		$plcOrder = "0";
			
		$stmt->bindParam(1, $uuid);		
		$stmt->bindParam(2, $cardNox);
		$stmt->bindParam(3, $parts_id);
		$stmt->bindParam(4, $item_quantity);
		$stmt->bindParam(5, $item_points);
		$stmt->bindParam(6, $branch_code);		
		$stmt->bindParam(7, $orderStat);
		$stmt->bindParam(8, $plcOrder);
		$result = $stmt->execute();	

		if($result){			
			//$stmt = $db->prepare("SELECT * FROM G_Card_Order_Redeem WHERE sUniqueID = ?");
			
			$stmt = $db->prepare("SELECT * FROM G_Card_Order_Redeem 
									INNER JOIN Branch 
									ON G_Card_Order_Redeem.sBranchCd = Branch.sBranchCd 		
									INNER JOIN Spareparts 
									ON G_Card_Order_Redeem.sPartsIDx = Spareparts.sPartsIDx 
									where sUniqueID=?");
			$stmt->bindParam(1, $uuid);
			$stmt->execute();
			$order = $stmt->fetch(PDO::FETCH_ASSOC);
			
			$response["error"] = FALSE;
			$response["order"]["uid"] = $order["sTransNox"];			
			$response["order"]["PartsId"] = $order["sPartsIDx"];
			$response["order"]["GcardNox"] = $order["sGCardNox"];
			$response["order"]["ItemQuantity"] = $order["nItemQtyx"];			
			$response["order"]["ItemDesc"] = $order["sDescript"];			
			$response["order"]["ItemPoints"] = $order["nPointsxx"];			
			$response["order"]["Branch"] = $order["sBranchNm"];
			$response["order"]["BranchCode"] = $order["sBranchCd"];
			$response["order"]["BranchAddress"] = $order["sAddressx"];
			$response["order"]["DateOrdered"] = $order["dOrderedx"];
			$response["order"]["DatePickup"] = $order["dPickupxx"];
			$response["order"]["OrderStat"] = $order["cTranStat"];
			$response["order"]["PlaceOrderStat"] = $order["cPlcOrder"];
			
			echo json_encode($response);
		}
		else {
		// required post params is missing
		$response["error"] = TRUE;
		$response["error_msg"] = "Please try again!";
		echo json_encode($response);
}
		
	}
 else {
    // required post params is missing
    $response["error"] = TRUE;
    $response["error_msg"] = "Required parameters is missing!";
    echo json_encode($response);
}
?>

