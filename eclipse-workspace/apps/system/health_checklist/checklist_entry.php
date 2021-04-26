<?php
/* Encode health checklist.
 *
 * /system/health_checklist/checklist_entry.php
 *
 * mac 2020.12.28
 *  started creating this object.
 */


require_once 'config.php';
require_once 'Nautilus.php';
require_once 'CommonUtil.php';

date_default_timezone_set('Asia/Manila');

$otp = CommonUtil::GenerateOTP(6);


$branchcd = "";
if(isset($_GET['brc'])){
    $branchcd = $_GET['brc'];
} else showMessage("Access of this page is not allowed.");

/**
 * Show dialog message. Exit window by default.
 */
function showMessage($fsValue, $lbExit = true){
    echo '<script type="text/JavaScript">';
    echo 'alert("'. $fsValue .'");';
    if ($lbExit == true) echo 'window.location.replace("https://www.guanzongroup.com.ph/")';
    echo '</script>';
}
?>

<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	
	<title>Guanzon - Health Checklist</title>
	<link rel="shortcut icon" href="https://www.guanzongroup.com.ph/wp-content/uploads/2018/05/cropped-logo_only.png">
   	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css">
    
    <script src="https://code.jquery.com/jquery-3.5.0.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.1/jquery.min.js"></script>
    
    <!-- jQuery UI -->
    <link rel="stylesheet" href="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/themes/smoothness/jquery-ui.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
    
    <style>
        body, html {
            height: 100%;
            margin: 0;
        }
        .menu-bg {
            background-image: url("../images/health checklist header.png");
            height: 65px;
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
        }
        .body-bg {
            background-image: url("");
            height: 100%;
            background-position: 100%;
            background-repeat: no-repeat;
            background-size: cover;
        }
        .body-bg {
            padding: 10px;
        }
        .logo {
            float: left;
            width: 203px;
            margin-left: 4%;
        }
        .fb {
            background-color:rgba(230,230,230,0.5);
            float: right;
            height: 65px;
            width: 300px;
            text-align: center;
            padding-top: 15px;
            position: absolute;
            top: 20%;
            left: 50%;
            -ms-transform: translateX(-50%) translateY(-50%);
            -webkit-transform: translate(-50%,-50%);
            transform: translate(-50%,-50%);
        }
        @media ( min-width: 576px ) {
            .logo {
                margin-left: 11%;
                width: 243px;
            }
            .menu-bg {
                height: 78px;
            }
            .fb {
                background-color:white;
                float: right;
                height: 55px;
                width: 300px;
                padding-top: 0px;
                margin-right: 15%;
                margin-top: 15px;
                top: 5%;
                left: 70%;
            }
        }
        
        label{
            color: black;
        }
        
        #confirmation label{
            color: red;
        }
        
        .authorize{
            text-align: center;
            font-size: 10px;
        }
        
        .branch{
            text-align: center;
            margin-bottom: 0;
        }
        
        .branch #sBranchNm{
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 0;
        }
        
        .branch #sAddresBr, #sTownBrch,  #sLanLneBr, #sEmailBrx{
            text-align: center;
            font-size: 13px;
            margin-bottom: 0;
        }
        
        .question{
            width: 70%;
            text-align: left;
        }
        
        input{
            width: 100%;
            padding-left:5px;
            padding-right:5px;
            border-radius: 5px;
        }       
               
        select{
            width: 30%;
            border-radius: 5px;
            float:right;
        }
        
        div .xcontainer {
            border: 1px solid white;
            border-radius: 5px;
            
            padding-top: 5px;
            padding-right: 10px;
            padding-left: 10px;;
        }
        
        div .xbutton{
            padding-top: 10px;
            padding-right: 10px;
            padding-bottom: 5px;
            padding-left: 10px;
        }
        
        .xbutton .xbtn{
            font-family: Arial, Helvetica, sans-serif;
            background: #4c4c4c;
            color: white;
            font-size: 15px;
            border-radius: 5px;
            border: 1px solid #4c4c4c;
            padding: 5px;
                        
            display: block;
            width: 100%;
            text-align: center;
            margin-bottom: 5px;
        }
    </style>
