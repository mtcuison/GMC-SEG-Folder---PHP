<?php
	require_once 'include/DB_Connect.php';
	$db = DB();
		
	$gcard_number = $_POST['gcard_number'];
	$gcard_number = htmlspecialchars($gcard_number);
	
	$response = array("error" => FALSE);
		
	$stmt = $db->prepare("Select * from G_Card_Master where sCardNmbr = ? ");
	$stmt->bindParam(1, $gcard_number);
	$stmt->execute();
		
	while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
		$cardNox = $row["sGCardNox"];	
	}	
	//echo $cardNox;
	
	$json_response = array();
	
	$stmt = $db->prepare("SELECT * FROM G_Card_Redemption INNER JOIN G_Card_Promo_Master 
		ON G_Card_Redemption.sPromoIDx = G_Card_Promo_Master.sTransNox where sGCardNox=?");
	$stmt->bindParam(1, $cardNox);
	$stmt->execute();
	
	while($redemption = $stmt->fetch(PDO::FETCH_ASSOC)){
		$row_array["error"] = FALSE;		
		$row_array["Id"] = $redemption["sTransNox"];
		$row_array["Date"] = $redemption["dTransact"];
		$row_array["Description"] = $redemption["sPromDesc"];
		$row_array["Points"] = $redemption["nPointsxx"];
		
		array_push($json_response,$row_array);
		
	}

echo json_encode($json_response); 	
	
?>