<?php
require_once 'config.php';
require_once 'Nautilus.php';
require_once 'CommonUtil.php';
require_once 'FBPromoValidator.php';

//verify required parameters if present
if(isset($_GET['ref'])){
    $transnox = base64_decode($_GET['ref']);
} else showMessage("Invalid parameter detected. (0)");


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

$validator = new FBPromoValidator($app, $transnox);

//check if document used was valid for the division
if ($validator->isSessionOK() == false)  showMessage($validator->getMessage());

//get division
$division = $validator->getDivision();

if ($division == "") showMessage("Branch division is not set.");

if ($division != "2") showMessage("This page is not suitable for your reference number. Thank you for your attempt.");

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
	
	<title>Guanzon - Cartrade</title>
	<link rel="shortcut icon" href="https://www.guanzongroup.com.ph/wp-content/uploads/2018/05/cropped-logo_only.png">
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
    			if(href.indexOf("nissanguanzongroup") >= 0 ){
    				document.getElementById('nissancarstat').setAttribute("value", "0");
    			} else if (href.indexOf("guanzongroup") >= 0 ){
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
            	var name = '<?php echo $clientnm; ?>';
                
                document.getElementById('fbLink').setAttribute("onclick","fbLogout()");
                document.getElementById('fbLink').setAttribute("style","display: block");
                document.getElementById('fbLink').innerHTML = '<i class="fab fa-facebook-square"></i> Logout from Facebook!';
                document.getElementById('status').innerHTML = '<p>Howdy ' + name + '! <br> Like our page and earn raffle tickets.</p>';

                document.getElementById('guanzon-mc').setAttribute("style","display: none");                
                document.getElementById('guanzon-mp').setAttribute("style","display: none");
                document.getElementById('pedritos').setAttribute("style","display: none");
                document.getElementById('monarch').setAttribute("style","display: none");
                document.getElementById('cartrade').setAttribute("style","display: block");
                document.getElementById('honda').setAttribute("style","display: none");
                document.getElementById('nissan').setAttribute("style","display: none");
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
                document.getElementById('xcontainer').setAttribute("style","display: none");

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
                document.getElementById('cartrade').setAttribute("style","display: none");
                document.getElementById('honda').setAttribute("style","display: block");
                document.getElementById('nissan').setAttribute("style","display: block");
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

            	var link = 'guanzongroup';
				var status = String(document.getElementById('guanzonmcstat').getAttribute("value"));
				
				var transno = '<?php echo $transnox; ?>';
				$.get("savedetail.php", {ref: transno, link: link, stat: status} , function(result){
					if (result.result != "success"){
						alert(result.error.message);
						window.location.replace('https://www.guanzongroup.com.ph/');
					}
                }, 'json');
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
           	
            	var link = 'guanzon.mobitek.ph';
				var status = String(document.getElementById('guanzonmpstat').getAttribute("value"));
				
				var transno = '<?php echo $transnox; ?>';
				$.get("savedetail.php", {ref: transno, link: link, stat: status} , function(result){
					if (result.result != "success"){
						alert(result.error.message);
						window.location.replace('https://www.guanzongroup.com.ph/');
					}
                }, 'json');
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
            	
            	var link = 'monarch.hotel.ph';
				var status = String(document.getElementById('monarchstatxx').getAttribute("value"));
				
				var transno = '<?php echo $transnox; ?>';
				$.get("savedetail.php", {ref: transno, link: link, stat: status} , function(result){
					if (result.result != "success"){
						alert(result.error.message);
						window.location.replace('https://www.guanzongroup.com.ph/');
					}
                }, 'json');
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
            	
            	var link = 'lospedritos2017';
				var status = String(document.getElementById('pedritosstatx').getAttribute("value"));
				
				var transno = '<?php echo $transnox; ?>';
				$.get("savedetail.php", {ref: transno, link: link, stat: status} , function(result){
					if (result.result != "success"){
						alert(result.error.message);
						window.location.replace('https://www.guanzongroup.com.ph/');
					}
                }, 'json');
            });

			//Guanzon Cartrade
            $('#cartrade').click(function (e) {
            	document.getElementById("cartrade").setAttribute("style","display: none");
            	document.getElementById("popup-cartrade").setAttribute("style","display: block");
            });
            $('#popup-cartrade .btns .btn2').click(function (e) {
            	document.getElementById("popup-cartrade").setAttribute("style","display: none");

            	var division = '<?php echo $division; ?>';
				if (division == '2'){
            		document.getElementById('others').setAttribute("style","display: block");
            	}
            	
            	var link = 'guanzoncartrade';
				var status = String(document.getElementById('cartradestatx').getAttribute("value"));
				
				var transno = '<?php echo $transnox; ?>';
				$.get("savedetail.php", {ref: transno, link: link, stat: status} , function(result){
					if (result.result != "success"){
						alert(result.error.message);
						window.location.replace('https://www.guanzongroup.com.ph/');
					}
                }, 'json');
            });
            
          	//Honda
            $('#honda').click(function (e) {
            	document.getElementById("honda").setAttribute("style","display: none");
            	document.getElementById("popup-honda").setAttribute("style","display: block");
            });
            $('#popup-honda .btns .btn2').click(function (e) {
            	document.getElementById("popup-honda").setAttribute("style","display: none");

            	var division = '<?php echo $division; ?>';
				if (division == '6'){
            		document.getElementById('others').setAttribute("style","display: block");
            	}
            	
            	var link = 'hondacarspangasinaninc';
				var status = String(document.getElementById('hondacarstatx').getAttribute("value"));
				
				var transno = '<?php echo $transnox; ?>';
				$.get("savedetail.php", {ref: transno, link: link, stat: status} , function(result){
					if (result.result != "success"){
						alert(result.error.message);
						window.location.replace('https://www.guanzongroup.com.ph/');
					}
                }, 'json');
            });
            
          	//Nissan
            $('#nissan').click(function (e) {
            	document.getElementById("nissan").setAttribute("style","display: none");
            	document.getElementById("popup-nissan").setAttribute("style","display: block");
            });
            $('#popup-nissan .btns .btn2').click(function (e) {
            	document.getElementById("popup-nissan").setAttribute("style","display: none");

            	var division = '<?php echo $division; ?>';
				if (division == '5'){
            		document.getElementById('others').setAttribute("style","display: block");
            	}

            	var link = 'nissanguanzongroup';
				var status = String(document.getElementById('nissancarstat').getAttribute("value"));

				var transno = '<?php echo $transnox; ?>';
				$.get("savedetail.php", {ref: transno, link: link, stat: status} , function(result){
					if (result.result != "success"){
						alert(result.error.message);
						window.location.replace('https://www.guanzongroup.com.ph/');
					}
                }, 'json');
            });
        });
    </script>
	
	<!-- <div id="fb-root"></div> -->
	<div class="menu-bg">
    	<img class="logo" src="../images/guanzon-mc.png"></img>
    </div> 
    
    <div class="body-bg">     
    	<div class="xcontainer" id="xcontainer">
    		<!-- Display login status -->
        	<div id="status" class="status"><p>Verifying encoded information. Please wait...</p></div>
        	
        	<div class="xbutton">
        		<a href="#" class="xbtn" id="guanzon-mc" style="display: none;">Guanzon - Motorcycle</a>
        		<a href="#" class="xbtn" id="others" style="display: none;">Want more entries? Follow our partners too!</a>
                <a href="#" class="xbtn" id="guanzon-mp" style="display: none;">Guanzon - Mobile Phone</a>
                <a href="#" class="xbtn" id="cartrade" style="display: none;">Guanzon - Cartrade</a>
                <a href="#" class="xbtn" id="monarch" style="display: none;">The Monarch Hotel</a>
                <a href="#" class="xbtn" id="pedritos" style="display: none;">Los Pedritos</a>
                <a href="#" class="xbtn" id="honda" style="display: none;">Honda Cars</a>
                <a href="#" class="xbtn" id="nissan" style="display: none;">Nissan Pangasinan</a>
                    
                <!-- Facebook login or logout button -->
                <a href="javascript:void(0);" onclick="fbLogin();" id="fbLink" class="xbtn" style="display: none;"><i class="fab fa-facebook-square"></i> Login with Facebook!</a>
        	</div>
    	</div>
    				
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
        	<div class="fb-page" data-href="https://www.facebook.com/guanzon.mobitek.ph" data-tabs="" data-width="" data-height="" data-small-header="false" data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="false"><blockquote cite="https://www.facebook.com/guanzon.mobitek.ph" class="fb-xfbml-parse-ignore"><a href="https://www.facebook.com/guanzon.mobitek.ph">Guanzon Mobitek</a></blockquote></div>
          	<div class="btns">
          		<button type="submit" class="btn2">Submit</button>
          	</div>
        </div>
        
        <div class="popup" id="popup-cartrade">
        	<h1>Like our page and click "Submit" to earn raffle entry!</h1>
        	<input type="hidden" id="cartradestatx" name="cartradestatx" value="1">
        	<div class="fb-page" data-href="https://www.facebook.com/guanzoncartrade" data-tabs="" data-width="" data-height="" data-small-header="false" data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="false"><blockquote cite="https://www.facebook.com/guanzoncartrade" class="fb-xfbml-parse-ignore"><a href="https://www.facebook.com/guanzoncartrade">Guanzon Cartrade - Used Cars and Accessories</a></blockquote></div>
          	<div class="btns">
          		<button type="submit" class="btn2">Submit</button>
          	</div>
        </div>
        
        <div class="popup" id="popup-monarch">
        	<h1>Like our page and click "Submit" to earn raffle entry!</h1>
        	<input type="hidden" id="monarchstatxx" name="monarchstatxx" value="1">
        	<div class="fb-page" data-href="https://www.facebook.com/monarch.hotel.ph" data-tabs="" data-width="" data-height="" data-small-header="false" data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="false"><blockquote cite="https://www.facebook.com/monarch.hotel.ph" class="fb-xfbml-parse-ignore"><a href="https://www.facebook.com/monarch.hotel.ph">The Monarch Hotel Philippines</a></blockquote></div>
          	<div class="btns">
          		<button type="submit" class="btn2">Submit</button>
          	</div>
        </div>
        
        <div class="popup" id="popup-pedritos">
        	<h1>Like our page and click "Submit" to earn raffle entry!</h1>
        	<input type="hidden" id="pedritosstatx" name="pedritosstatx" value="1">
        	<div class="fb-page" data-href="https://www.facebook.com/lospedritos2017" data-tabs="" data-width="" data-height="" data-small-header="false" data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="false"><blockquote cite="https://www.facebook.com/lospedritos2017" class="fb-xfbml-parse-ignore"><a href="https://www.facebook.com/lospedritos2017">Los Pedritos</a></blockquote></div>
          	<div class="btns">
          		<button type="submit" class="btn2">Submit</button>
          	</div>
        </div>
        
        <div class="popup" id="popup-honda">
        	<h1>Like our page and click "Submit" to earn raffle entry!</h1>
        	<input type="hidden" id="hondacarstatx" name="hondacarstatx" value="1">
        	<div class="fb-page" data-href="https://www.facebook.com/hondacarspangasinaninc" data-tabs="" data-width="" data-height="" data-small-header="false" data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="false"><blockquote cite="https://www.facebook.com/hondacarspangasinaninc" class="fb-xfbml-parse-ignore"><a href="https://www.facebook.com/hondacarspangasinaninc">Honda Cars Pangasinan, Inc.</a></blockquote></div>
          	<div class="btns">
          		<button type="submit" class="btn2">Submit</button>
          	</div>
        </div>
        
        <div class="popup" id="popup-nissan">
        	<h1>Like our page and click "Submit" to earn raffle entry!</h1>
        	<input type="hidden" id="nissancarstat" name="nissancarstat" value="1">
        	<div class="fb-page" data-href="https://www.facebook.com/nissanguanzongroup" data-tabs="" data-width="" data-height="" data-small-header="false" data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="false"><blockquote cite="https://www.facebook.com/nissanguanzongroup" class="fb-xfbml-parse-ignore"><a href="https://www.facebook.com/nissanguanzongroup">Nissan Pangasinan</a></blockquote></div>
          	<div class="btns">
          		<button type="submit" class="btn2">Submit</button>
          	</div>
        </div>
    </div>	
</body>
</html>