<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;


//load required files
require_once 'config.php';
require_once APPPATH.'/core/Nautilus.php';
require_once APPPATH.'/core/WSHeaderValidatorFactory.php';
require_once 'MySQLAES.php';

//Load Composer's autoloader
require '../vendor/autoload.php';

//fetch pass headers
$myheader = apache_request_headers();

if(!isset($myheader['g-api-id'])){
    echo "anggapoy nagawaan ti labat awa!";
    return;
}

if(stripos(APPSYSX, $myheader["g-api-id"]) === false){
    echo "anto la ya... sika lamet!";
    return;
}

//verify headers
$factory = new WSHeaderValidatorFactory();
$validator = $factory->make($myheader['g-api-id']);

$json = array();
if(!$validator->isHeaderOk($myheader)){
    $json["result"] = "error";
    $json["error"]["message"] = $validator->getMessage();
    echo json_encode($json);
    return;
}

//fetch parameters passed
$data = file_get_contents('php://input');

//convert to json passed parameters
$parjson = json_decode($data, true);

//check if pass parameters are converted to json
if(is_null($parjson)){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Invalid parameters detected";
    echo json_encode($json);
    return false;
}

//verify if a name was passed
if(!isset($parjson['name'])){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Unset User AUTH NAME detected.";
    echo json_encode($json);
    return false;
}

//verify if an email was passed
if(!isset($parjson['mail'])){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Unset User AUTH EMAIL detected.";
    echo json_encode($json);
    return false;
}

//verify if a pswd was passed
if(!isset($parjson['pswd'])){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Unset User AUTH PASSWORD detected.";
    echo json_encode($json);
    return false;
}

//verify if a mobile was passed
if(!isset($parjson['mobile'])){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Unset User AUTH MOBILE detected.";
    echo json_encode($json);
    return false;
}

//GET HEADERS HERE
$prodctid = $myheader['g-api-id'];

//initialize driver to use
$app = new Nautilus(APPPATH);

//load the driver
if(!$app->LoadEnv($prodctid)){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = $app->getErrorMessage();
    echo json_encode($json);
    return;
}

//GET THE PARAMETERS here
$emailadd = htmlspecialchars($parjson['mail']);
$username = htmlspecialchars($parjson['name']);
$password = htmlspecialchars($parjson['pswd']);
$mobileno = htmlspecialchars($parjson['mobile']);

//Make sure aprameters are not empty/invalid
//test email add
if($emailadd == ""){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Empty User AUTH EMAIL detected.";
    echo json_encode($json);
    return;
}
elseif(!CommonUtil::isValidEmail($emailadd)){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Invalid User AUTH EMAIL detected.";
    echo json_encode($json);
    return;
}

//test client name
if($username == ""){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Empty User AUTH NAME detected.";
    echo json_encode($json);
    return;
}

//test password
if($password == ""){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Empty User AUTH PASSWORD detected.";
    echo json_encode($json);
    return;
}

//test mobile no
if($mobileno == ""){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Empty User AUTH MOBILE detected.";
    echo json_encode($json);
    return;
}

//check if account is existing
$sql = "SELECT *" .
    " FROM App_User_Master a" .
    " WHERE sProdctID = " . CommonUtil::toSQL($prodctid) .
      " AND sEmailAdd = " . CommonUtil::toSQL($emailadd);
//echo $sql;

$rows = $app->fetch($sql);

//var_dump($rows);

if($rows != null){
    $json["result"] = "error";
    if($app->getErrorMessage() !== ""){
        $json["error"]["code"] = $app->getErrorCode();
        $json["error"]["message"] = $app->getErrorMessage();
    }
    else{
        $json["error"]["code"] = AppErrorCode::EXISTING_ACCOUNT;
        $json["error"]["message"] = "Email account was already registered";
    }
    echo json_encode($json);
    return;
}

//create asin/salt
$salt = bin2hex(random_bytes(32));
//create user id
$userid = CommonUtil::GetNextCode("App_User_Master", "sUserIDxx", true, $app->getConnection(), "GAP0");

$app->beginTrans();

//Save the new account
$sql = "INSERT INTO App_User_Master" .
    " SET sUserIDxx = " . CommonUtil::toSQL($userid) .
    ", sProdctID = " . CommonUtil::toSQL($prodctid) .
    ", sUserName = " . CommonUtil::toSQL($username) .
    ", sEmailAdd = " . CommonUtil::toSQL($emailadd) .
    ", sPassword = " . CommonUtil::toSQL(CommonUtil::app_encrypt($password, $salt)) .
    ", sItIsASIN = " . CommonUtil::toSQL($salt) .
    ", sMobileNo = " . CommonUtil::toSQL($mobileno) .
    ", cGloblAct = '0'" .
    ", cActivatd = '0'" .
    ", dCreatedx = " . CommonUtil::toSQL(date('Y/m/d H:i:s'));
    
