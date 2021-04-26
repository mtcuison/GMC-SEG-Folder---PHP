<?php
include_once('fb-config.php');

if(isset($_SESSION['fbUserId']) and $_SESSION['fbUserId']!=""){
	header('location: welcome.php');
	exit;
}

$permissions = array('user_likes'); // Optional permissions
$loginUrl = $helper->getLoginUrl('https://restgk.guanzongroup.com.ph/fb/fb-callback.php', $permissions);
?>

<!DOCTYPE html>
<html>
  <head>
  	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  
    <style>
        body, html {
            height: 100%;
            margin: 0;
        }
        .menu-bg {
            background-image: url("images/guanzon-mc header.png");
            height: 65px;
            background-position: center;
            background-repeat: no-repeat;
            background-size: cover;
        }
        .body-bg {
            background-image: url("images/guanzon-mc bg.jpg");
            height: 100%;
            background-position:75%;
            background-repeat: no-repeat;
            background-size: cover;
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
            background-color:transparent;
            float: right;
            height: 55px;
            width: 300px;
            padding-top: 0px;
            margin-right: 15%;
            margin-top: 15px;
            top: 5%;
            left: 70%;
        }
    </style>
    
    <title>Guanzon Group</title>
	
	<link rel="shortcut icon" href="https://www.guanzongroup.com.ph/wp-content/uploads/2018/05/cropped-logo_only.png">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.2.1/css/bootstrap.min.css" integrity="sha384-GJzZqFGwb1QTTN6wy59ffF1BuGJpLSa9DkKMp0DgiMDm4iYMj70gZWKYbI706tWS" crossorigin="anonymous">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.6.3/css/all.css" integrity="sha384-UHRtZLI+pbxtHCWp1t77Bi1L4ZtiqrqD80Kn4Z8NTSRyMA2Fd33n5dQ8lWUE00s/" crossorigin="anonymous">
	</head>
  
	<body>  
		<div class="menu-bg">
      		<img class="logo" src="images/guanzon-mc.png"></img>
        </div>      
        <div class="body-bg">
        	<br>
        	<div class="container">
    			<div class="row">
					<div class="col-sm-12 col-md-4 m-auto">
						<div class="border p-5 mb-5">
							<form method="post">
								<p class="text-center text-white">Login with Facebook for full access!</p>
								<div class="form-group">
								<a href="<?php echo htmlspecialchars($loginUrl); ?>" class="btn btn-primary btn-block"><i class="fab fa-facebook-square"></i> Login with Facebook!</a>
							</div>
							</form>
						</div>
					</div>
				</div>
    		</div> <!--/.container-->
        </div>
	</body>
</html>
