<?php

require_once 'include/DB_Connect.php';
$db = DB();

require_once 'include/GetNextCode.php';
$next = new getNextCode();
$trans_nox =  $next->GetTransNox("Send_Mail_Master", "sTransNox", TRUE , $db, "M001");	

	// json response array
	$response = array("error" => FALSE);
	 
	if (isset($_POST['mailto']) && isset($_POST['mailbody'])){
		// receiving the post params
		$mailto = $_POST['mailto'];
		$mailto = htmlspecialchars($mailto);
		
		$mailbody = $_POST['mailbody'];
		$mailbody = htmlspecialchars($mailbody);	
		
		$stmt= $db->query("SET Names utf8");
		
		$dTransact = date("Y-m-d h:i:s");		
		$mail_from = "noreply@guanzongroup.com.ph";
		$mail_cc = "";
		$mail_bcc = "";
		$subject = "Guanzon G-Card App";
		$attach = "";
		$source_code = "";
		$source_no = "";
		$status = "1";
		$posted = "";
				
		$stmt = $db->prepare("INSERT INTO Send_Mail_Master(sTransNox, dTransact, sMailFrom, sMailToxx, sMailCCxx, sMailBCCx, sSubjectx, sMailBody, sAttached, sSourceCD, sSourceNo, cStatusxx, dPostedxx) 
		VALUES(? , NOW() , ? , ? , ?, ?, ?, ?, ?, ?, ?, ?, ?)");	

			$stmt->bindParam(1, $trans_nox);				
			$stmt->bindParam(2, $mail_from);				
			$stmt->bindParam(3, $mailto);				
			$stmt->bindParam(4, $mail_cc);				
			$stmt->bindParam(5, $mail_bcc);				
			$stmt->bindParam(6, $subject);				
			$stmt->bindParam(7, $mailbody);				
			$stmt->bindParam(8, $attach);				
			$stmt->bindParam(9, $source_code);				
			$stmt->bindParam(10, $source_no);				
			$stmt->bindParam(11, $status);
			$stmt->bindParam(12, $posted);
			$result = $stmt->execute();

			if($result){
				$response["error"] = FALSE;
				echo json_encode($response);
			}
		
		
	}else{
		//G_card not legit. cancel registration
			$response["error"] = TRUE;
			$response["error_msg"] = "Error. Please contact our customer service for assistanace.";
			echo json_encode($response);
	}	
		
?>

