<?php 
require_once 'config.php';
require_once 'Nautilus.php';
require_once 'CommonUtil.php';
require_once 'FBPromoValidatorFactory.php';

define("PROCESS_LINK", "#"); ///xtest/process.php

//verify required parameters if present
if(isset($_GET['brc'])){
    $branchcd = strtoupper(base64_decode(htmlspecialchars($_GET['brc'])));
} else showMessage("Invalid parameter detected. (0)");

if(isset($_GET['typ'])){
    $doctypex = htmlspecialchars($_GET['typ']);
} else showMessage("Invalid parameter detected. (1)");

if(isset($_GET['nox'])){
    $docnoxxx = htmlspecialchars($_GET['nox']);
} else showMessage("Invalid parameter detected. (2)");

if(isset($_GET['mob'])){
    $mobileno = htmlspecialchars($_GET['mob']);
} else showMessage("Invalid parameter detected. (3)");

if(isset($_GET['nme'])){
    $clientnm = htmlspecialchars($_GET['nme']);
} else showMessage("Invalid parameter detected. (5)");

if(isset($_GET['stmp'])){
    //unix time
    $stamp = htmlspecialchars($_GET['stmp']);

    //datetime encoded on base64
    //$stamp = strtoupper(base64_decode(htmlspecialchars($_GET['stamp'])));
} else showMessage("Invalid parameter detected. (4)");
//end - verify required parameters if present

//account credentials
$prodctid = "IntegSys";
$$userid = "M001111122";

//initialize application driver
$app = new Nautilus(APPPATH);
if(!$app->LoadEnv($prodctid)) {
    showMessage($app->getErrorMessage() . "(" . $app->getErrorCode() . ")");
}
if(!$app->loaduser($prodctid, $userid)){ 
    showMessage($app->getErrorMessage() . "(" . $app->getErrorCode() . ")");
}

//get validator
$validator = (new FBPromoValidatorFactory())->make($app, $branchcd, $stamp);

//set information
//$validator->setMaster("clientnm", "test");
//$validator->setMaster("refercde", $doctypex);
//$validator->setMaster("refernme", $docnoxxx);

if ($validator == null) showMessage("Unable to initialize validator. ");
//check if document used was valid for the division
if ($validator->IsDocumentValid() == false) showMessage($validator->getMessage() . "(" . $validator->getErrorCode() . ")");
//get division
$division = $validator->getDivision();

/**
 * Show dialog message. Exit window by default.
 */
function showMessage($fsValue, $lbExit = true){
    echo '<script type="text/JavaScript">';
    echo 'alert("'. $fsValue .'");';
    if ($lbExit == true) echo 'window.close();';
    echo '</script>';
}
?>

<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	
	<title>Guanzon - Motorcycle</title>
	<link rel="shortcut icon" href="https://www.guanzongroup.com.ph/wp-content/uploads/2018/05/cropped-logo_only.png">
	<link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/all.css">
    <script src="https://code.jquery.com/jquery-3.5.0.js"></script>
