<html>
<head>
	<script src="https://code.jquery.com/jquery-3.5.0.js"></script>
</head>
<body>	
	<?php
	if(isset($_GET['guanzonmcstat'])){
	    $guanzonmcstat = $_GET['guanzonmcstat'];
	    
	    if ($guanzonmcstat != 1){
	        echo '<script type="text/JavaScript">
                    alert("You disliked the guanzon mc page.");
                    window.close();
                </script>';
	    } else{
	        echo '<script type="text/JavaScript">
                    alert("The user do nothing/liked the guanzon mc page.");
                    window.close();
                </script>';
	    }
	} else if(isset($_GET['guanzonmpstat'])){
	    $guanzonmpstat = $_GET['guanzonmpstat'];
	    
	    if ($guanzonmpstat != 1){
	        echo '<script type="text/JavaScript">
                    alert("You disliked the guanzon mp page.");
                    window.close();
                </script>';
	    } else{
	        echo '<script type="text/JavaScript">
                    alert("The user do nothing/liked the guanzon mp page.");
                    window.close();
                </script>';
	    }
	} else if(isset($_GET['cartradestatx'])){
	    $cartradestatx = $_GET['cartradestatx'];
	    
	    if ($cartradestatx != 1){
	        echo '<script type="text/JavaScript">
                    alert("You disliked the guanzon cartrade page.");
                    window.close();
                </script>';
	    } else{
	        echo '<script type="text/JavaScript">
                    alert("The user do nothing/liked the guanzon cartrade page.");
                    window.close();
                </script>';
	    }
	} else if(isset($_GET['monarchstatxx'])){
	    $monarchstatxx = $_GET['monarchstatxx'];
	    
	    if ($monarchstatxx != 1){
	        echo '<script type="text/JavaScript">
                    alert("You disliked the monarch page.");
                    window.close();
                </script>';
	    } else{
	        echo '<script type="text/JavaScript">
                    alert("The user do nothing/liked the monarch page.");
                    window.close();
                </script>';
	    }
	} else if(isset($_GET['pedritosstatx'])){
	    $pedritosstatx = $_GET['pedritosstatx'];
	    
	    if ($pedritosstatx != 1){
	        echo '<script type="text/JavaScript">
                    alert("You disliked the los pedritos page.");
                    window.close();
                </script>';
	    } else{
	        echo '<script type="text/JavaScript">
                    alert("The user do nothing/liked the los pedritos page.");
                    window.close();
                </script>';
	    }
	} else if(isset($_GET['hondacarstatx'])){
	    $hondacarstatx = $_GET['hondacarstatx'];
	    
	    if ($hondacarstatx != 1){
	        echo '<script type="text/JavaScript">
                    alert("You disliked the honda cars page.");
                    window.close();
                </script>';
	    } else{
	        echo '<script type="text/JavaScript">
                    alert("The user do nothing/liked the honda cars page.");
                    window.close();
                </script>';
	    }
	} else if(isset($_GET['nissancarstat'])){
	    $nissancarstat = $_GET['nissancarstat'];
	    
	    if ($nissancarstat != 1){
	        echo '<script type="text/JavaScript">
                    alert("You disliked the nissan cars page.");
                    window.close();
                </script>';
	    } else{
	        echo '<script type="text/JavaScript">
                    alert("The user do nothing/liked the nissan cars page.");
                    window.close();
                </script>';
	    }
	} else {
	    echo '<script type="text/JavaScript">
                alert("Supa ka? Atam su gagawen mo? Ag ka kwatit ta anggapo lan lamang su antam!");
                window.close();
            </script>';
	}	
	exit;
    ?>
</body>
</html>