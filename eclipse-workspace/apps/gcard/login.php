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
	 
	if (isset($_POST['gcard_number']) && isset($_POST['password'])) {
		// receiving the post params
		$gcard_number = $_POST['gcard_number'];
		$gcard_number = htmlspecialchars($gcard_number);
		
		$password = $_POST['password'];
		$password = htmlspecialchars($password);		
		
		$stmt= $db->query("SET Names utf8");
		
		$stmt = $db->prepare("SELECT * FROM G_Card_Master where sCardNmbr= ? ");
		$stmt->bindParam(1, $gcard_number);
		$stmt->execute();
		
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			
			$AvlPoint = $row["nAvlPoint"];		
		}
		
		
		if($num_rows = $stmt->rowCount() > 0){
			
			$stmt = $db->prepare("SELECT * FROM G_Card_App_Master WHERE sCardNmbr= ? ");
			$stmt->bindParam(1, $gcard_number);
			$stmt->execute();
			
			if($num_rows = $stmt->rowCount() > 0){
				
				while($row = $stmt->fetch(PDO::FETCH_ASSOC))
				{					
					$encrypted_password = $row["sPassword"];	
					
					$decrypted_password = encrypt_decrypt('decrypt', $encrypted_password);
				
					if($password == $decrypted_password){
						$n_AvlPoint = $row["nAvlPoint"];
					
						//echo $AvlPoint;
						//echo "\n" . $n_AvlPoint;
						
						if($n_AvlPoint != $AvlPoint){
							echo "update point";
							
							$stmt1 = $db->prepare("Update G_Card_App_Master Set nAvlPoint = ? where sCardNmbr = ?");
							$stmt1->bindParam(1, $AvlPoint);
							$stmt1->bindParam(2, $gcard_number);
							
							if($stmt1->execute()){
								$response["error"] = FALSE;					
								$response["uid"] = $row["sTransNox"];
								$response["user"]["name"] = $row["sClientNm"];
								$response["user"]["gcard_number"] = $row["sCardNmbr"];
								$response["user"]["avl_points"] = $AvlPoint;
								$response["user"]["created_at"] = $row["dOrderedx"];				
								echo json_encode($response);
							}				
							
						}
						else{
							$response["error"] = FALSE;					
							$response["uid"] = $row["sTransNox"];
							$response["user"]["name"] = $row["sClientNm"];
							$response["user"]["gcard_number"] = $row["sCardNmbr"];
							$response["user"]["avl_points"] = $row["nAvlPoint"];
							$response["user"]["created_at"] = $row["dModified"];				
							echo json_encode($response);
						}
												
					}
					else{
						$response["error"] = TRUE;
						$response["error_msg"] = "Login Credentials doesnt match. Please try again.";
						echo json_encode($response);
					}
				}
			}
			else{
				$response["error"] = TRUE;
				$response["error_msg"] = "This G-card is not yet registered.";
				echo json_encode($response);
			}
			
			
		}
		else
		{
			//G_card not legit. cancel registration
			$response["error"] = TRUE;
			$response["error_msg"] = "The entered Gcard number doesnt exist in our records.";
			echo json_encode($response);
		}
	}
 else {
    // required post params is missing
    $response["error"] = TRUE;
    $response["error_msg"] = "Required parameters gcard_number or password is missing!";
    echo json_encode($response);
}
?>

