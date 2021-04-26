<?php
include_once('fb-config.php');

if(isset($_SESSION['fbUserId']) and $_SESSION['fbUserId']!=""){
	header('location: welcome.php');
	exit;
}

$permissions = array('user_likes'); // Optional permissions
$loginUrl = $helper->getLoginUrl('https://restgk.guanzongroup.com.ph/fb/fb-callback.php', $permissions);
?>

<!doctype html>
<html lang="en-US" xmlns:fb="https://www.facebook.com/2008/fbml" xmlns:addthis="https://www.addthis.com/help/api-spec"  prefix="og: http://ogp.me/ns#" class="no-js">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">	
	
	<title>Guanzon Group</title>
	
	<link rel="shortcut icon" href="https://www.guanzongroup.com.ph/wp-content/uploads/2018/05/cropped-logo_only.png">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" crossorigin="anonymous">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/all.css" integrity="sha384-UHRtZLI+pbxtHCWp1t77Bi1L4ZtiqrqD80Kn4Z8NTSRyMA2Fd33n5dQ8lWUE00s/" crossorigin="anonymous">	
</head>

<body>
	<div class="container">
    	<div class="row">
			<div class="col-sm-12 col-md-4 m-auto">
				<br>
				<h6 class="text-center">Login using Facebook to gain full access</h6>
				
				<form method="post">
					<div class="form-group">
						<a href="<?php echo htmlspecialchars($loginUrl); ?>" class="btn btn-primary btn-block"><i class="fab fa-facebook-square"></i> Log in with Facebook!</a>
					</div>
				</form>
			</div>
		</div>
    </div> <!--/.container-->
</body>
</html>