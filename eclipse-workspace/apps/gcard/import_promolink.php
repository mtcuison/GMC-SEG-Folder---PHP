<?php
	require_once 'include/DB_Connect.php';
	$db = DB();

		// json response array
	$response = array("error" => FALSE);
	$json_response = array();
	
	$stmt = $db->prepare("SELECT" . 
	                           "  sTransNox" .
	                           ", dTransact" . 
	                           ", sImageURL" . 
	                           ", IF(IFNULL(sPromoURL, 'https://www.guanzongroup.com.ph/') = '', 'https://www.guanzongroup.com.ph/', IFNULL(sPromoURL, 'https://www.guanzongroup.com.ph/')) sPromoURL" . 
	                           ", sCaptionx" . 
	                           ", dDateFrom" . 
	                           ", dDateThru" .
	                       " FROM G_Card_Promo_Link" .
	                       " WHERE dDateThru > Now()");
	$stmt->execute();
	
	while($promo = $stmt->fetch(PDO::FETCH_ASSOC)){
		$row_array["error"] = FALSE; 
		$row_array["uid"] = $promo["sTransNox"];
		$row_array["Link"] = $promo["sImageURL"];
		$row_array["URL"] = $promo["sPromoURL"];
		$row_array["Caption"] = $promo["sCaptionx"];
		$row_array["Date_from"] = $promo["dDateFrom"];
		$row_array["Date_thru"] = $promo["dDateThru"];
		
		array_push($json_response,$row_array);		
	}

echo json_encode($json_response); 	
	
?>