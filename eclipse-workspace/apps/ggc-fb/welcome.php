<?php 
    include_once('fb-config.php');
    
    if(!isset($_SESSION['fbUserId']) and $_SESSION['fbUserId']==""){
    	header('location: https://restgk.guanzongroup.com.ph/fb/index.php');
    	exit;
    }
?>

<!doctype html>
<html lang="en-US" xmlns:fb="https://www.facebook.com/2008/fbml" xmlns:addthis="https://www.addthis.com/help/api-spec"  prefix="og: http://ogp.me/ns#" class="no-js">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<title>Login with Facebook using PHP SDK</title>
	<link rel="shortcut icon" href="https://www.guanzongroup.com.ph/wp-content/uploads/2018/05/cropped-logo_only.png">
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

	<pre> <?php echo "User ID:\t" . $_SESSION['fbUserId'] ?></pre>
	<pre> <?php echo "User Name:\t"  . $_SESSION['fbUserName'] ?></pre>
	<pre> <?php echo "Access Token:\t"  . $_SESSION['fbAccessToken'] ?></pre>
	
	<iframe src="https://www.facebook.com/plugins/page.php?href=https%3A%2F%2Fwww.facebook.com%2FJLEYMApartment%2F&tabs&width=500&height=214&small_header=false&adapt_container_width=true&hide_cover=false&show_facepile=true&appId=292663425362349" width="500" height="214" style="border:none;overflow:hidden" scrolling="no" frameborder="0" allowTransparency="true" allow="encrypted-media"></iframe>
</body>
</html>