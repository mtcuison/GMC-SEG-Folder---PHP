<?php

require_once 'include/DB_Connect.php';
$db = DB();
	
	// json response array
	$response = array("error" => FALSE);
	
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
	 
	if (isset($_POST['gcard_number'])) {
		// receiving the post params
		$gcard_number = $_POST['gcard_number'];
		$gcard_number = htmlspecialchars($gcard_number);	
		
		$stmt= $db->query("SET Names utf8");
		
		$stmt = $db->prepare("Select * from G_Card_Master where sCardNmbr= ? ");
		$stmt->bindParam(1, $gcard_number);
		$stmt->execute();
		
		if($num_rows = $stmt->rowCount() > 0){
			
			$stmt = $db->prepare("Select * from G_Card_App_Master where sCardNmbr= ? ");
			$stmt->bindParam(1, $gcard_number);
			$stmt->execute();	
			
			
			if($num_rows = $stmt->rowCount() > 0){
				//include 'sample_sec_questions.php';
				while($row = $stmt->fetch(PDO::FETCH_ASSOC))
				{		
					$email = $row['sEmailAdd'];
					$client_name = $row['sClientNm'];
					$encrypted_password = $row["sPassword"];				
					
				}
				
				$decrypted_password = encrypt_decrypt('decrypt', $encrypted_password);
				
				$response["error"] = FALSE;	
				$response["email"] = $email;
				$response["name"] = $client_name;	
				$response["password"] = $decrypted_password;					
				echo json_encode($response);
				
							
			}
			else{
				$response["error"] = TRUE;
				$response["error_msg"] = "This G-card is not yet registered";
				echo json_encode($response);
			}
		}
		else
		{
			//G_card not legit. cancel registration
			$response["error"] = TRUE;
			$response["error_msg"] = "This G-card does not exist in our records";
			echo json_encode($response);
		}
		
	}	
		
?>

