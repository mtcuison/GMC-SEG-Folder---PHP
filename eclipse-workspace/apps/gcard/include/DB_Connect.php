<?php	
	require_once 'Load_Config.php';		
	function DB()
	{
		try{
			$db = new PDO('mysql:host='.HOST.';dbname='.DATABASE.'', USER, PASSWORD);
			//echo "Success";
			return $db;
		}
		catch (PDOException $e) {
			return "Error!: " . $e->getMessage();
			//echo $e->getMessage();
			die();
		}
	}	
?>