</head>
<body>	
	<script type="text/javascript">
		function EnableFields() {
			document.getElementById('confirmation').setAttribute("style","display: none");
		 	document.getElementById('btnSubmit').setAttribute("style","display: block");
		 	document.getElementById('waiver').setAttribute("style","display: block");
		 	 
		 	// Enable #x
		 	$('#nTemprtre').prop("disabled", false);
		 	$('#sLastName').prop("disabled", false);
		 	$('#sFrstName').prop("disabled", false);
		 	$('#sMiddName').prop("disabled", false);
		 	$('#sSuffixNm').prop("disabled", false);
		 	$('#cGenderxx').prop("disabled", false);
		 	$('#nCltAgexx').prop("disabled", false);
		 	$('#sMobileNo').prop("disabled", false);
		 	$('#sAddressx').prop("disabled", false);
		 	$('#sTownName').prop("disabled", false);
		 	$('#cWithSore').prop("disabled", false);
		 	$('#cWithPain').prop("disabled", false);
		 	$('#cWithCghx').prop("disabled", false);
		 	$('#cWithCold').prop("disabled", false);
		 	$('#cWithHdch').prop("disabled", false);
		 	$('#cStayedxx').prop("disabled", false);
		 	$('#cContactx').prop("disabled", false);
		 	$('#cTravelld').prop("disabled", false);
		 	$('#cTravlNCR').prop("disabled", false);
		}

		function DisableFields() {
			document.getElementById('confirmation').setAttribute("style","display: block");
		 	document.getElementById('btnSubmit').setAttribute("style","display: none");
		 	document.getElementById('waiver').setAttribute("style","display: none");

		 	$('#nTemprtre').prop("disabled", true);
		 	$('#sLastName').prop("disabled", true);
		 	$('#sFrstName').prop("disabled", true);
		 	$('#sMiddName').prop("disabled", true);
		 	$('#sSuffixNm').prop("disabled", true);
		 	$('#cGenderxx').prop("disabled", true);
		 	$('#nCltAgexx').prop("disabled", true);
		 	$('#sMobileNo').prop("disabled", true);
		 	$('#sAddressx').prop("disabled", true);
		 	$('#sTownName').prop("disabled", true);
		 	$('#cWithSore').prop("disabled", true);
		 	$('#cWithPain').prop("disabled", true);
		 	$('#cWithCghx').prop("disabled", true);
		 	$('#cWithCold').prop("disabled", true);
		 	$('#cWithHdch').prop("disabled", true);
		 	$('#cStayedxx').prop("disabled", true);
		 	$('#cContactx').prop("disabled", true);
		 	$('#cTravelld').prop("disabled", true);
		 	$('#cTravlNCR').prop("disabled", true);
		}
	
        $(document).ready(function () {
            //hide popup box
            document.getElementById('confirmation').setAttribute("style","display: none");
            
			//set branch info
			var branchcd = '<?php echo  $branchcd; ?>';		
				
            $.get("../param/getBranch.php", {branchcd: branchcd} , function(result){
				if (result.result == "success"){
					$('#sBranchNm').text(result.branchnm);
					$('#sAddresBr').text(result.addressx);
					$('#sTownBrch').text(result.towncity);
			 		$('#sEmailBrx').text(result.emailadd);
			 		$('#sLanLneBr').text(result.landline);		
				} else {
					alert(result.error.message);
					window.location.replace("https://www.guanzongroup.com.ph/")
				}
            }, 'json');	
            

            //set autocomplete for town/city
    		$("#sTownName").autocomplete({
				source: function( request, response ) {
        	  	// Fetch data
        	  	$.ajax({
                	  	url: "../param/getTownCity.php",
                	    type: 'post',
                	    dataType: "json",
                	    data: {
                	    	search: request.term
                	    },
                	    	success: function(data) {
                	     	response(data);
        	    		}
        	   		});
        	  	},
        	  
        		select: function (event, ui) {
        			// Set selection
        			$('#sTownName').val(ui.item.label); // display the selected text
        			$('#sTownIDxx').val(ui.item.value); // assign value of primary key to text field
        			return false;
        		}
        	});
            
            $('#btnSubmit').click(function (e) {
            	var sBranchCd = '<?php echo  $branchcd; ?>';		
            	var otp = '<?php echo  $otp; ?>';

            	var sTransNox = $('#sTransNox').val();
            	var nTemprtre = $('#nTemprtre').val();	
            	var sLastName = $('#sLastName').val();
            	var sFrstName = $('#sFrstName').val();
            	var sMiddName = $('#sMiddName').val();
            	var sSuffixNm = $('#sSuffixNm').val();
            	var cGenderxx = $('#cGenderxx').val();
            	var nCltAgexx = $('#nCltAgexx').val();
            	var sMobileNo = $('#sMobileNo').val();
            	var sAddressx = $('#sAddressx').val();
            	var sTownIDxx = $('#sTownIDxx').val();
            	var cWithSore = $('#cWithSore').val();
            	var cWithPain = $('#cWithPain').val();
            	var cWithCghx = $('#cWithCghx').val();
            	var cWithCold = $('#cWithCold').val();
            	var cWithHdch = $('#cWithHdch').val();
            	var cStayedxx = $('#cStayedxx').val();
            	var cContactx = $('#cContactx').val();
            	var cTravelld = $('#cTravelld').val();
            	var cTravlNCR = $('#cTravlNCR').val();

                $.get("checklist_submit.php", {sBranchCd: sBranchCd, sOTPNoxxx: otp, nTemprtre: nTemprtre, sLastName: sLastName, 
                	sFrstName: sFrstName, sMiddName: sMiddName, sSuffixNm: sSuffixNm, cGenderxx: cGenderxx, nCltAgexx: nCltAgexx,
                	sMobileNo: sMobileNo, sMobileNo: sMobileNo, sAddressx: sAddressx, sTownIDxx: sTownIDxx, sTownIDxx: sTownIDxx,
                	cWithSore: cWithSore, cWithPain: cWithPain, cWithCghx: cWithCghx, cWithCold: cWithCold, cWithHdch: cWithHdch,
                	cStayedxx: cStayedxx, cContactx: cContactx, cTravelld: cTravelld, cTravlNCR: cTravlNCR, sTransNox: sTransNox} , function(result){

    				if (result.result == "success"){
						$('#sTransNox').val(result.transnox);
    					$('#dTransact').val(result.datetime);
					 	$('#sOTPValue').val(result.otp);


					 	DisableFields();

					 	//send masking
					 	//$.get("send_text_masking.php", {transno: result.transnox} , function(result){
		    			//	if (result.result == "success"){
		    			//		DisableFields();
		    			//	} else {
		    			//		alert(result.error.message);
		    			//		window.close();
		    			//	}
		                //}, 'json');	 
    				} else {
    					alert(result.error.message);
    				}
                }, 'json');	
            });

            $('#btnConfirm').click(function (e) {
            	var branchcd = '<?php echo  $branchcd; ?>';
            	var transnox = $('#sTransNox').val();
            	var mobileno = $('#sMobileNo').val(); //$('#sOTPxxxxx').val();
				var mobilexx = $('#sMobileNo').val();
            	var otpnoxxx = $('#sOTPValue').val();

            	if (mobileno != mobilexx){
            		alert("Mobile no. did not matched to the original value given.");
            	} else{
            		$.get("checklist_otp_entry.php", {sTransNox: transnox, sOTPNoxxx: otpnoxxx} , function(result){
        				if (result.result == "success"){
        					window.location.replace("checklist_load.php?branch=" + branchcd + "&transno=" + transnox);
        				} else {
        					alert(result.error.message);
        					window.close();
        				}
                    }, 'json');
            	}
            });
            $('#btnCancel').click(function (e) {
            	EnableFields();
            });
        });
    </script>
    
    <div class="menu-bg">
    	<img class="logo" src="../images/guanzon-mc.png"></img>
    </div>   
    
    <div class="body-bg">         
    	<div class="xcontainer">    	    	
    		<div class="branch">
				<label id="sBranchNm"></label><br>
				<label id="sAddresBr"></label><br>
				<label id="sTownBrch"></label><br>
				<label id="sEmailBrx"></label><br>
				<label id="sLanLneBr"></label>				
			</div>
    	        	
        	<br>
        	<div class="xbutton">
        		<div class="param" id="param" style="display: block;">        		
        			<label for="nTemprtre">Temperature: </label>
        			<input type= "text" name="nTemprtre"  id="nTemprtre" value="" style="width:30%;" autocomplete="off">
            		<br><br>
    				<label for="sLastName">Last Name: </label>
        			<input type= "text" name="sLastName"  id="sLastName" value="" autocomplete="off">
            		<br><br>
              		<label for="sFrstName">First Name: </label>
              		<input type= "text" name="sFrstName"  id="sFrstName" value="" autocomplete="off">
              		<br><br>
              		<label for="sMiddName">Middle Name: </label>
              		<input type= "text" name="sMiddName"  id="sMiddName" value="" autocomplete="off">
              		<br><br>
              		<label for="sSuffixNm">Suffix Name: </label>
              		<input type= "text" name="sSuffixNm"  id="sSuffixNm" value=""  style="width:30%;" autocomplete="off">
              		<br><br>
              		<label for="cGenderxx">Gender: </label>
              		<select name="cGenderxx" id="cGenderxx" style="float:none;">
              		<option value="0" selected="selected"> Male </option>
              		<option value="1"> Female </option>
              		<option value="2"> LGBTQ </option>
              		</select>
              		<br><br>
              		<label for="nCltAgexx">Age: </label>
              		<input type= "text" name="nCltAgexx"  id="nCltAgexx" value="" style="width:30%;" autocomplete="off">
              		<br><br>
              		<label for="nCltAgexx">Mobile No: </label>
              		<input type= "text" name="sMobileNo"  id="sMobileNo" value="">
              		<br><br>
              		<label for="sAddressx">House No./Street/Barangay: </label>
              		<input type= "text" name="sAddressx"  id="sAddressx" value="" autocomplete="off">
              		<br><br>
              		<label for="sTownName">Town/City: </label>
              		<input type='text' id='sTownName'>
              		<input type='text' id='sTownIDxx' value="" style="display:none;">
              		<input type='text' id='sTransNox' value="" style="display:none;">
              		<input type='text' id='dTransact' value="" style="display:none;">
              		<input type='text' id='sOTPValue' value="" style="display:none;">
              		<br><br>
              		<hr>
              		<br>
              		
              		<label class="question">1. Are you experiencing: </label><br>
              		
              		<div class="xcontainer">
                  		<label class="question" for="cWithSore"> SORE THROAT </label>
                  		<select name="cWithSore" id="cWithSore">
                  		<option value="0" selected="selected"> No </option>
                  		<option value="1"> Yes </option>
                  		</select>
              		</div>
              		<div class="xcontainer">
                  		<label class="question" for="cWithPain"> BODY PAINS </label>
                  		<select name="cWithPain" id="cWithPain">
                  		<option value="0" selected="selected"> No </option>
                  		<option value="1"> Yes </option>
                  		</select>
                  	</div>
              		
              		<div class="xcontainer">
                  		<label class="question" for="cWithCghx"> COUGH </label>
                  		<select name="cWithCghx" id="cWithCghx">
                  		<option value="0" selected="selected"> No </option>
                  		<option value="1"> Yes </option>
                  		</select>
                  	</div>
              		
              		<div class="xcontainer">
                  		<label class="question" for="cWithCold"> COLDS </label>
                  		<select name="cWithCold" id="cWithCold">
                  		<option value="0" selected="selected"> No </option>
                  		<option value="1"> Yes </option>
                  		</select>
                  	</div>
              		
              		<div class="xcontainer">
                  		<label class="question" for="cWithHdch"> HEADACHE </label>
                  		<select name="cWithHdch" id="cWithHdch">
                  		<option value="0" selected="selected"> No </option>
                  		<option value="1"> Yes </option>
                  		</select>
                  	</div>
                  	
              		<br>
              		<label class="question">2. Have you worked together or stayed in the same close environment of a confirmed COVID-19 Case? </label>
              		<select name="cStayedxx" id="cStayedxx">
              		<option value="0" selected="selected"> No </option>
              		<option value="1"> Yes </option>
              		</select>
              		<br><br>
              		<label class="question">3. Have you had any contact with anyone with fever, cough, colds, and/or sore throat in the past 2 weeks? </label>
              		<select name="cContactx" id="cContactx">
              		<option value="0" selected="selected"> No </option>
              		<option value="1"> Yes </option>
              		</select>
              		<br><br>
              		<label class="question">4. Have you travelled outside of the Philippines in the last 14 days? </label>
              		<select name="cTravelld" id="cTravelld">
              		<option value="0" selected="selected"> No </option>
              		<option value="1"> Yes </option>
              		</select>
              		<br><br>
              		<label class="question">5. Have you travelled to any area in NCR aside from your home? </label>
              		<select name="cTravlNCR" id="cTravlNCR">
              		<option value="0" selected="selected"> No </option>
              		<option value="1"> Yes </option>
              		</select>
              		
              		<br><br>
              		<div class="xcontainer" id="waiver">
              			<label class="authorize">
              			I hereby authorize Guanzon Group to collect and process the data indicated herein for the purpose of effecting control
              			of the COVID-19 infection, I understand that my personal information is protected by R.A.10173, Data Privacy Act of 2012, 
              			and that I am required by R.A.11469, Bayanihan to Heal as One Act to provide truthful information.
              			</label>
              		</div>
            	</div>
            	
            	<div id="confirmation">
 					<label for="sOTPxxxxx">Kinldy review your entry and tap CONFIRM to continue:</label>
 					<input type= "text" name="sOTPxxxxx"  id="sOTPxxxxx" value="" style="display:none;"><br><br>
 					<input type="button" class="xbtn" id="btnConfirm" value="Confirm">
 					<input type="button" class="xbtn" id="btnCancel" value="Edit Checklist">
            	</div>
            	
            	<br>
            	<input type="button" class="xbtn" id="btnSubmit" value="Submit">
            	<br>
        	</div>
    	</div>
    </div>
</body>
</html>