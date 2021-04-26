<?php
	require_once 'include/DB_Connect.php';
	$db = DB();
	
	$gcard_number = $_POST['gcard_number'];
	$gcard_number = htmlspecialchars($gcard_number);
	
	//$gcard_number = "0131400000700";
	$response = array("error" => FALSE);
		
	$stmt = $db->prepare("Select * from G_Card_Master where sCardNmbr = ? ");
	$stmt->bindParam(1, $gcard_number);
	$stmt->execute();
		
	while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
		$cardNox = $row["sGCardNox"];	
		
	}	
	//echo $cardNox;
	
	$json_response = array();		

			
	$sql = 	"SELECT *".
		    " FROM G_Card_Order_Redeem a".
					" LEFT JOIN Branch b ON a.sBranchCd = b.sBranchCd".
					" LEFT JOIN Spareparts c ON a.sPartsIDx = c.sPartsIDx".
		    " WHERE a.sGCardNox=? AND a.cTranStat='0' ";	
	
	$stmt = $db->prepare($sql);
	$stmt->bindParam(1, $cardNox);
	$stmt->execute();
	
	while($order = $stmt->fetch(PDO::FETCH_ASSOC)){
		$row_array["error"] = FALSE; 
		$row_array["uid"] = $order["sTransNox"];
		$row_array["PartsId"] = $order["sPartsIDx"];
		$row_array["ItemDesc"] = $order["sDescript"];
		$row_array["GcardNox"] = $order["sGCardNox"];
		$row_array["ReferNox"] = $order["sReferNox"];
		$row_array["ItemQuantity"] = $order["nItemQtyx"];	
		$row_array["ItemPoints"] = $order["nPointsxx"];	
		$row_array["BranchCode"] = $order["sBranchCd"];	
		$row_array["Branch"] = $order["sBranchNm"];	
		$row_array["BranchAddress"] = $order["sAddressx"];	
		$row_array["DateOrdered"] = $order["dOrderedx"];
		$row_array["DatePickup"] = $order["dPickupxx"];
		$row_array["DatePlaceOrder"] = $order["dPlacOrdr"];
		$row_array["OrderStat"] = $order["cTranStat"];
		$row_array["PlaceOrderStat"] = $order["cPlcOrder"];
		
		array_push($json_response,$row_array);		
	}

echo json_encode($json_response); 	
	
?>