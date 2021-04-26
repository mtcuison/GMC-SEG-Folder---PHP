<?php
//API URL: https://docs.samsungknox.com/knox-guard/api-reference/Default.htm#section/Server
//define("KNOX_BASE", "https://eu-kg-integration.samsungknox.com");
//define("KNOX_VERSION", "/api/v1");

define("KNOX_BASE", "https://eu-kcs-api.samsungknox.com");
define("KNOX_VERSION", "/kcs/v1.1/kg");
define("KNOX_KDP_VERSION", "/kcs/v1/rp");
define("KNOX_API_TOKEN", "4acf4e04bc32889d41343bcdca160fc86c0afa95cc81c3f79a5fa391aa2a30d7");

//AUTHORIZED SAMSUNG KNOX GUARD RESELLER
define("KNOX_RESELLER_ID", "4154438742");

//CUSTOMER ID OF THE RESELLER
//=========================================================
//ASSIGNED CUSTOMER ID OF GUANZON MERCHANDISING CORPORATION
define("KNOX_CUSTOMER_GMC_ID", "9902915670");
//=========================================================
define("KNOX_URL_KDP_DEVICES_UPLOAD", "/devices/upload");
define("KNOX_URL_KDP_DEVICES_STATUS", "/devices/status");
define("KNOX_URL_KDP_DEVICES_LIST", "/devices");
define("KNOX_URL_KDP_CUSTOMERS_LIST", "/customers/list");
define("KNOX_URL_KDP_DEVICES_DELETE", "devices/delete");

define("KNOX_URL_AUTHORIZATION", "/authorization");
define("KNOX_URL_DEVICES_APPROVE", "/devices/approve");
define("KNOX_URL_DEVICES_APPROVEASYNC", "/devices/approveAsync");
define("KNOX_URL_DEVICES_BLINK", "/devices/blink");
define("KNOX_URL_DEVICES_BLINKASYNC", "/devices/blinkAsync");
define("KNOX_URL_DEVICES_COMPLETE","/devices/complete");
define("KNOX_URL_DEVICES_COMPLETEASYNC", "/devices/completeAsync");
define("KNOX_URL_DEVICES_DELETE", "/devices/delete");
define("KNOX_URL_DEVICES_DELETEASYNC", "/devics/deleteAsync");
define("KNOX_URL_DEVICES_GETDEVICELOG", "/devices/getDeviceLog");
define("KNOX_URL_DEVICES_GETPIN", "/devices/getPin");
define("KNOX_URL_DEVICES_LIST", "/devices/list");
define("KNOX_URL_DEVICES_LOCK", "/devices/lock");
define("KNOX_URL_DEVICES_LOCKASYNC", "/devices/lockAsync");
define("KNOX_URL_DEVICES_REJECT", "/devices/reject");
define("KNOX_URL_DEVICES_REJECTASYNC", "/devices/rejectAsync");
define("KNOX_URL_DEVICES_SENDMESSAGE", "/devices/sendMessage");
define("KNOX_URL_DEVICES_SENDMESSAGEASYNC", "/devices/sendMessageAsync");
define("KNOX_URL_DEVICES_UNLOCK", "/devices/unlock");
define("KNOX_URL_DEVICES_UNLOCKSYNC", "/devices/unlockAsync");
define("KNOX_URL_DEVICES_UPDATEINFO", "/devices/updateInfo");

?>
