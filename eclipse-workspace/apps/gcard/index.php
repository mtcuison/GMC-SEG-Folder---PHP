<?php 
    require_once 'config.php';
    require_once APPPATH.'/core/Nautilus.php';
    require_once APPPATH.'/core/WSHeaderValidatorFactory.php';
    require_once 'MySQLAES.php';


    $emailadd = htmlspecialchars("");
    $username = htmlspecialchars("");
    $password = htmlspecialchars("");
    $salt = htmlspecialchars("");
    
    $password = CommonUtil::app_decrypt($password, $hash);
?>
