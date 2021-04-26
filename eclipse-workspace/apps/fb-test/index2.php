<!doctype html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>Test</title>
</head>
<body>		
    <div id="fb-root"></div>
    <script src="https://connect.facebook.net/en_US/all.js"></script>
    
    <script>
        FB.init({
        appId  : '292663425362349',
        status : true, // check login status
        cookie : true, // enable cookies to allow the server to access the session
        xfbml  : true, // parse XFBML
        channelUrl : 'https://restgk.guanzongroup.com.ph/fb-test/channel.html' // channel.html file
        //oauth  : true // enable OAuth 2.0
        });

        FB.Event.subscribe('auth.authResponseChange', function(response) {
        	if (response.status === 'connected') {
        		console.log("ola");
          		testAPI();
            } else {
            	console.log("olat");
            	console.log(response.status);
            }
        });

     	// Load the SDK asynchronously
        (function(d){
            var js, id = 'facebook-jssdk', ref = d.getElementsByTagName('script')[0];
            if (d.getElementById(id)) {return;}
            js = d.createElement('script'); js.id = id; js.async = true;
            js.src = "//connect.facebook.net/en_US/all.js";
            ref.parentNode.insertBefore(js, ref);
        }(document));

        function testAPI() {
            FB.api('/me', function(response) {
            	console.log('Good to see you, ' + response.name + '.');
            });
            FB.api('/me/likes/841936465828643', function(response) {
            	console.log(response.data);
            });
        }
    </script>
</body>
</html>