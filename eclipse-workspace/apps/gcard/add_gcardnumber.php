<?php
	require_once 'include/DB_Connect.php';
	$db = DB();
	
	$stmt = $db->prepare("SELECT * FROM Gcard_App_Master ORDER BY dTimeStmp DESC LIMIT 1");
	$stmt->execute();
	
	if($num_rows = $stmt->rowCount() > 0){
		
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			$i = $row["nEntryNox"];
		}	
		$entryNox = $i+1;
	}else{
		$entryNox = 1;
	}	

	$response = array("error" => FALSE);

	if (isset($_POST['gcard_number']) && isset($_POST['bday'])) {
		
		//POST VALUES
		$gcard_number = $_POST['gcard_number'];
		$gcard_number = htmlspecialchars($gcard_number);
		
		$bday = $_POST['bday'];
		$bday = htmlspecialchars($bday);
		
		$user_id = $_POST['user_id'];
		$user_id = htmlspecialchars($user_id);
		
		$doVerify = "1";
		$verify = "0";
		
		//check if entered gcard is in our records.
		$stmt = $db->prepare("Select * from G_Card_Master where sCardNmbr= ? ");
		$stmt->bindParam(1, $gcard_number);
		$stmt->execute();
		
		while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
			$clientID = $row["sClientID"];	
			$status = $row["cCardStat"];
		}		
		
		if($num_rows = $stmt->rowCount() > 0){	
			//check if entered gcard is in use.
			$stmt = $db->prepare("SELECT * FROM Gcard_App_Master WHERE sCardNmbr = ? AND cPlsVerfy ='0'");
			$stmt->bindParam(1, $gcard_number);
			$stmt->execute();			
			$row = $stmt->fetch(PDO::FETCH_ASSOC);	
			
			if($row > 0){
				//cPlsVerify
				//0: No need for verification ($doVerify)
				//1: Needs verification ($verify)
				
				//gcard is already in use. Perform insert but cPlsVerify is activated. Value is "1"
				$stmt = $db->prepare("INSERT INTO Gcard_App_Master(
										sUserIDxx, 
										nEntryNox, 
										sCardNmbr,
										dRegister,
										cPlsVerfy) 
									VALUES(?, ?, ?, NOW(), ?)");
				$stmt->bindParam(1, $user_id);
				$stmt->bindParam(2, $entryNox);
				$stmt->bindParam(3, $gcard_number);
				$stmt->bindParam(4, $doVerify);
				$stmt->execute();	
				
			}else{			
				
				//check if gcard is activated
				//0 - open 
				//1 - printed
				//2 - encoded
				//3 - issued
				//4 - activated
				if($status == "4"){
					//gcard activated proceed to next step
					//select bday sa client_master
					$stmt = $db->prepare("Select * from Client_Master where sClientID = ? ");
					$stmt->bindParam(1, $clientID);
					$stmt->execute();				
					while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
						$birthday = $row["dBirthDte"];						
					}		
					
					//STEP3: Check kung ung inenter na bday ni user is same sa records natin
					if($bday == $birthday){						
						
						//SUCCESSFULL ADDING OF GCARD
						$stmt = $db->prepare("INSERT INTO Gcard_App_Master(
												sUserIDxx, 
												nEntryNox, 
												sCardNmbr,
												dRegister,
												cPlsVerfy) 
											VALUES(?, ?, ?, NOW(), ?)");
						$stmt->bindParam(1, $user_id);
						$stmt->bindParam(2, $entryNox);
						$stmt->bindParam(3, $gcard_number);
						$stmt->bindParam(4, $verify);
						$result = $stmt->execute();	
						
						if($result){
							$stmt = $db->prepare("SELECT * FROM Gcard_App_Master WHERE sCardNmbr = ?");
							$stmt->bindParam(1, $gcard_number);
							$stmt->execute();
							$user = $stmt->fetch(PDO::FETCH_ASSOC);
							
							$response["error"] = FALSE;				
							$response["user"]["UserID"] = $user["sUserIDxx"];
							$response["user"]["EntryNumber"] = $user["nEntryNox"];
							$response["user"]["GcardNumber"] = $user["sCardNmbr"];
							//$response["user"]["GcardNox"] = $user["sGCardNox"];		
							//$response["user"]["AvailablePoint"] = $user["nAvlPoint"];		
							$response["user"]["DateRegistration"] = $user["dRegister"];		
							$response["user"]["Verify"] = $user["cPlsVerfy"];		
							$response["user"]["TimeStamp"] = $user["dTimeStmp"];	
							echo json_encode($response);		
							
							
						}else{
							$response["error"] = TRUE;
							$response["error_msg"] = "Adding Failed.";
							echo json_encode($response);
						}

						

										
						
						
					}else{
						//cPlsVerify
						//0: No need for verification ($doVerify)
						//1: Needs verification ($verify)
						
						//details submitted not matched
						$stmt = $db->prepare("INSERT INTO Gcard_App_Master(
												sUserIDxx, 
												nEntryNox, 
												sCardNmbr,
												dRegister,
												cPlsVerfy) 
											VALUES(?, ?, ?, NOW(), ?)");
						$stmt->bindParam(1, $user_id);
						$stmt->bindParam(2, $entryNox);
						$stmt->bindParam(3, $gcard_number);
						$stmt->bindParam(4, $doVerify);
						$stmt->execute();	
					}
				}else{
					//hindi pa activated ung gcard ni client
						$response["error"] = TRUE;
						$response["error_msg"] = "Your gcard is not yet activated. Please visit the branch to activate your gcard and try again.";
						echo json_encode($response);
				}
			}
			
		}else{
			//G_card not legit. cancel registration
			$response["error"] = TRUE;
			$response["error_msg"] = "Invalid G-card Detected.";
			echo json_encode($response);
		}		
	}else{		
		$response["error"] = TRUE;
		$response["error_msg"] = "Required parameters is missing!";
		echo json_encode($response);
	}
?>