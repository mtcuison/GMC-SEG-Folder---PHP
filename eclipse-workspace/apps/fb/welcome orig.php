<?php 
    include_once('fb-config.php');
    
    if(!isset($_SESSION['fbUserId']) and $_SESSION['fbUserId']==""){
    	header('location: https://restgk.guanzongroup.com.ph/fb/index.php');
    	exit;
    } /*else{        
        session_destroy();
        unset($_SESSION['fbUserId']);
        unset($_SESSION['fbUserName']);
        unset($_SESSION['fbAccessToken']);
        header('location: https://guanzongroup.com.ph');
        exit;
    }*/
?>

<!doctype html>
<html lang="en-US" xmlns:fb="https://www.facebook.com/2008/fbml" xmlns:addthis="https://www.addthis.com/help/api-spec"  prefix="og: http://ogp.me/ns#" class="no-js">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>Login with Facebook using PHP SDK</title>
		
	<link rel="shortcut icon" href="https://www.guanzongroup.com.ph/wp-content/uploads/2018/05/cropped-logo_only.png">
	<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" crossorigin="anonymous">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/all.css" integrity="sha384-UHRtZLI+pbxtHCWp1t77Bi1L4ZtiqrqD80Kn4Z8NTSRyMA2Fd33n5dQ8lWUE00s/" crossorigin="anonymous">
    
    <script 
		async defer crossorigin="anonymous" 
		src="https://connect.facebook.net/en_US/sdk.js#xfbml=1&version=v8.0&appId=292663425362349&autoLogAppEvents=1" 
		nonce="D9m9Kw0G"></script>
</head>

<body>		
	<?php         
	   /**
	    * Page IDs
	    * 
	    * Guanzon Group = 841936465828643
	    * Los Pedritos = 261262891083435
	    * Monarch = 2039642249643703
	    * CarTrade = 185109168681968
	    * Honda Cars = 1729666640659207
	    * Nissan Pangasinan = 110789853624345
	    * 
	    */
    
    ?>

	<!--<pre> <?php echo "User ID:\t" . $_SESSION['fbUserId'] ?></pre> -->
	<!--<pre> <?php echo "User Name:\t"  . $_SESSION['fbUserName'] ?></pre> -->
	<!--<pre> <?php echo "Access Token:\t"  . $_SESSION['fbAccessToken'] ?></pre> -->
    
    <div 
    	class="fb-like" 
    	data-href="https://www.facebook.com/guanzongroup" 
    	data-width="" 
    	data-layout="standard" 
    	data-action="like" 
    	data-size="small" 
    	data-share="true"></div>
    		
	<div 
		class="fb-page" 
		data-href="https://www.facebook.com/guanzongroup" 
		data-tabs="" 
		data-width="" 
		data-height="" 
		data-small-header="false" 
		data-adapt-container-width="false" 
		data-hide-cover="false" 
		data-show-facepile="true">
		
		<blockquote 
			cite="https://www.facebook.com/guanzongroup" 
			class="fb-xfbml-parse-ignore"><a href="https://www.facebook.com/guanzongroup">Guanzon Group of Companies</a></blockquote></div>
	
	<br><br>
	<div 
		class="fb-page" 
		data-href="https://www.facebook.com/lospedritos2017" 
		data-tabs="" 
		data-width="" 
		data-height="" 
		data-small-header="false" 
		data-adapt-container-width="true" 
		data-hide-cover="false" 
		data-show-facepile="true">
		
		<blockquote 
			cite="https://www.facebook.com/lospedritos2017" 
			class="fb-xfbml-parse-ignore"><a href="https://www.facebook.com/lospedritos2017">Los Pedritos</a></blockquote></div>
	
	<br><br>		
	<div 
		class="fb-page" 
		data-href="https://www.facebook.com/monarch.hotel.ph" 
		data-tabs="" 
		data-width="" 
		data-height="" 
		data-small-header="false" 
		data-adapt-container-width="true" 
		data-hide-cover="false" 
		data-show-facepile="true">
		
		<blockquote 
			cite="https://www.facebook.com/monarch.hotel.ph" 
			class="fb-xfbml-parse-ignore"><a href="https://www.facebook.com/monarch.hotel.ph">The Monarch Hotel Philippines</a></blockquote></div>
	
	<br><br>
	<div 
		class="fb-page" 
		data-href="https://www.facebook.com/guanzoncartrade" 
		data-tabs="" 
		data-width="" 
		data-height="" 
		data-small-header="false" 
		data-adapt-container-width="true" 
		data-hide-cover="false" 
		data-show-facepile="true">
		
		<blockquote 
			cite="https://www.facebook.com/guanzoncartrade" 
			class="fb-xfbml-parse-ignore"><a href="https://www.facebook.com/guanzoncartrade">Guanzon Cartrade - Used Cars and Accessories</a></blockquote></div>
	
	<br><br>
	<div 
		class="fb-page" 
		data-href="https://www.facebook.com/hondacarspangasinaninc" 
		data-tabs="" 
		data-width="" 
		data-height="" 
		data-small-header="false" 
		data-adapt-container-width="true" 
		data-hide-cover="false" 
		data-show-facepile="true">
		
		<blockquote 
			cite="https://www.facebook.com/hondacarspangasinaninc" 
			class="fb-xfbml-parse-ignore"><a href="https://www.facebook.com/hondacarspangasinaninc">Honda Cars Pangasinan, Inc.</a></blockquote></div>
	
	<br><br>
	<div 
		class="fb-page" 
		data-href="https://www.facebook.com/nissanguanzongroup" 
		data-tabs="" 
		data-width="" 
		data-height="" 
		data-small-header="false" 
		data-adapt-container-width="true" 
		data-hide-cover="false" 
		data-show-facepile="true">
		
		<blockquote 
			cite="https://www.facebook.com/nissanguanzongroup" 
			class="fb-xfbml-parse-ignore"><a href="https://www.facebook.com/nissanguanzongroup">Nissan Pangasinan</a></blockquote></div>
	
	<br>
	<div class="container">		
    	<div class="row">
			<div class="col-sm-12 text-center p-5">
				<a href="logout.php" class="btn btn-warning"><i class="fas fa-file-download"></i> Visit Guanzon Group Website</a>
			</div>
		</div>
    </div> <!--/.container-->
</body>
</html>