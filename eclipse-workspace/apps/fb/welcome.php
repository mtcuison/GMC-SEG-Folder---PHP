<?php 
    include_once('fb-config.php');
    
    if(!isset($_SESSION['fbUserId']) and $_SESSION['fbUserId']==""){
    	header('location: https://restgk.guanzongroup.com.ph/fb/index.php');
    	exit;
    }
?>

<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:fb="https://www.facebook.com/2008/fbml">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>Test</title>
</head>
<body>		        
	<div id="fb-root"></div>

    <script>           
        window.fbAsyncInit = function () {
            FB.init({
                appId  : '292663425362349',
                status : true, // check login status
                cookie : true, // enable cookies to allow the server to access the session
                xfbml  : true, // parse XFBML
                oauth  : true, // enable OAuth 2.0
                version: 'v3.2'
            });
            
            FB.api({ method: 'pages.isFan', page_id: '841936465828643' }, function(response) {
                console.log(response);
            	if (response) {
            	  alert("user has liked the page");
            	} else {
            	  alert("user has not liked the page");
            	}
            });
        };

        (function(d, s, id){
    	var js, fjs = d.getElementsByTagName(s)[0];
    	if (d.getElementById(id)) {return;}
    	js = d.createElement(s); js.id = id;
    	js.src = "//connect.facebook.net/en_US/sdk.js";
    	fjs.parentNode.insertBefore(js, fjs);

    	}(document, 'script', 'facebook-jssdk'));   
    </script>
</body>
</html>