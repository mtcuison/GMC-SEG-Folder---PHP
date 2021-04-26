<?php
require_once 'config.php';
require_once 'Nautilus.php';
require_once 'CommonUtil.php';

date_default_timezone_set('Asia/Manila');

if(isset($_GET['brc'])){
    $branchcd = strtoupper(base64_decode(htmlspecialchars($_GET['brc'])));
} else showMessage("Access of this page is not allowed.");

if(isset($_GET['stamp'])){
    //unix time
    $stamp = htmlspecialchars($_GET['stamp']);
    
    //datetime encoded on base64
    //$stamp = strtoupper(base64_decode(htmlspecialchars($_GET['stamp'])));
} else showMessage("Access of this page is not allowed.");

if(isset($_GET['div'])){
    $division = htmlspecialchars($_GET['div']);
} else showMessage("Access of this page is not allowed.");

//convert unix to datetime then validate
if (!isValidTimeStamp(date("Y-m-d H:i:s", $stamp))){
    showMessage("Access of this page is not allowed. Invalid parameter detected.");
}


/**
 * Show dialog message. Exit window by default.
 */
function showMessage($fsValue, $lbExit = true){
    echo '<script type="text/JavaScript">';
    echo 'alert("'. $fsValue .'");';
    if ($lbExit == true) echo 'window.close();';
    echo '</script>';
}

/**
 * Check if a string is a valid Timestamp
 */
function isValidTimeStamp($date, $format = 'Y-m-d H:i:s'){
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
}
?>

