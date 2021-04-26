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
	
	if (isset($_POST['old_pass']) && isset($_POST['new_pass']) && isset($_POST['retype_pass'])) 
	{		
		//security questions	
		$stmt= $db->query("SET Names utf8");		
		$gcard_number = $_POST['gcard_number'];
		$gcard_number = htmlspecialchars($gcard_number);
		
		$old_pass = $_POST['old_pass'];
		$old_pass = htmlspecialchars($old_pass);
		
		$new_pass = $_POST['new_pass'];
		$new_pass = htmlspecialchars($new_pass);
		
		$retype_pass = $_POST['retype_pass'];	
		$retype_pass = htmlspecialchars($retype_pass);
		
		$stmt = $db->prepare("Select * from G_Card_App_Master where sCardNmbr= ? ");		
		$stmt->bindParam(1, $gcard_number);	
		$stmt->execute();
		
		while($row = $stmt->fetch(PDO::FETCH_ASSOC))
		{					
			$password = $row["sPassword"];
			$decrypted_password = encrypt_decrypt('decrypt', $password);			
			
		}		
		if ($decrypted_password == $old_pass){
			if($new_pass == $retype_pass){
				//echo "Chaqnge password";
				
				$encrypted_password = encrypt_decrypt('encrypt', $new_pass);
							
				$stmt = $db->prepare("Update G_Card_App_Master Set sPassword = ? where sCardNmbr = ?");
				$stmt->bindParam(1, $encrypted_password);
				$stmt->bindParam(2, $gcard_number);
				
				if($stmt->execute()){
					$response["error"] = FALSE;
					echo json_encode($response);					 
				}
			}else{
				$response["error"] = TRUE;
				$response["error_msg"] = "Password didnt match.";
				echo json_encode($response);
			}
		}
		else{
			$response["error"] = TRUE;
			$response["error_msg"] = "Incorrect Password";
			echo json_encode($response);
		}
	}else{
		
		$response["error"] = TRUE;
		$response["error_msg"] = "Required parameters is missing!";
		echo json_encode($response);
	}

?>