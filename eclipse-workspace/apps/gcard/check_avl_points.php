<?php

require_once 'include/DB_Connect.php';
$db = DB();
	
	// json response array
	$response = array("error" => FALSE);
	 
	if (isset($_POST['gcard_number'])) {
		// receiving the post params
		$gcard_number = $_POST['gcard_number'];
		$gcard_number = htmlspecialchars($gcard_number);

		$avl_points = $_POST['avl_points'];
		$avl_points = htmlspecialchars($avl_points);		
		
		$stmt= $db->query("SET Names utf8");
		
		$stmt = $db->prepare("Select * from G_Card_Master where sCardNmbr= ? ");
		$stmt->bindParam(1, $gcard_number);
		$stmt->execute();
		
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)){			
			$AvlPoint = $row["nAvlPoint"];		
		}
		
		if($AvlPoint != $avl_points){
			$response["error"] = TRUE;
			$response["error_msg"] = "Points not updated";
			echo json_encode($response);
		}
		
		else{
			$response["error"] = False;
			$response["error_msg"] = "Points updated";
			echo json_encode($response);
		}
		
		
	}	
	else{
		echo 'error detected';
		echo $_POST['gcard_number'];
	}
		
?>

