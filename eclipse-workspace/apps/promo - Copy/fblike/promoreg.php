<?php
require_once 'config.php';
require_once 'Nautilus.php';
require_once 'CommonUtil.php';

if(isset($_GET['brc'])){
    $branchcd = strtoupper(base64_decode(htmlspecialchars($_GET['brc'])));
} else showMessage("Access of this page is not allowed.");

//account credentials
$prodctid = RAFFLE_PRODUCT;
$userid = RAFFLE_USER;

//initialize application driver
$app = new Nautilus(APPPATH);
if(!$app->LoadEnv($prodctid)) {
    showMessage($app->getErrorMessage() . "(" . $app->getErrorCode() . ")");
}
if(!$app->loaduser($prodctid, $userid)){
    showMessage($app->getErrorMessage() . "(" . $app->getErrorCode() . ")");
}

//validate branch if exists
$sql = "SELECT" .
        "  a.sBranchCd" .
        ", a.sBranchNm" .
        ", b.cDivision" .
    " FROM Branch a" .
        " LEFT JOIN Branch_Others b ON a.sBranchCd = b.sBranchCd" .
    " WHERE a.cRecdStat = '1'" .
        " AND a.sBranchCd = " . CommonUtil::toSQL($branchcd);

$rows = $app->fetch($sql);

if($rows === null){
    showMessage($app->getErrorMessage() . "(" . $app->getErrorCode() . ")");
} elseif(empty($rows)){
    showMessage("User have invalid rights to access this page.");
}

$division = $rows[0]["cDivision"];

if ($division == null){
    showMessage("Invalid division value.");
}

date_default_timezone_set('Asia/Manila');
$date = new DateTime('now');
$stamp = $date->format(CommonUtil::format_timestamp);

//convert to unix time
$stamp = strtotime($stamp);

//convert to base64
//$stamp = base64_encode($stamp);

$nextURL = "/promo/fblike/encode.php?brc=";
$nextURL = $nextURL . base64_encode($branchcd) . "&stamp=" . $stamp . "&div=" . $division;

header("Location: $nextURL");
exit();

/**
 * Show dialog message. Exit window by default.
 */
function showMessage($fsValue, $lbExit = true){
    echo '<script type="text/JavaScript">';
    echo 'alert("'. $fsValue .'");';
    if ($lbExit == true) echo 'window.close();';
    echo '</script>';
}
?>

<html>
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=yes">
	
	<title>Facebook Promo LIKE Registration</title>
    <script src="https://code.jquery.com/jquery-3.5.0.js"></script>
</head>
<body>
</body>
</html>