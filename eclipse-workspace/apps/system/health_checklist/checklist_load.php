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

if(isset($_GET['branch'])){
    $branchcd = $_GET['branch'];
} else showMessage("Invalid parameter detected. 1", true);

 if(isset($_GET['transno'])){
     $transnox = $_GET['transno'];
 } else showMessage("Invalid parameter detected. 2", true);
 
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
        
        .authorize{
            font-weight: bold;
            text-align: center;
            font-size: 15px;
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
        $(document).ready(function () {
			//set branch info
			var branchcd = '<?php echo  $branchcd; ?>';				
			var transnox = '<?php echo  $transnox; ?>';
				
            $.get("../param/getBranch.php", {branchcd: branchcd} , function(result){
				if (result.result == "success"){
					$('#sBranchNm').text(result.branchnm);
					$('#sAddresBr').text(result.addressx);
					$('#sTownBrch').text(result.towncity);
			 		$('#sEmailBrx').text(result.emailadd);
			 		$('#sLanLneBr').text(result.landline);		
				} else {
					alert(result.error.message);
					window.close();
				}
            }, 'json');	

            $.get("../param/getTransaction.php", {transno: transnox} , function(result){
            	if (result.result == "success"){
            		$('#sClientNm').text(result.clientnm);
                	$('#nTemprtre').text(result.temprtre);
                	$('#nCltAgexx').text(result.cltagexx);
                	$('#cGenderxx').text(result.genderxx);
                	$('#sMobileNo').text(result.mobileno);
                	$('#sAddressx').text(result.addressx);
                	$('#sTownName').text(result.townname);
                	
                	$('#cWithSore').text(result.withsore);
                	$('#cWithPain').text(result.withpain);
                	$('#cWithCghx').text(result.withcghx);
                	$('#cWithCold').text(result.withcold);
                	$('#cWithHdch').text(result.withhdch);
                	$('#cStayedxx').text(result.stayedxx);
                	$('#cContactx').text(result.contactx);
                	$('#cTravelld').text(result.travelld);
                	$('#cTravlNCR').text(result.travlncr);

                	$('#dSubmittd').text(result.submittd);		
				} else {
					alert(result.error.message);
					window.close();
				}
            	
            }, 'json');

            $('#btnVisit').click(function (e) {
            	window.open("https://www.guanzongroup.com.ph/");
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
		    	<div class="param" id="param">
        			<div style="font-weight: bold; font-size: 13px;">
        				<label for="dSubmittd">D/T: </label>
        				<label id="dSubmittd"></label>
        				<br><br>
        				<label for="sClientNm">Fullname: </label>
        				<label id="sClientNm"></label>
        				<br>
        				<label for="cGenderxx">Gender: </label>
        				<label id="cGenderxx"></label>
        				<br>
        				<label for="nCltAgexx">Age: </label>
        				<label id="nCltAgexx"></label>
        				<br>
        				<label for="sMobileNo">Mobile No.: </label>
        				<label id="sMobileNo"></label>
        				<br>
        				<label for="sAddressx">Address: </label>
        				<label id="sAddressx"></label>
        				<br>
        				<label for="sTownName">Town/City: </label>
        				<label id="sTownName"></label>        				
        			</div>
        			<br>
        			<div style="font-size: 14px;">
        				<label for="nTemprtre">Temperature: </label>
        				<label id="nTemprtre" style="font-weight: bold;"></label>
        				<br>
        				<label>Was experiencing: </label><br>
        				<div style="margin-left: 20px;">
        					<label for="cWithSore"> SORE THROAT: </label>
        					<label id="cWithSore" style="font-weight: bold;"></label><br>
        					<label for="cWithPain"> BODY PAINS: </label>
        					<label id="cWithPain" style="font-weight: bold;"></label><br>
        					<label for="cWithCghx"> COUGH: </label>
        					<label id="cWithCghx" style="font-weight: bold;"></label><br>
        					<label for="cWithCold"> COLDS: </label>
        					<label id="cWithCold" style="font-weight: bold;"></label><br>
        					<label for="cWithHdch"> HEADACHE: </label>
        					<label id="cWithHdch" style="font-weight: bold;"></label><br>
        				</div>
        				<label for="cStayedxx">Have worked together or stayed in the same close environment of a confirmed COVID-19 Case:</label>
        				<label id="cStayedxx" style="font-weight: bold;"></label><br>
        				<label for="cContactx">Had any contact with anyone with fever, cough, colds, and/or sore throat in the past 2 weeks:</label>
        				<label id="cContactx" style="font-weight: bold;"></label><br>
        				<label for="cTravelld">Have travelled outside of the Philippines in the last 14 days:</label>
        				<label id="cTravelld" style="font-weight: bold;"></label><br>
        				<label for="cTravlNCR">Have travelled to any area in NCR aside from your home:</label>
        				<label id="cTravlNCR" style="font-weight: bold;"></label><br>
        			</div>
        			<br>
        			<div class="xcontainer">
              			<label class="authorize">
              			Your health checklist was submitted successfully. Thank you.
              			</label>
              		</div>
              		
              		<br>
              		<input type="button" class="xbtn" id="btnVisit" value="Visit our website now!">
              		<br>
    			</div>
			</div>
    	</div>
    </div>	
</body>
</html>