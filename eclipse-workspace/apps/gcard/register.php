<?php	
	require_once 'include/DB_Connect.php';
	$db = DB();
 
	// json response array
	$response = array("error" => FALSE);
	
	//function for encryption/decryption
	function encrypt_decrypt($action, $string) {
		$output = false;
		$encrypt_method = "AES-256-CBC";
		$secret_key = 'This is my secret key';
		$secret_iv = 'This is my secret iv';
		// hash
		$key = hash('sha256', $secret_key);
		
		// iv - encrypt method AES-256-CBC expects 16 bytes - else you will get a warning
		$iv = substr(hash('sha256', $secret_iv), 0, 16);
		
		if ( $action == 'encrypt' ) {
			$output = openssl_encrypt($string, $encrypt_method, $key, 0, $iv);
			$output = base64_encode($output);
		} else if( $action == 'decrypt' ) {
			$output = openssl_decrypt(base64_decode($string), $encrypt_method, $key, 0, $iv);
		}
		return $output;
	}
	
	if (isset($_POST['birthday']) && isset($_POST['gcard_number']) && isset($_POST['mobile'])&& isset($_POST['email']) && isset($_POST['password'])) 
	{
		$stmt= $db->query("SET Names utf8");
		
		$bday = $_POST['birthday'];
		$bday = htmlspecialchars($bday);
		
		$gcard_number = $_POST['gcard_number'];
		$gcard_number = htmlspecialchars($gcard_number);
		
		$mobile_number = $_POST['mobile'];
		$mobile_number = htmlspecialchars($mobile_number);
		
		$email_address = $_POST['email'];
		$email_address = htmlspecialchars($email_address);
		
		$password = $_POST['password'];	
		$password = htmlspecialchars($password);
		

		//fetch avalable points
		$stmt = $db->prepare("SELECT * FROM G_Card_Master WHERE sCardNmbr= ? ");
		$stmt->bindParam(1, $gcard_number);
		$stmt->execute();	
		
		while($row = $stmt->fetch(PDO::FETCH_ASSOC))
		{
			$clientID = $row["sClientID"];	
			$avl_points = $row["nAvlPoint"];
		}
		
		//fetch name
		$stmt = $db->prepare("Select * from Client_Master where sClientID = ? ");
		$stmt->bindParam(1, $clientID);
		$stmt->execute();				
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)){			
			$name = $row["sFrstName"].' '.$row["sMiddName"].' '.$row["sLastName"];
		}
		
		//insert record
		$uuid = uniqid('', true);					
		$encrypted_password = encrypt_decrypt('encrypt', $password);
					
		$stmt = $db->prepare("INSERT INTO G_Card_App_Master(sTransNox, sClientNm, sCardNmbr,sMobileNo,sEmailAdd,nAvlPoint, sPassword, dModified) VALUES(?, ?, ?, ?, ?, ?, ?, NOW())");
		$stmt->bindParam(1, $uuid);
		$stmt->bindParam(2, $name);
		$stmt->bindParam(3, $gcard_number);
		$stmt->bindParam(4, $mobile_number);
		$stmt->bindParam(5, $email_address);					
		$stmt->bindParam(6, $avl_points);					
		$stmt->bindParam(7, $encrypted_password);						
		$result = $stmt->execute();	

		//successfull insert
		//display json array
					
		if($result){
			$stmt1 = $db->prepare("SELECT * FROM G_Card_App_Master WHERE sCardNmbr = ?");
			$stmt1->bindParam(1, $gcard_number);
			$stmt1->execute();
			$user = $stmt1->fetch(PDO::FETCH_ASSOC);
				
			$response["error"] = FALSE;
			$response["uid"] = $user["sTransNox"];
			$response["user"]["name"] = $user["sClientNm"];
			$response["user"]["gcard_number"] = $user["sCardNmbr"];
			$response["user"]["avl_points"] = $user["nAvlPoint"];
			$response["user"]["created_at"] = $user["dModified"];				
			echo json_encode($response);
			
			$stmt = $db->prepare("Update G_Card_App_Number Set cMemberxx = ? where sMobileNo = ?");
			$member = '1';
			$stmt->bindParam(1, $member);
			$stmt->bindParam(2, $mobile_number);
						
		}
		else{
			$response["error"] = TRUE;
			$response["error_msg"] = "Failed in registration";
			echo json_encode($response);
		}
	}
	else{		
		$response["error"] = TRUE;
		$response["error_msg"] = "Required parameters is missing!";
		echo json_encode($response);
	}

?>