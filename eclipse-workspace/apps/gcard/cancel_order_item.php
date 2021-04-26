<?php
	require_once 'include/DB_Connect.php';
	$db = DB();

	if (isset($_POST['uid'])) {
		$stmt= $db->query("SET Names utf8");
		
		$uid = $_POST['uid'];
		$uid = htmlspecialchars($uid);
		
		$json_response = array();

		$stmt = $db->prepare("Update G_Card_Order_Redeem set cTranStat = '3' where sTransNox = ?");
		$stmt->bindParam(1, $uid);
		
		if($stmt->execute()){
			$response["error"] = FALSE;
			$response["error_msg"] = "done";
			echo json_encode($response);			 
		}			
		
		else{
			$response["error"] = TRUE;
			$response["error_msg"] = "Error. Please try again later.";
			echo json_encode($response);
		}
	}
	else{
		
		// required post params is missing
		$response["error"] = TRUE;
		$response["error_msg"] = "Required parameters missing!";
		echo json_encode($response);
	}
	
?>