<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	
	<title>Guanzon - Cartrade</title>
	<link rel="shortcut icon" href="https://www.guanzongroup.com.ph/wp-content/uploads/2018/05/cropped-logo_only.png">
   	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/all.css">
    
    <script src="https://code.jquery.com/jquery-3.5.0.js"></script>
    
    <link rel="stylesheet" href="style.css">
    <style>
        body, html {
            height: 100%;
            margin: 0;
        }
        .menu-bg {
            background-image: url("../images/guanzon-mc header.png");
            height: 65px;
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
        }
        .body-bg {
            background-image: url("../images/cartrade bg.png");
            height: 100%;
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
            color: #4c4c4c;
        }
        
        input{
            width: 100%;
            padding-left:5px;
            padding-right:5px;
            border: 1px solid #4c4c4c;
            border-radius: 5px;
        }
        
        select{
            width: 100%;
            border: 1px solid #4c4c4c;
            border-radius: 5px;
        }
        
        div .status{
            color: #4c4c4c;
        }
        
        div .xcontainer {
            border: 1px solid #4c4c4c;
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
	<script>     
    	window.fbAsyncInit = function() {
            // FB JavaScript SDK configuration and setup
            FB.init({
              appId : '292663425362349', // FB App ID
              status: true,
              cookie: true, 
              xfbml: true
            });
            
            // Check whether the user already logged in
            FB.getLoginStatus(function(response) {
            	document.getElementById('status').innerHTML = '<p>Retreiving login information.</p>';
                
                if (response.status === 'connected') {
                    //display user data
                    getFbUserData();
                } else{
                	document.getElementById("param").setAttribute("style","display: none");
                	document.getElementById('fbLink').setAttribute("style","display: block");
                    document.getElementById('fbLink').setAttribute("onclick","fbLogin()");
                    document.getElementById('fbLink').innerHTML = 'Login with Facebook!';
                    document.getElementById('status').innerHTML = 'Please login your Facebook account.';
                }
            });
        };
        
     	// Asynchronously
        (function (d) {
            var js, id = 'facebook-jssdk', ref = d.getElementsByTagName('script')[0];
            if (d.getElementById(id)) { return; }
            js = d.createElement('script'); js.id = id; js.async = true;
            js.src = "//connect.facebook.net/en_US/all.js";
            ref.parentNode.insertBefore(js, ref);
        } (document));
	   
        // Facebook login with JavaScript SDK
        function fbLogin() {
            FB.login(function (response) {
            	document.getElementById('param').setAttribute("style","display: none");
            	document.getElementById('fbLink').setAttribute("style","display: none");
                document.getElementById('status').innerHTML = 'Account was signing in. Please wait.';
                
                if (response.authResponse) {
                    // Get and display the user profile data
                    getFbUserData();
                } else {
                    document.getElementById('status').innerHTML = 'User cancelled login or did not fully authorize.';
                }
            }, {scope: 'email'});
        }
        
        // Fetch the user profile data from facebook
        function getFbUserData(){
            FB.api('/me', {locale: 'en_US', fields: 'id,first_name,last_name,email'},
            function (response) {				
				var division = '<?php echo  $division; ?>';				
				
                $.get("../param/download_raffle_entry_basis.php", {div: division} , function(result){
					if (result.result == "success"){
						var list = "";

						$.each(result.detail, function() {
							list = list + '<option value="' + this.sReferCde + '">' + this.sReferNme + '</option>';
						});

						document.getElementById('typ').innerHTML = list;

						document.getElementById("param").setAttribute("style","display: block");
		            	document.getElementById('fbLink').setAttribute("style","display: none;");
		                document.getElementById('fbLink').setAttribute("onclick","fbLogout()");
		                document.getElementById('fbLink').innerHTML = '<i class="fab fa-facebook-square"></i> Logout from Facebook!';
		                document.getElementById('status').innerHTML = 'Thanks for logging in!';
					} else {
						alert(result.error.message);
						window.close();
					}
                }, 'json');	
            });
        }
        
        // Logout from facebook
        function fbLogout() {
            FB.logout(function() {
            	document.getElementById("param").setAttribute("style","display: none");
            	document.getElementById('fbLink').setAttribute("style","display: block");
                document.getElementById('fbLink').setAttribute("onclick","fbLogin()");
                document.getElementById('fbLink').innerHTML = 'Login with Facebook!';
                document.getElementById('status').innerHTML = '';
                
                window.location.replace('https://www.guanzongroup.com.ph/');
            });
        }
	</script>
	
	<script type="text/javascript">
        $(document).ready(function () {            
            $('.param #btnSubmit').click(function (e) {
                var nme = $('.param #nme').val();                           	
                var typ = $('.param #typ').val();
                var nox = $('.param #nox').val();
                var mob = $('.param #mob').val();

                if (nme == ""){
					alert("UNSET customer name.");
					return false;
                }

                if (typ == ""){
					alert("UNSET document type.");
					return false;
                }

                if (nox == ""){
					alert("UNSET document number.");
					return false;
                }

                if (mob == ""){
                	alert("UNSET mobile number.");
                	return false;
                }

                var branch = '<?php echo base64_encode($branchcd); ?>';
                var stamp = '<?php echo $stamp; ?>';

             	// Send the input data to the server using get
                $.get("validate.php", {branch: branch, stamp: stamp, doctype: typ, docnmbr: nox, mobilex: mob} , function(result){
					if (result.result == "success"){
						var url = "/promo/fblike/fbpage-cartrade.php";
						url = url + '?brc=' + branch + '&stmp=' + stamp + '&typ=' + typ + '&nox=' + nox + '&mob=' + mob + '&nme=' + nme;
						
						window.location.replace(url);
					} else {
						alert(result.error.message);
					}
                }, 'json');
            });
        });
    </script>
    
    <!-- <div id="fb-root"></div> -->
    
    <div class="menu-bg">
    	<img class="logo" src="../images/cartrade 3.png"></img>
    </div>   
    
    <div class="body-bg">     
    	<div class="xcontainer">
    		<!-- Display login status -->
        	<div id="status" class="status"><p>Verifying encoded information. Please wait...</p></div>
        	
        	<div class="xbutton">
        		<div class="param" id="param" style="display: none;">
    				<label for="nme">Name: </label>
        			<input type= "text" name="nme"  id="nme" value="">
            		<br><br>
              		<label for="typ">Document Type: </label>
              		<select name="typ" id="typ">
              		</select>
              		<br><br>
              		<label for="nox">Document No: </label>
              		<input type= "text" name="nox"  id="nox" value="">
              		<br><br>
              		<label for="mob">Mobile No: </label>
              		<input type= "text" name="mob"  id="mob" value="">
              		<br><br>
              		<input type="submit" id="btnSubmit" value="Submit" class="xbtn">
            	</div>
            	
            	<a href="javascript:void(0);" onclick="fbLogin();" id="fbLink" class="xbtn" style="display: none;"><i class="fab fa-facebook-square"></i> Login with Facebook!</a>
        	</div>
    	</div>
    </div>
</body>
</html>