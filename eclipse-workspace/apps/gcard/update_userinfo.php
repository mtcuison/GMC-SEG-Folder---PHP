<?php
	require_once 'include/DB_Connect.php';
	$db = DB();

	if (isset($_POST['gcard_number'])) {
		
		$gcard_number = $_POST['gcard_number'];
		$gcard_number = htmlspecialchars($gcard_number);
		$json_response = array();

		$stmt = $db->prepare("Select nAvlPoint from G_Card_Master where sCardNmbr= ? ");
		$stmt->bindParam(1, $gcard_number);
		$stmt->execute();
		
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			
			$AvlPoint = $row["nAvlPoint"];		
		}
		
		$stmt = $db->prepare("Update G_Card_App_Master Set nAvlPoint = ? where sCardNmbr = ?");
		$stmt->bindParam(1, $AvlPoint);
		$stmt->bindParam(2, $gcard_number);
		
		if($stmt->execute()){
			$stmt = $db->prepare("Select nAvlPoint from G_Card_App_Master where sCardNmbr = ? ");
			$stmt->bindParam(1, $gcard_number);
			$stmt->execute();
			$user = $stmt->fetch(PDO::FETCH_ASSOC);
			
			$response["error"] = FALSE;
			$response["user"]["nAvlPoint"] = $user["nAvlPoint"];        
			echo json_encode($response);
			 
			}			
		}
	
	else{
		
		// required post params is missing
		$response["error"] = TRUE;
		$response["error_msg"] = "Required parameters gcard_number or password is missing!";
		echo json_encode($response);
	}
	
?>