</head>
<body>
	<div id="fb-root"></div>
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
                    document.getElementById('status').innerHTML = '<p>Please login your Facebook account.</p>';
                }
            });
    
            FB.Event.subscribe('edge.create', function(href, widget) {
        		//the user liked the page.
        		//there is a bug on facebook here.
        		//unreliable event.
       		});
    
    		FB.Event.subscribe('edge.remove', function(href, widget) {
    			//the user disliked the page. we are not issuing him a raffle ticket then.
    			if (href.indexOf("guanzongroup") >= 0 ){
    				document.getElementById('guanzonmcstat').setAttribute("value", "0");
    			} else if(href.indexOf("guanzon.mobitek.ph") >= 0 ){
    				document.getElementById('guanzonmpstat').setAttribute("value", "0");
    			} else if(href.indexOf("guanzoncartrade") >= 0 ){
    				document.getElementById('cartradestatx').setAttribute("value", "0");
    			} else if(href.indexOf("monarch.hotel.ph") >= 0 ){
    				document.getElementById('monarchstatxx').setAttribute("value", "0");
    			} else if(href.indexOf("lospedritos2017") >= 0 ){
    				document.getElementById('pedritosstatx').setAttribute("value", "0");
    			} else if(href.indexOf("hondacarspangasinaninc") >= 0 ){
    				document.getElementById('hondacarstatx').setAttribute("value", "0");
    			} else if(href.indexOf("nissanguanzongroup") >= 0 ){
    				document.getElementById('nissancarstat').setAttribute("value", "0");
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
                document.getElementById('status').innerHTML = '<p>Account was signing in. Please wait.</p>';
                
                if (response.authResponse) {
                    // Get and display the user profile data
                    getFbUserData();
                } else {
                    document.getElementById('status').innerHTML = '<p>User cancelled login or did not fully authorize.</p>';
                }
            }, {scope: 'email'});
        }
        
        // Fetch the user profile data from facebook
        function getFbUserData(){
            FB.api('/me', {locale: 'en_US', fields: 'id,first_name,last_name,email'},
            function (response) {
                document.getElementById('fbLink').setAttribute("onclick","fbLogout()");
                document.getElementById('fbLink').setAttribute("style","display: block");
                document.getElementById('fbLink').innerHTML = '<i class="fab fa-facebook-square"></i> Logout from Facebook!';
                document.getElementById('status').innerHTML = '<p>Thanks for logging in, ' + response.first_name + '!</p>';

                document.getElementById('guanzon-mc').setAttribute("style","display: none");                
                document.getElementById('guanzon-mp').setAttribute("style","display: none");
                document.getElementById('pedritos').setAttribute("style","display: none");
                document.getElementById('monarch').setAttribute("style","display: none");
                document.getElementById('cartrade').setAttribute("style","display: none");
                document.getElementById('honda').setAttribute("style","display: none");
                document.getElementById('nissan').setAttribute("style","display: none");
                
				var division = '<?php echo $division; ?>';
				var branchcd = '<?php echo $branchcd; ?>';

				switch (division) {
				case "0": //mobile phone
					document.getElementById('guanzon-mp').setAttribute("style","display: block"); break;
				case "1": //motorcycle
					document.getElementById('guanzon-mc').setAttribute("style","display: block"); break;
				case "2": //auto group
					if (branchcd == "V001")
						document.getElementById('cartrade').setAttribute("style","display: block");
					else if (branchcd == "N001")
						document.getElementById('nissan').setAttribute("style","display: block");
					else
						document.getElementById('honda').setAttribute("style","display: block");
						
					break;
				case "3": //monarch
					document.getElementById('monarch').setAttribute("style","display: block"); break;
				case "4":
					document.getElementById('pedritos').setAttribute("style","display: block"); break;
				}
            });
        }
        
        // Logout from facebook
        function fbLogout() {
            FB.logout(function() {            	
                document.getElementById('fbLink').setAttribute("onclick","fbLogin()");
                document.getElementById('fbLink').innerHTML = 'Login with Facebook!';

                document.getElementById('status').innerHTML = '';

                document.getElementById('guanzon-mc').setAttribute("style","display: none");
                document.getElementById('guanzon-mp').setAttribute("style","display: none");
                document.getElementById('pedritos').setAttribute("style","display: none");
                document.getElementById('monarch').setAttribute("style","display: none");
                document.getElementById('cartrade').setAttribute("style","display: none");
                document.getElementById('honda').setAttribute("style","display: none");
                document.getElementById('nissan').setAttribute("style","display: none");
                document.getElementById('fbLink').setAttribute("style","display: none");

                window.location.replace('https://www.guanzongroup.com.ph/');
            });
        }
	</script>
	
	<script type="text/javascript">
        $(document).ready(function () {
        	//Others
            $('#others').click(function (e) {
            	document.getElementById('others').setAttribute("style","display: none");
            	                
            	document.getElementById('guanzon-mc').setAttribute("style","display: block");                
                document.getElementById('guanzon-mp').setAttribute("style","display: block");
                document.getElementById('pedritos').setAttribute("style","display: block");
                document.getElementById('monarch').setAttribute("style","display: block");
                document.getElementById('cartrade').setAttribute("style","display: block");
                document.getElementById('honda').setAttribute("style","display: block");
                document.getElementById('nissan').setAttribute("style","display: block");

                var division = '<?php echo $division; ?>';
				var branchcd = '<?php echo $branchcd; ?>';

				switch (division) {
				case "0": //mobile phone
					document.getElementById('guanzon-mp').setAttribute("style","display: none"); break;
				case "1": //motorcycle
					document.getElementById('guanzon-mc').setAttribute("style","display: none"); break;
				case "2": //auto group
					if (branchcd == "V001")
						document.getElementById('cartrade').setAttribute("style","display: none");
					else if (branchcd == "N001")
						document.getElementById('nissan').setAttribute("style","display: none");
					else
						document.getElementById('honda').setAttribute("style","display: none");
						
					break;
				case "3": //monarch
					document.getElementById('monarch').setAttribute("style","display: none"); break;
				case "4":
					document.getElementById('pedritos').setAttribute("style","display: nones"); break;
				}
            });
            
            //Guanzon MC
            $('#guanzon-mc').click(function (e) {
            	document.getElementById("guanzon-mc").setAttribute("style","display: none");
            	document.getElementById("popup-mc").setAttribute("style","display: block");
            });
            $('#popup-mc .btns .btn2').click(function (e) {
            	document.getElementById("popup-mc").setAttribute("style","display: none");

            	var division = '<?php echo $division; ?>'; 
            	if (division == '1'){
            		document.getElementById('others').setAttribute("style","display: block");
            	}

            	alert(document.getElementById('guanzonmcstat').getAttribute("value"));
            });

            //Guanzon MP
            $('#guanzon-mp').click(function (e) {
            	document.getElementById("guanzon-mp").setAttribute("style","display: none");
            	document.getElementById("popup-mp").setAttribute("style","display: block");
            });
            $('#popup-mp .btns .btn2').click(function (e) {
            	document.getElementById("popup-mp").setAttribute("style","display: none");

            	var division = '<?php echo $division; ?>'; 
            	if (division == '0'){
            		document.getElementById('others').setAttribute("style","display: block");
            	}

            	alert(document.getElementById('guanzonmpstat').getAttribute("value"));
            });

            //Monarch
            $('#monarch').click(function (e) {
            	document.getElementById("monarch").setAttribute("style","display: none");
            	document.getElementById("popup-monarch").setAttribute("style","display: block");
            });
            $('#popup-monarch .btns .btn2').click(function (e) {
            	document.getElementById("popup-monarch").setAttribute("style","display: none");

            	var division = '<?php echo $division; ?>'; 
            	if (division == '3'){
            		document.getElementById('others').setAttribute("style","display: block");
            	}

            	alert(document.getElementById('monarchstatxx').getAttribute("value"));
            });
            

          	//Pedritos
            $('#pedritos').click(function (e) {
            	document.getElementById("pedritos").setAttribute("style","display: none");
            	document.getElementById("popup-pedritos").setAttribute("style","display: block");
            });
            $('#popup-pedritos .btns .btn2').click(function (e) {
            	document.getElementById("popup-pedritos").setAttribute("style","display: none");

            	var division = '<?php echo $division; ?>'; 
            	if (division == '4'){
            		document.getElementById('others').setAttribute("style","display: block");
            	}

            	alert(document.getElementById('pedritosstatx').getAttribute("value"));
            });

			//Guanzon Cartrade
            $('#cartrade').click(function (e) {
            	document.getElementById("cartrade").setAttribute("style","display: none");
            	document.getElementById("popup-cartrade").setAttribute("style","display: block");
            });
            $('#popup-cartrade .btns .btn2').click(function (e) {
            	document.getElementById("popup-cartrade").setAttribute("style","display: none");

            	var division = '<?php echo $division; ?>';
				var branchcd = '<?php echo $branchcd; ?>';
				if (division == '2' && branchcd == 'V001'){
            		document.getElementById('others').setAttribute("style","display: block");
            	}

            	alert(document.getElementById('cartradestatx').getAttribute("value"));
            });
            
          	//Honda
            $('#honda').click(function (e) {
            	document.getElementById("honda").setAttribute("style","display: none");
            	document.getElementById("popup-honda").setAttribute("style","display: block");
            });
            $('#popup-honda .btns .btn2').click(function (e) {
            	document.getElementById("popup-honda").setAttribute("style","display: none");

            	var division = '<?php echo $division; ?>';
				var branchcd = '<?php echo $branchcd; ?>';
				if (division == '2' && branchcd =='H001'){
            		document.getElementById('others').setAttribute("style","display: block");
            	}

            	alert(document.getElementById('hondacarstatx').getAttribute("value"));
            });
            
          	//Nissan
            $('#nissan').click(function (e) {
            	document.getElementById("nissan").setAttribute("style","display: none");
            	document.getElementById("popup-nissan").setAttribute("style","display: block");
            });
            $('#popup-nissan .btns .btn2').click(function (e) {
            	document.getElementById("popup-nissan").setAttribute("style","display: none");

            	var division = '<?php echo $division; ?>';
				var branchcd = '<?php echo $branchcd; ?>';
				if (division == '2' && branchcd == 'N001'){
            		document.getElementById('others').setAttribute("style","display: block");
            	}

            	alert(document.getElementById('nissancarstat').getAttribute("value"));
            });
        });
    </script>
	
	<!-- Display login status -->
    <div id="status"><p>Verifying encoded information. Please wait...</p></div>
        
    <a href="#" id="guanzon-mc" class="btn btn-primary btn-block" style="display: none;">Guanzon - Motorcycle</a>
    <a href="#" id="others" class="btn btn-primary btn-block" style="display: none;">Want more entries? Follow our partners too!</a>
    <a href="#" id="guanzon-mp" class="btn btn-primary btn-block" style="display: none;">Guanzon - Mobile Phone</a>
    <a href="#" id="cartrade" class="btn btn-primary btn-block" style="display: none;">Guanzon - Cartrade</a>
    <a href="#" id="monarch" class="btn btn-primary btn-block" style="display: none;">The Monarch Hotel</a>
    <a href="#" id="pedritos" class="btn btn-primary btn-block" style="display: none;">Los Pedritos</a>
    <a href="#" id="honda" class="btn btn-primary btn-block" style="display: none;">Honda Cars</a>
    <a href="#" id="nissan" class="btn btn-primary btn-block" style="display: none;">Nissan Pangasinan</a>
        
    <!-- Facebook login or logout button -->
    <a href="javascript:void(0);" onclick="fbLogin();" id="fbLink" class="btn btn-primary btn-block" style="display: none;"><i class="fab fa-facebook-square"></i> Login with Facebook!</a>
	    
	
	<div class="popup" id="popup-mc">
    	<h1>Like our page and click "Submit" to earn raffle entry!</h1>
    	<input type="hidden" id="guanzonmcstat" name="guanzonmcstat" value="1">
    	<div class="fb-page" data-href="https://www.facebook.com/guanzongroup" data-tabs="" data-width="" data-height="" data-small-header="false" data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="false"><blockquote cite="https://www.facebook.com/guanzongroup/" class="fb-xfbml-parse-ignore"><a href="https://www.facebook.com/guanzongroup/">Guanzon Group of Companies</a></blockquote></div>
      	<div class="btns">
      		<button type="submit" class="btn2">Submit</button>
      	</div>
    </div>
    
    <div class="popup" id="popup-mp">
    	<h1>Like our page and click "Submit" to earn raffle entry!</h1>
    	<input type="hidden" id="guanzonmpstat" name="guanzonmpstat" value="1">
    	<div class="fb-page" data-href="https://www.facebook.com/guanzon.mobitek.ph" data-tabs="" data-width="" data-height="" data-small-header="false" data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="false"><blockquote cite="https://www.facebook.com/guanzongroup/" class="fb-xfbml-parse-ignore"><a href="https://www.facebook.com/guanzongroup/">Guanzon Group of Companies</a></blockquote></div>
      	<div class="btns">
      		<button type="submit" class="btn2">Submit</button>
      	</div>
    </div>
    
    <div class="popup" id="popup-cartrade">
    	<h1>Like our page and click "Submit" to earn raffle entry!</h1>
    	<input type="hidden" id="cartradestatx" name="cartradestatx" value="1">
    	<div class="fb-page" data-href="https://www.facebook.com/guanzoncartrade" data-tabs="" data-width="" data-height="" data-small-header="false" data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="false"><blockquote cite="https://www.facebook.com/guanzongroup/" class="fb-xfbml-parse-ignore"><a href="https://www.facebook.com/guanzongroup/">Guanzon Group of Companies</a></blockquote></div>
      	<div class="btns">
      		<button type="submit" class="btn2">Submit</button>
      	</div>
    </div>
    
    <div class="popup" id="popup-monarch">
    	<h1>Like our page and click "Submit" to earn raffle entry!</h1>
    	<input type="hidden" id="monarchstatxx" name="monarchstatxx" value="1">
    	<div class="fb-page" data-href="https://www.facebook.com/monarch.hotel.ph" data-tabs="" data-width="" data-height="" data-small-header="false" data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="false"><blockquote cite="https://www.facebook.com/guanzongroup/" class="fb-xfbml-parse-ignore"><a href="https://www.facebook.com/guanzongroup/">Guanzon Group of Companies</a></blockquote></div>
      	<div class="btns">
      		<button type="submit" class="btn2">Submit</button>
      	</div>
    </div>
    
    <div class="popup" id="popup-pedritos">
    	<h1>Like our page and click "Submit" to earn raffle entry!</h1>
    	<input type="hidden" id="pedritosstatx" name="pedritosstatx" value="1">
    	<div class="fb-page" data-href="https://www.facebook.com/lospedritos2017" data-tabs="" data-width="" data-height="" data-small-header="false" data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="false"><blockquote cite="https://www.facebook.com/guanzongroup/" class="fb-xfbml-parse-ignore"><a href="https://www.facebook.com/guanzongroup/">Guanzon Group of Companies</a></blockquote></div>
      	<div class="btns">
      		<button type="submit" class="btn2">Submit</button>
      	</div>
    </div>
    
    <div class="popup" id="popup-honda">
    	<h1>Like our page and click "Submit" to earn raffle entry!</h1>
    	<input type="hidden" id="hondacarstatx" name="hondacarstatx" value="1">
    	<div class="fb-page" data-href="https://www.facebook.com/hondacarspangasinaninc" data-tabs="" data-width="" data-height="" data-small-header="false" data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="false"><blockquote cite="https://www.facebook.com/guanzongroup/" class="fb-xfbml-parse-ignore"><a href="https://www.facebook.com/guanzongroup/">Guanzon Group of Companies</a></blockquote></div>
      	<div class="btns">
      		<button type="submit" class="btn2">Submit</button>
      	</div>
    </div>
    
    <div class="popup" id="popup-nissan">
    	<h1>Like our page and click "Submit" to earn raffle entry!</h1>
    	<input type="hidden" id="nissancarstat" name="nissancarstat" value="1">
    	<div class="fb-page" data-href="https://www.facebook.com/nissanguanzongroup" data-tabs="" data-width="" data-height="" data-small-header="false" data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="false"><blockquote cite="https://www.facebook.com/guanzongroup/" class="fb-xfbml-parse-ignore"><a href="https://www.facebook.com/guanzongroup/">Guanzon Group of Companies</a></blockquote></div>
      	<div class="btns">
      		<button type="submit" class="btn2">Submit</button>
      	</div>
    </div>
</body>
</html>