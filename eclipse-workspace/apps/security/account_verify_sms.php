<?php
require_once 'config.php';
require_once APPPATH.'/core/Nautilus.php';

if(!isset($_GET["email"])){
    echo "Unable to verify account. Email was not set properly!";
    return;
}

if(!isset($_GET["hash"])){
    echo "Unable to verify account. Hash was not set properly!";
    return;
}

$email = htmlspecialchars($_GET["email"]);
$hash = htmlspecialchars($_GET["hash"]);

//initialize driver to use
$app = new Nautilus(APPPATH);

//load the driver
if(!$app->LoadEnv("gRider")){
    echo "Unable to verify account. Error starting database.";
    return;
}

$sql = "SELECT * FROM App_User_Master";
$sql .= " WHERE sEmailAdd = '$email'";
$sql .= " AND sItIsASIN = '$hash'";
$rows = $app->fetch($sql);

//echo $sql;

if($rows == null){
    echo "Unable to verify account. Error error loading record.";
    return;
}

if($rows[0]["cActivatd"] == "1"){
    echo "This account was already verified";
    return;
}
else if($rows[0]["cActivatd"] == "3"){
    echo "This account was already deactivated";
    return;
}

$uid = $rows[0]["sUserIDxx"];
$sql = "UPDATE App_User_Master SET cActivatd = '1'";
//mac 2019.09.12
//RECORD THE DATE ACTIVATED
$sql .= ", dActivatd = " . CommonUtil::toSQL(date('Y/m/d H:i:s')); 
$sql .= ", dTimeStmp = " . CommonUtil::toSQL(date('Y/m/d H:i:s'));
$sql .= " WHERE sUserIDxx = '$uid'";
$result = $app->execute($sql);

if($result >= 0){
    if($rows[0]["sProdctID"] == "IntegSys" || $rows[0]["sProdctID"] == "Telecom"){
        echo "Your account was verified successfully. Please call MIS Support Group for authorization to use the application.";
    }
    else{
        echo "Your account was verified successfully. You can now login on Guanzon App.";
    }
    return;
}

echo "Unable to verify account. Your account cannot be updated.";

?>
