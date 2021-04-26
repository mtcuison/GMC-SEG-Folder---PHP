<?php
	require_once 'include/DB_Connect.php';
	$db = DB();
	
	require_once 'include/GetNextCode.php';
	$next = new getNextCode();
	$uuid =  $next->GetTransNox("G_Card_Order_Redeem", "sTransNox", TRUE , $db, "M001");	
	
	$json_response = array();
	$json = file_get_contents('php://input');
	
	//decoding of json
	$obj = json_decode($json);
	$gcard_number = $obj->{'gcard_number'};
	$PartsId = $obj->{'PartsId'};
	$PromoId = $obj->{'PromoId'};
	$ItemQuantity = $obj->{'ItemQuantity'};
	$ItemPoints = $obj->{'ItemPoints'};
	$BranchCode = $obj->{'BranchCode'};
	$DateOrdered = $obj->{'DateOrdered'};
	$DatePickup = $obj->{'DatePickup'};
	$DatePlaceOrder = $obj->{'DatePlaceOrder'};
	$TransStat = $obj->{'TransStat'};
	//$PlaceOrderStat = $obj->{'PlaceOrderStat'};
	
	$sql = "SELECT IFNULL(a.sGroupIDx, '') sGroupIDx, a.* FROM G_Card_Master a 
				WHERE IFNULL(a.sGroupIDx, '') = '' 
				AND cIndvlPts = '1' 	
				AND cMainGrpx = '0'
				AND sCardNmbr = ?";
				
	$stmt = $db->prepare($sql);
	$stmt->bindParam(1, $gcard_number);
	$stmt->execute();
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	
	if($row > 0){
		$stmt = $db->prepare("Select * from G_Card_Master where sCardNmbr = ? ");
		$stmt->bindParam(1, $gcard_number);
		$stmt->execute();			
		
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			$cardNox = $row["sGCardNox"];	
		}
	
		$PlaceOrderStat = NULL;
		//$uuid = uniqid('', true);
		$referNox = strtoupper(uniqid('G-'. '')); 		
		
		$stmt = $db->prepare('INSERT INTO G_Card_Order_Redeem (sTransNox, sGCardNox, sReferNox, sPartsIDx, sPromoIDx, nItemQtyx, nPointsxx,sBranchCd,dOrderedx, dPlacOrdr, dPickupxx, cTranStat, cPlcOrder) 
							VALUES (:uuid, :cardNox, :referNox, :PartsId, :PromoId,:ItemQuantity, :ItemPoints, :BranchCode, :DateOrdered, :DatePickup, :DatePlaceOrder, :TransStat, :PlaceOrderStat)');	
				
		if (!empty($PartsId)) {
			$result = $stmt->execute(array(
				'uuid' => $uuid,
				'cardNox' => $cardNox,
				'referNox' => $referNox,
				'PartsId' => $PartsId,
				'PromoId' => $PromoId,
				'ItemQuantity' => $ItemQuantity,
				'ItemPoints' => $ItemPoints,
				'BranchCode' => $BranchCode,
				'DateOrdered' => $DateOrdered,
				'DatePickup' => $DatePickup,
				'DatePlaceOrder' => $DatePlaceOrder,
				'TransStat' => $TransStat,
				'PlaceOrderStat' => $PlaceOrderStat
			));			
			
			if($result){
				$stmt = $db->prepare("SELECT * FROM G_Card_Order_Redeem  a
										LEFT JOIN Branch b
										ON a.sBranchCd = b.sBranchCd 		
										LEFT JOIN Spareparts c
										ON a.sPartsIDx = c.sPartsIDx 
										where a.sTransNox=?");
				$stmt->bindParam(1, $uuid);
				$stmt->execute();
				$order = $stmt->fetch(PDO::FETCH_ASSOC);
				
				$response["error"] = FALSE;
				$response["order"]["uid"] = $order["sTransNox"];			
				$response["order"]["PartsId"] = $order["sPartsIDx"];
				$response["order"]["GcardNox"] = $order["sGCardNox"];
				$response["order"]["ReferNox"] = $order["sReferNox"];
				$response["order"]["ItemQuantity"] = $order["nItemQtyx"];			
				$response["order"]["ItemDesc"] = $order["sDescript"];			
				$response["order"]["ItemPoints"] = $order["nPointsxx"];			
				$response["order"]["Branch"] = $order["sBranchNm"];
				$response["order"]["BranchCode"] = $order["sBranchCd"];
				$response["order"]["BranchAddress"] = $order["sAddressx"];
				$response["order"]["DateOrdered"] = $order["dOrderedx"];
				$response["order"]["DatePickup"] = $order["dPickupxx"];
				$response["order"]["DatePlaceOrder"] = $order["dPlacOrdr"];
				$response["order"]["TransStat"] = $order["cTranStat"];
				$response["order"]["PlaceOrderStat"] = $order["cPlcOrder"];
				
				echo json_encode($response, JSON_PARTIAL_OUTPUT_ON_ERROR);		
				
			}else{
				$response["error"] = TRUE;
				$response["error_msg"] = "Error";
				echo json_encode($response);
			}					
		}else{
			$response["error"] = TRUE;
			$response["error_msg"] = "Json empty";
			echo json_encode($response);
		}
		
	}else{
		$response["error"] = TRUE;
		$response["error_msg"] = "Redemption for this G-card is not yet supported. Please contact the nearest branch for assistance. Thank you";
		echo json_encode($response);
	}
	
?>