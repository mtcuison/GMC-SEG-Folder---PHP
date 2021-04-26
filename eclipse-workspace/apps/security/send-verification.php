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

//GET HEADERS HERE
$prodctid = $myheader['g-api-id'];

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
    return;
}

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

if(!isset($parjson['email'])){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Unset EMAIL detected..";
    echo json_encode($json);
    return;
}

if(!isset($parjson['username'])){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Unset USERNAME detected..";
    echo json_encode($json);
    return;
}

if(!isset($parjson['password'])){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Unset PASSWORD detected..";
    echo json_encode($json);
    return;
}

if(!isset($parjson['hash'])){
    $json["result"] = "error";
    $json["error"]["code"] = AppErrorCode::INVALID_PARAMETER;
    $json["error"]["message"] = "Unset HASH detected..";
    echo json_encode($json);
    return;
}

$emailadd = htmlspecialchars($parjson['email']);
$username = htmlspecialchars($parjson['username']);
$password = htmlspecialchars($parjson['password']);
$salt = htmlspecialchars($parjson['hash']);

sendverification($emailadd, $username, $password, $salt);
return;

//verification function...
function sendverification($email, $name, $password, $hash){
    $password = CommonUtil::app_decrypt($password, $hash);
    
    $to      = $email; // Send email to our user
    $subject = 'Signup | Verification'; // Give the email a subject
    $message = '
        
        Thanks for signing up!
        Your account has been created, you can login with the following credentials after you have activated your account by pressing the url below.
        
        ------------------------
        email   : '.$email.'
        password: '.$password.'
        ------------------------
            
        Please click this link to activate your account:
        https://restgk.guanzongroup.com.ph/security/account_verify.php?email='.$email.'&hash='.$hash.'
            
        '; 
    $aes = new MySQLAES(APPKEYX);
    $sndrpaswd = $aes->decrypt(USERPAS);
    
    $mail = new PHPMailer(true);                              // Passing `true` enables exceptions
    try {
        $mail->SMTPDebug = 0;                                 // Enable verbose debug output
        $mail->isSMTP();                                      // Set mailer to use SMTP
        $mail->Host = '192.168.10.220';                       // Specify main and backup SMTP servers
        $mail->SMTPAuth = true;                               // Enable SMTP authentication
        $mail->Username = USERACT;                            // SMTP username
        $mail->Password = $sndrpaswd;                         // SMTP password
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
        
        $json["result"] = "success";
        $json["message"] = "Verification email sent successfully.";
        echo json_encode($json);
        return;
    } catch (Exception $e) {
        $json["result"] = "error";
        $json["error"]["code"] = "100";
        $json["error"]["message"] = 'Message could not be sent. Mailer Error: '. $mail->ErrorInfo;    }
        echo json_encode($json);
    }
?>