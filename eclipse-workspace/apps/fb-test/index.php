<!doctype html>
<html>
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>Test</title>
</head>
<body>		
	<div>
    <div id="fb-root"></div>
        <script src="https://connect.facebook.net/en_US/all.js"></script>
        
        <script>
            FB.init({
            appId  : '292663425362349',
            status : true, // check login status
            cookie : true, // enable cookies to allow the server to access the session
            xfbml  : true, // parse XFBML
            channelUrl : 'https://restgk.guanzongroup.com.ph/fb-test/channel.html', // channel.html file
            oauth  : true // enable OAuth 2.0
            });
        </script>
        
        <script>
        //Load the JavaScript SDK asynchronously
        (function (d, s, id) {
            var js, fjs = d.getElementsByTagName(s)[0];
            if (d.getElementById(id)) return;
            js = d.createElement(s); js.id = id;
            js.src = "https://connect.facebook.net/en_US/sdk.js";
            fjs.parentNode.insertBefore(js, fjs);
        }(document, 'script', 'facebook-jssdk'));
    </script>
        
        <script>
           FB.Event.subscribe('edge.create', function(href, widget) {
				alert("The man has liked the page. Olala!");
				console.log('The man has liked the page. Olala!');
           });

           FB.Event.subscribe('edge.remove', function(href, widget) {
    	     	alert("The man has disliked the page. Sayang!");
    	     	console.log('The man has disliked the page. Sayang!');
    	   });
        </script>
        
        <div class="fb-like" data-href="https://www.facebook.com/guanzongroup" data-width="" data-layout="standard" data-action="like" data-size="large" data-share="true"></div>
    </div>
</body>
</html>