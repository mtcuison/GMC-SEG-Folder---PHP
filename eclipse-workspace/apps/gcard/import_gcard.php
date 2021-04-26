<?php
	require_once 'include/DB_Connect.php';
	$db = DB();
	$stmt= $db->query("SET Names utf8");
	
	$user_id = $_POST['user_id'];
	$user_id = htmlspecialchars($user_id);
	
	$response = array("error" => FALSE);

	$json_response = array();
	
	$stmt = $db->prepare("SELECT * FROM Gcard_App_Master a LEFT JOIN G_Card_Master b ON a.sCardNmbr = b.sCardNmbr 
	WHERE sUserIDxx = ?");
	$stmt->bindParam(1, $user_id);
	$stmt->execute();
	
	while($transaction = $stmt->fetch(PDO::FETCH_ASSOC)){
		$row_array["error"] = FALSE;
		$row_array["UserID"] = $transaction["sUserIDxx"];
		$row_array["EntryNumber"] = $transaction["nEntryNox"];
		$row_array["GcardNumber"] = $transaction["sCardNmbr"];	
		$row_array["GcardNox"] = $transaction["sGCardNox"];	
		$row_array["AvailablePoint"] = $transaction["nAvlPoint"];	
		$row_array["DateRegistration"] = $transaction["dRegister"];	
		$row_array["Verify"] = $transaction["cPlsVerfy"];
		$row_array["TimeStamp"] = $transaction["dTimeStmp"];
		
		
		array_push($json_response,$row_array);
			
	}

echo json_encode($json_response); 	
	
?>