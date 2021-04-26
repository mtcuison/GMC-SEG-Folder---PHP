<?php	
	require_once 'include/DB_Connect.php';
	$db = DB();
 
	// json response array
	$response = array("error" => FALSE);
	if (isset($_POST['gcard_number']) && isset($_POST['birthday']) &&isset($_POST['mobile'])&& isset($_POST['email'])&& isset($_POST['password'])) 
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
		
		$stmt= $db->query("SET Names utf8");
		
		$stmt = $db->prepare("SELECT * FROM G_Card_App_Master WHERE sCardNmbr = ?");
		$stmt->bindParam(1, $gcard_number);
		$stmt->execute();
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		
		//STEP1: Check if existing na yung account
		if($row > 0){
			//existing na yung details na eneter ni client. Cancel registration
			$response["error"] = TRUE;
			$response["error_msg"] = "The entered Gcard number is already in use.";
			echo json_encode($response);
		}	
		else
		{	
			//di pa naki-create-an ng account. Verification of entered details
			//STEP2: Check kung legit yung g_card na inenter ni user
			
			$stmt = $db->prepare("Select * from G_Card_Master where sCardNmbr= ? ");
			$stmt->bindParam(1, $gcard_number);
			$stmt->execute();			
			
			//legit yung g_card. Proceed to step 3
			if($num_rows = $stmt->rowCount() > 0){
				while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
					$clientID = $row["sClientID"];	
					$avl_points = $row["nAvlPoint"];
					$status = $row["cCardStat"];
				}
				
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
						$name = $row["sFrstName"].' '.$row["sMiddName"].' '.$row["sLastName"];
						
					}				
					//STEP3: Check kung ung inenter na bday ni user is same sa records natin
					if($bday == $birthday){
						
						//STEP 4: validate the email
						$stmt = $db->prepare("SELECT sEmailAdd FROM G_Card_App_Master WHERE sEmailAdd = ?");
						$stmt->bindParam(1, $email_address);
						$stmt->execute();
						$row = $stmt->fetch(PDO::FETCH_ASSOC);
						
						//EMAIL SHOUD BE UNIQUE
						if($row > 0){
							//existing na yung details na eneter ni client. Cancel registration
							$response["error"] = TRUE;
							$response["error_msg"] = "Email Address is already in used.";
							echo json_encode($response);
						}else{
							$response["error"] = FALSE;
							echo json_encode($response);
						}
						
					}else{
						//hind match ung bday sa server
						$response["error"] = TRUE;
						$response["error_msg"] = "Details you submitted didnt match in our records. Please try again.";
						echo json_encode($response);
					}
				}else{
					//hindi pa activated ung gcard ni client
						$response["error"] = TRUE;
						$response["error_msg"] = "Your gcard is not yet activated. Please visit the branch to activate your gcard and try again.";
						echo json_encode($response);
				}	
			}			
			else{
				//G_card not legit. cancel registration
				$response["error"] = TRUE;
				$response["error_msg"] = "This G-card does not exist in our records";
				echo json_encode($response);
			}
			
		}
	}else{
		
		$response["error"] = TRUE;
		$response["error_msg"] = "Required parameters is missing!";
		echo json_encode($response);
	}

?>