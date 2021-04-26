<?php
	require_once 'include/DB_Connect.php';
	$db = DB();
	$stmt= $db->query("SET Names utf8");	
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
	
	$stmt = $db->prepare("SELECT * FROM G_Card_Detail INNER JOIN G_Card_Points_Basis ON G_Card_Detail.sSourceCd = G_Card_Points_Basis.sSourceCd where sGCardNox=?");
	$stmt->bindParam(1, $cardNox);
	$stmt->execute();
	
	while($transaction = $stmt->fetch(PDO::FETCH_ASSOC)){
		$row_array["error"] = FALSE;
		$row_array["Id"] = $transaction["sTransNox"];
		$row_array["Date"] = $transaction["dTransact"];
		$row_array["Description"] = $transaction["sDescript"];	
		$row_array["Amount"] = $transaction["nTranAmtx"];	
		$row_array["Points"] = $transaction["nPointsxx"];
		
		array_push($json_response,$row_array);		
	}

echo json_encode($json_response); 	
	
?>