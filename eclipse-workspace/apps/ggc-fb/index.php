<?php
include_once('fb-config.php');

if(isset($_SESSION['fbUserId']) and $_SESSION['fbUserId']!=""){
    header('location: welcome.php');
    exit;
}

$permissions = array('email'); // Optional permissions
$loginUrl = $helper->getLoginUrl('https://restgk.guanzongroup.com.ph/ggc-fb/fb-callback.php', $permissions);
?>

<!DOCTYPE html>
<html>
	<head>	
		<link rel="shortcut icon" href="https://www.guanzongroup.com.ph/wp-content/uploads/2018/05/cropped-logo_only.png">
	</head>
	<body>
	</body>
</html>