$affected = $app->execute($sql);
if($affected <= 0){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Unable to add account to our record... " . $app->getErrorMessage();
    $app->rollbackTrans();
    echo json_encode($json);
    return;
}

//kalyptus - 2019.07.30 02:51pm
//Please save also the information to App_User_Device
//Device use during sign-up are auto-verified...
$pcname 	= $myheader['g-api-imei'];
if(isset($myheader['g-api-model'])){
    $model = $myheader['g-api-model'];
}
else{
    $model = "UNKNOWN";
}
$token = $myheader['g-api-token'];

$sql = "INSERT INTO App_User_Device" .
      " SET sUserIDxx = " . CommonUtil::toSQL($userid) .
      ", sProdctID = " . CommonUtil::toSQL($prodctid) .
      ", sIMEINoxx = " . CommonUtil::toSQL($pcname) .
      ", sMobileNo = " . CommonUtil::toSQL($mobileno) .
      ", sModelCde = " . CommonUtil::toSQL($model) .
      ", sTokenIDx = " . CommonUtil::toSQL($token) .
      ", cVerified = '1'" . 
      ", dLastVrfy = " . CommonUtil::toSQL(date('Y/m/d H:i:s'));
      ", cRecdStat = '1'";

$affected = $app->execute($sql);
if($affected <= 0){
  $json["result"] = "error";
  $json["error"]["code"] = $app->getErrorCode();
  $json["error"]["message"] = "Unable to add device to our record... " . $app->getErrorMessage();
  $app->rollbackTrans();
  echo json_encode($json);
  return;
}

$app->commitTrans();

//send a verfication email
if (sendverification($emailadd, $username, $password, $salt) == true){
    // mac 2019.09.12
    //JUST FOR THE RECORD
    //update email status
    $sql = "UPDATE App_User_Master SET" .
                "  cEmailSnt = '1'" .
                ", nEmailSnt = nEmailSnt + 1" . 
            " WHERE sUserIDxx = " . CommonUtil::toSQL($userid);
    $affected = $app->execute($sql);
} else {
    // mac 2019.09.12
    //JUST FOR THE RECORD
    //update email status
    $sql = "UPDATE App_User_Master SET" .
                " nEmailSnt = nEmailSnt + 1" .
            " WHERE sUserIDxx = " . CommonUtil::toSQL($userid);
    $affected = $app->execute($sql);
}

//return success information
$json["result"] = "success";
$json["userid"] = $userid;
echo json_encode($json);
return;

//verification function...
function sendverification($email, $name, $password, $hash){
    $to      = $email; // Send email to our user
    $subject = 'Signup | Verification'; // Give the email a subject
    $message = '
        
        Thanks for signing up!
        Your account has been created, you can login with the following credentials after you have activated your account by pressing the url below.
        
        ------------------------
        email   : '.$email.'
        Password: '.$password.'
        ------------------------
            
        Please click this link to activate your account:
        https://restgk.guanzongroup.com.ph/security/account_verify.php?email='.$email.'&hash='.$hash.'
            
        '; // Our message above including the link
    
    //$headers = 'From:noreply@guanzongroup.com.ph' . "\r\n"; // Set from headers
    
    //please check [mail function] section of php.ini and set the necessary parameters accordingly
    //ini_set("SMTP","tls://192.168.10.220");
    //ini_set("smtp_port","587");
    //mail($to, $subject, $message, $headers); // Send our email
    
    //kalyptus - 2019.06.29 11:14am
    //assign to CONSTANTS the information of sender
    $aes = new MySQLAES(APPKEYX);
    $sndrpaswd = $aes->decrypt(USERPAS);
    
    $mail = new PHPMailer(true);                              // Passing `true` enables exceptions
    try {
        $mail->SMTPDebug = 0;                                 // Enable verbose debug output
        $mail->isSMTP();                                      // Set mailer to use SMTP
        $mail->Host = '192.168.10.220';                       // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                               // Enable SMTP authentication
        $mail->Username = USERACT;       // SMTP username
        $mail->Password = $sndrpaswd;                      // SMTP password
        $mail->SMTPSecure = 'tls';                            // Enable TLS encryption, `ssl` also accepted
        $mail->Port = 587;                                    // TCP port to connect to
        //$mail->SMTPAutoTLS = true;
        $mail->setFrom('noreply@guanzongroup.com.ph', 'Guanzon App');
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        
        $mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );
        
        return $mail->send();
    } catch (Exception $e) {
        echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
    }
}
?>
