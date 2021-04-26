<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//Load Composer's autoloader
require '../vendor/autoload.php';

require_once 'config.php';
require_once APPPATH.'/lib/samsung/knox_constant.php';
require_once 'Nautilus.php';
require_once 'CommonUtil.php';
require_once 'WebClient.php';
require_once 'WSHeaderValidatorFactory.php';
require_once 'MySQLAES.php';

$myheader = apache_request_headers();

if(!isset($myheader['g-api-id'])){
    echo "anggapoy nagawaan ti labat awa!";
    return;
}

if(stripos(APPSYSX, $myheader["g-api-id"]) === false){
    echo "anto la ya... sika lamet!";
    return;
}

$validator = (new WSHeaderValidatorFactory())->make($myheader['g-api-id']);

//var_dump($myheader);
$json = array();
if(!$validator->isHeaderOk($myheader)){
    $json["result"] = "error";
    $json["error"]["code"] = $validator->getErrorCode();
    $json["error"]["message"] = $validator->getMessage();
    echo json_encode($json);
    return;
}

//GET HEADERS HERE
//Product ID
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

//get the parameter
$data = file_get_contents('php://input');

//parse into json the PARAMETERS
$parjson = json_decode($data, true);
$email = $parjson["email"];

//load user's information...
$sql = "SELECT * FROM App_User_Master WHERE sEmailAdd = '$email' AND (sProdctID = '$prodctid' OR cGloblAct = '1') ";
$rows = $app->fetch($sql);

if($rows === null){
    $json["result"] = "error";
    $json["error"]["code"] = $app->getErrorCode();
    $json["error"]["message"] = "Error loading account. " . $app->getErrorMessage() ;
    echo json_encode($json);
    return;
}
elseif(empty($rows)){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_ACCOUNT;
    $json["error"]["message"] = "Invalid account detected." ;
    echo json_encode($json);
    return;
}

//since we have already validated the identity of the user, just assume that user is really existing...
$xpassword = CommonUtil::app_decrypt($rows[0]["sPassword"], $rows[0]["sItIsASIN"]);

sendForgotenPassword($email, $rows[0]["sUserName"], $xpassword);

$json["result"] = "success";
echo json_encode($json);
return;

//verification function...
function sendForgotenPassword($email, $name, $password){
    $to      = $email; // Send email to our user
    $subject = 'Forgotten Password'; // Give the email a subject
    $message = '
        
        Thanks you for keeping up with us!
        Listed below is your account credential.
        
        ------------------------
        email   : '.$email.'
        Password: '.$password.'
        ------------------------
            
        '; // Our message above including the link
    
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
        
        $mail->send();
    } catch (Exception $e) {
        echo 'Message could not be sent. Mailer Error: ', $mail->ErrorInfo;
    }
}

?>
