<?php	
	require_once 'include/DB_Connect.php';
	$db = DB();
 
	// json response array
	$response = array("error" => FALSE);
	
	if (isset($_POST['answer_1']) && isset($_POST['answer_2']) && isset($_POST['answer_3'])) 
	{		
		//security questions		
		$gcard_number = $_POST['gcard_number'];
		$gcard_number = htmlspecialchars($gcard_number);		
		
		$answer_1 = $_POST['answer_1'];
		$answer_1 = htmlspecialchars($answer_1);
		
		$answer_2 = $_POST['answer_2'];
		$answer_2 = htmlspecialchars($answer_2);
		
		$answer_3 = $_POST['answer_3'];	
		$answer_3 = htmlspecialchars($answer_3);
		
		$stmt= $db->query("SET Names utf8");
		$stmt = $db->prepare("SELECT * FROM G_Card_App_Master WHERE sCardNmbr = ?");
		$stmt->bindParam(1, $gcard_number);
		$stmt->execute();

		while($row = $stmt->fetch(PDO::FETCH_ASSOC))
		{
			$mobile_number = $row['sMobileNo'];
			$name = $row['sClientNm'];
			$ans_1 = $row["sAnswer01"];
			$ans_2 = $row["sAnswer02"];
			$ans_3 = $row["sAnswer03"];
			$encrypted_password = $row["sPassword"];			
		}

        if($answer_1 == $ans_1 && $answer_2 == $ans_2 && $answer_3 == $ans_3){
			$cryptKey  = 'qJB0rGtIn5UB1xG03efyCp';
			$qDecoded  = rtrim( mcrypt_decrypt( MCRYPT_RIJNDAEL_256, md5( $cryptKey ), base64_decode( $encrypted_password ), MCRYPT_MODE_CBC, md5( md5( $cryptKey ) ) ), "\0");
			
			$message = "Hi Mr/Mrs. " . "$name". ". Seems like you had forgotten your password for you Gcard App Account. Here is your password for you to revalidate your account. 
			\nPassword: "."$qDecoded". " 
			\nPlease make sure that you remember your password or change it thru our settings of the app for more easier password. Thank you!";
			$stmt = $db->prepare("INSERT INTO G_Card_Forgot_Pass(dTransact, sMobileNo, sMessagex, dDueUntil, cTranStat) 
					VALUES(NOW(),?,?,NOW(),'0')");
					
			$stmt->bindParam(1, $mobile_number);
			$stmt->bindParam(2, $message);
			$result = $stmt->execute();				
					
			$response["error"] = FALSE;	
			$response["password"] = $qDecoded;	
			echo json_encode($response);
		}else{
			$response["error"] = TRUE;
			$response["error_msg"] = "Sorry but you entered invalid answers. Please try again.";
			echo json_encode($response);
		}
		
		
	}
	else{
		
		$response["error"] = TRUE;
		$response["error_msg"] = "Required parameters is missing!";
		echo json_encode($response);
	}

?>