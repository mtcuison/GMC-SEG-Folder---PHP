<?php
	require_once 'include/DB_Connect.php';
	$db = DB();

	if (isset($_POST['uid'])) {
		
		$stmt= $db->query("SET Names utf8");
		
		$uid = $_POST['uid'];
		$uid = htmlspecialchars($uid);
		
		$item_quantity = $_POST['item_quantity'];
		$item_quantity  = htmlspecialchars($item_quantity);
		
		$item_points = $_POST['item_points'];
		$item_points = htmlspecialchars($item_points);
		
		$branch = $_POST['branch'];
		$branch = htmlspecialchars($branch);
		
		$branch_address = $_POST['branch_address'];
		$branch_address = htmlspecialchars($branch_address);	

		$branch_code = $_POST['branch_code'];
		$branch_code = htmlspecialchars($branch_code);
		
		$json_response = array();

		$stmt = $db->prepare("Update G_Card_Order_Redeem Set nItemQtyx = ?, nPointsxx = ?, sBranchCd = ?  WHERE sTransNox = ?");
		$stmt->bindParam(1, $item_quantity);
		$stmt->bindParam(2, $item_points);
		$stmt->bindParam(3, $branch_code);
		$stmt->bindParam(4, $uid);
		
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