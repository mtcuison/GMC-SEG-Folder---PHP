<?php
require_once 'Crypto.php';
require_once 'CommonUtil.php';
require_once 'Nautilus.php';

//Firebase Cloud Messaging HTTP protocol reference
//https://firebase.google.com/docs/cloud-messaging/http-server-ref
//https://stackoverflow.com/questions/41562823/fcm-remote-notifications-payload-for-ios-and-android
//https://www.pluralsight.com/guides/push-notifications-with-firebase-cloud-messaging
//https://firebase.google.com/docs/cloud-messaging/send-message
//https://www.quora.com/How-do-you-send-a-push-notification-with-Firebase-in-Java(sample)

class NotificationRequest{
    private $_app_driver;
    
    //Type of message to be delivered. e.g. 00001->ACCOUNT LOGIN ON NEW DEVICE
    private $_msg_type;

    //Parent transaction replied by this notification/message
    private $_msg_parent;
    
    //Displayed message if upon receipt of notification
    private $_msg_title;
    
    //Content of actual notification/message
    private $_msg_actual;
    
    //JSON data to be process
    private $_msg_data;
    
    //URL of an image to be displayed...
    private $_msg_url;
    
    //Application used by the sender
    //GuanzonApp/Telecom/IntegSys/SYSTEM
    private $_msg_source_app;
    
    //USER ID of the sender or NMM_SysMon_List->sSysMonID
    private $_msg_source_user;
    
    //IMEI No
    private $_msg_source_imeino;
    
    //Info of Users tagged as the OBSERVER of how the recepient perform the request...
    private $_msg_auditor;
    
    //Users tagged as the recepient of the notification/message...
    private $_msg_rcpt;
    
    public function __construct($driver){
        $this->_app_driver = $driver;
        
        $this->_msg_type = null;
        $this->_msg_parent = null;
        $this->_msg_title = null;
        $this->_msg_actual = null;
        $this->_msg_data = null;
        $this->_msg_url = null;
        $this->_msg_source_app = null;
        $this->_msg_source_user = null;
        $this->_msg_auditor = null;
        $this->_msg_rcpt = null;
    }
    
    public function setType($type){
        $this->_msg_type = $type;
    }
    
    public function setParent($parent){
        $this->_msg_parent = $parent;
    }

    public function setTitle($title){
        $this->_msg_title = $title;
    }
    
    public function setMessage($msg){
        $this->_msg_actual = $msg;
    }

    public function setData($data){
        $this->_msg_data = $data;
    }
    
    public function setURL($url){
        $this->_msg_url = $url;
    }
    
    public function setSourceApp($app){
        $this->_msg_source_app = $app;
    }
    
    public function setSourceUser($user){
        $this->_msg_source_user = $user;
    }

    public function setDevice($device){
        $this->_msg_source_imeino = $device;
    }
    
    public function setAuditor($audit){
        $this->_msg_auditor = $audit;    
    }
    
    public function setRecepient($rcpt){
        $this->_msg_rcpt = $rcpt;
    }

    //Saves the notification request to the server...
    //Returns the json to be return to the requesting user...
    public function saveRequest(){
        $date = new DateTime('now');
        $year = $date->format("Y");
        
        $transno = CommonUtil::GetNextCode("NMM_Request_Master", "sTransNox", $year, $this->_app_driver->getConnection(), "MX01");
        
        $date = new DateTime('now');
        $stamp = $date->format(CommonUtil::format_timestamp);
        
        $this->_app_driver->beginTrans();
        
        //create the master record of notification request...
        $sql = "INSERT INTO NMM_Request_Master" .
            " SET sTransNox = '$transno'" .
            ", sParentxx = '$this->_msg_parent'" .
            ", dCreatedx = '$stamp'" .
            ", sAppSrcex = '$this->_msg_source_app'" .
            ", sCreatedx = '$this->_msg_source_user'" .
            ", sMsgTitle = '$this->_msg_title'" .
            ", sMessagex = '$this->_msg_actual'" .
            ", sImageURL = '$this->_msg_url'" .
            ", sDataSndx = '$this->_msg_data'" .
            ", sMsgTypex = '$this->_msg_type'";
        
        $json = array();
        if($this->_app_driver->execute($sql) <= 0){
            $json["result"] = "error";
            $json["error"]["code"] = $this->_app_driver->getErrorCode();
            $json["error"]["message"] = "Unable to create notification record. " . $this->_app_driver->getErrorMessage();
            $this->_app_driver->rollbackTrans();
            return $json;
        }
        
        //Perform saving of sender info if not system initiated...
        if(strcasecmp($this->_msg_source_app, 'SYSTEM') != 0){
            //save the info of original device            
            //assume that account/device of sender is valid...
            $sql = "INSERT INTO NMM_Request_Sender" . 
                  " SET sTransNox = '$transno'" .
                     ", sAppSrcex = '$this->_msg_source_app'" . 
                     ", sCreatedx = '$this->_msg_source_user'" . 
                     ", sIMEINoxx = '$this->_msg_source_imeino'" . 
                     ", cSentxxxx = '1'" . 
                     ", dSentxxxx = '$stamp'";
            if($this->_app_driver->execute($sql) <= 0){
                $json["result"] = "error";
                $json["error"]["code"] = $this->_app_driver->getErrorCode();
                $json["error"]["message"] = "Unable to create notification record of sender. " . $this->_app_driver->getErrorMessage();
                $this->_app_driver->rollbackTrans();
                return $json;
            }

            //look only the activated account and verified devices of the sender
            $sql = "SELECT a.sUserIDxx, a.sProdctID, a.sIMEINoxx, a.sTokenIDx, b.sUserName, b.sEmailAdd" . 
                  " FROM App_User_Device a" . 
                        " LEFT JOIN App_User_Master b ON a.sUserIDxx = b.sUserIDxx" . 
                  " WHERE a.sUserIDxx = '$this->_msg_source_user'" . 
                    " AND a.sProdctID = '$this->_msg_source_app'" . 
                    " AND a.sIMEINoxx <> '$this->_msg_source_imeino'" . 
                    " AND a.cVerified = '1'" . 
                    " AND b.cActivatd = '1'" .
                    " AND LENGTH(IFNULL(a.sTokenIDx, '')) > 20";
            $rows = $this->_app_driver->fetch($sql);
            
            if($rows === null){
                $json["result"] = "error";
                $json["error"]["code"] = $this->_app_driver->getErrorCode();
                $json["error"]["message"] = "Unable to load user account and device! " . $this->_app_driver->getErrorMessage();
                $this->_app_driver->rollbackTrans();
                return $json;
            }
            
            $rows_found = sizeof($rows);
            for($ctr=0;$ctr<$rows_found; $ctr++){
                $appid = $rows[$ctr]["sProdctID"];
                $usrid = $rows[$ctr]["sUserIDxx"];
                $imeix = $rows[$ctr]["sIMEINoxx"];
                $sql = "INSERT INTO NMM_Request_Sender" .
                    " SET sTransNox = '$transno'" .
                    ", sAppSrcex = '$appid'" .
                    ", sCreatedx = '$usrid'" .
                    ", sIMEINoxx = '$imeix'" .
                    ", cSentxxxx = '0'";
                if($this->_app_driver->execute($sql) <= 0){
                    $json["result"] = "error";
                    $json["error"]["code"] = $this->_app_driver->getErrorCode();
                    $json["error"]["message"] = "Unable to create detailed notification record of sender. " . $this->_app_driver->getErrorMessage();
                    $this->_app_driver->rollbackTrans();
                    return $json;
                } //if($this->_app_driver->execute($sql) <= 0){
            } //for($ctr=0;$ctr<$rows_found; $ctr++){
        } //if(strcasecmp($this->_msg_source_app, 'SYSTEM') != 0){
        
        //Send message to the OBSERVERS
        if($this->_msg_auditor != null){
            $rows_found = sizeof($this->_msg_auditor);
            for($ctr=0;$ctr<$rows_found; $ctr++){
                $appid = $this->_msg_auditor[$ctr]["app"];
                $usrid = $this->_msg_auditor[$ctr]["user"];
                $imeix = "";
                
                //look only the activated account and verified devices of the observer
                $sql = "SELECT a.sUserIDxx, a.sProdctID, a.sIMEINoxx, a.sTokenIDx, b.sUserName, b.sEmailAdd" .
                    " FROM App_User_Device a" .
                    " LEFT JOIN App_User_Master b ON a.sUserIDxx = b.sUserIDxx" .
                    " WHERE a.sUserIDxx = '$usrid'" .
                    " AND a.sProdctID = '$appid'" .
                    " AND a.cVerified = '1'" .
                    " AND b.cActivatd = '1'" . 
                    " AND LENGTH(IFNULL(a.sTokenIDx, '')) > 20" .
                    " ORDER BY sUserIDxx, sProdctID, sIMEINoxx";
                $rows = $this->_app_driver->fetch($sql);
                
                if($rows === null){
                    $json["result"] = "error";
                    $json["error"]["code"] = $this->_app_driver->getErrorCode();
                    $json["error"]["message"] = "Unable to load MONITORING account and device! " . $this->_app_driver->getErrorMessage();
                    $this->_app_driver->rollbackTrans();
                    return $json;
                }
                
                $rows_found_dev = sizeof($rows);
                for($ctrx=0;$ctrx<$rows_found_dev; $ctrx++){
                    $imeix = $rows[$ctrx]["sIMEINoxx"];
                    //Insert record to recepients info
                    $sql = "INSERT INTO NMM_Request_Recepient" .
                        " SET sTransNox = '$transno'" .
                        ", sRecpntGr = null" .
                        ", sAppRcptx = '$appid'" .
                        ", sRecpntxx = '$usrid'" .
                        ", sIMEINoxx = '$imeix'" .
                        ", cMonitorx = '1'" .
                        ", cMesgStat = '0'";
                    if($this->_app_driver->execute($sql) <= 0){
                        $json["result"] = "error";
                        $json["error"]["code"] = $this->_app_driver->getErrorCode();
                        $json["error"]["message"] = "Unable to create notification record of observers. " . $this->_app_driver->getErrorMessage();
                        $this->_app_driver->rollbackTrans();
                        return $json;
                    } //if($this->_app_driver->execute($sql) <= 0){
                } //for($ctr=0;$ctr<$rows_found_dev; $ctr++)
            } //for($ctr=0;$ctr<$rows_found; $ctr++){
        } //if($this->_msg_auditor != null){
        
        //Send message to the RECEPIENTS
        $rows_found = sizeof($this->_msg_rcpt);
        for($ctr=0;$ctr<$rows_found; $ctr++){
            $appid = $this->_msg_rcpt[$ctr]["app"];
            $usrid = $this->_msg_rcpt[$ctr]["user"];
            if(isset($this->_msg_rcpt[$ctr]["group"])){
                $grpid = $this->_msg_rcpt[$ctr]["group"];
            }
            else{
                $grpid = "";
            }
            $imeix = "";
            
            if(empty($grpid)){
                //look only the activated account and verified devices of the recepient
                $sql = "SELECT a.sUserIDxx, a.sProdctID, a.sIMEINoxx, a.sTokenIDx, b.sUserName, b.sEmailAdd" .
                    " FROM App_User_Device a" .
                    " LEFT JOIN App_User_Master b ON a.sUserIDxx = b.sUserIDxx" .
                    " WHERE a.sUserIDxx = '$usrid'" .
                    " AND a.sProdctID = '$appid'" .
                    " AND a.cVerified = '1'" .
                    " AND b.cActivatd = '1'" .
                    " AND LENGTH(IFNULL(a.sTokenIDx, '')) > 20" .
                    " ORDER BY sUserIDxx, sProdctID, sIMEINoxx";
            }
            else{
                $sql = "SELECT a.sUserIDxx, a.sProdctID, a.sIMEINoxx, a.sTokenIDx, b.sUserName, b.sEmailAdd" .
                      " FROM NMM_Group_Member c" . 
                            " LEFT JOIN App_User_Master b ON c.sUserIDxx = b.sUserIDxx" .
                            " LEFT JOIN App_User_Device a ON a.sProdctID = c.sRecpntGr AND a.sUserIDxx = c.sUserIDxx" .
                      " WHERE c.sNMMGrpID = '$grpid'" .
                        " AND a.cVerified = '1'" .
                        " AND b.cActivatd = '1'" .
                        " AND LENGTH(IFNULL(a.sTokenIDx, '')) > 20" .
                      " ORDER BY sUserIDxx, sProdctID, sIMEINoxx";
            }
            
            $rows = $this->_app_driver->fetch($sql);
            
            if($rows === null){
                $json["result"] = "error";
                $json["error"]["code"] = $this->_app_driver->getErrorCode();
                $json["error"]["message"] = "Unable to load RECEPIENT account and device! " . $this->_app_driver->getErrorMessage();
                $this->_app_driver->rollbackTrans();
                return $json;
            }
            
            $rows_found_dev = sizeof($rows);
            for($ctrx=0;$ctrx<$rows_found_dev; $ctrx++){
                $imeix = $rows[$ctrx]["sIMEINoxx"];
                //Insert record to recepients info
                $sql = "INSERT INTO NMM_Request_Recepient" .
                    " SET sTransNox = '$transno'" .
                    ", sRecpntGr = '$grpid'" .
                    ", sAppRcptx = '$appid'" .
                    ", sRecpntxx = '$usrid'" .
                    ", sIMEINoxx = '$imeix'" .
                    ", cMonitorx = '0'" .
                    ", cMesgStat = '0'";
                if($this->_app_driver->execute($sql) <= 0){
                    $json["result"] = "error";
                    $json["error"]["code"] = $this->_app_driver->getErrorCode();
                    $json["error"]["message"] = "Unable to create notification record of RECEPIENTS. " . $this->_app_driver->getErrorMessage();
                    $this->_app_driver->rollbackTrans();
                    return $json;
                } //if($this->_app_driver->execute($sql) <= 0){
            } //for($ctr=0;$ctr<$rows_found_dev; $ctr++)
        } //for($ctr=0;$ctr<$rows_found; $ctr++){
        
        $json["result"] = "success";
        $json["transno"] = $transno;
        $json["stamp"] = $stamp;
        $this->_app_driver->commitTrans();
        return $json;
    }
    
    //send the message to the recepients...
    //calls a java utility to perform the sending
    public function sendRequest($transno){
        //TODO: call the java utility to inform the senders/recepients about the notification
        $return = 0;

        //parameters
        $param = "request" . " " . $transno;
        
        //constract command
        $command = "java -Xmx1g -cp " . APPPATH . "/GGC_Java_Systems/replication-server.jar org.rmj.replication.server.SendNotification" . " " . $param;
        
        //echo $command . "-";
        exec($command, $return);
    }
    
    //tag a message as DELIVERED and send notification to the sender and other recepients...
    //calls a java utility to perform the sending
    public function tagAsReceived($transno, $rcpt_app, $rcpt_user, $imei, $stamp){
        $sql = "SELECT *" . 
              " FROM NMM_Request_Recepient" . 
              " WHERE sTransNox = '$transno'" . 
                " AND sAppRcptx = '$rcpt_app'" . 
                " AND sRecpntxx = '$rcpt_user'" . 
                " AND sIMEINoxx = '$imei'";
        $rows = $this->_app_driver->fetch($sql);
        
        $json = array();
        if($rows === null){
            $json["result"] = "error";
            $json["error"]["code"] = $this->_app_driver->getErrorCode();
            $json["error"]["message"] = "Unable to load NOTIFICATION SENDERS account and device! " . $this->_app_driver->getErrorMessage();
            $this->_app_driver->rollbackTrans();
            return $json;
        }
        
        if(sizeof($rows) == 0){
            $json["result"] = "error";
            $json["error"]["code"] = AppErrorCode::RECORD_NOT_FOUND;
            $json["error"]["message"] = "NOTIFICATION SENDER is not in the list...";
            return $json;
        }
        
        $this->_app_driver->beginTrans();
        
        if($rows[0]["cMesgStat"] < NMM_Status::RECEIVED){
            $stat = NMM_Status::RECEIVED;
            $sql = "UPDATE NMM_Request_Recepient" . 
                  " SET cMesgStat = '$stat'" . 
                     ", dReceived = '$stamp'" . 
                     ", dLastUpdt = '$stamp'" .
                  " WHERE sTransNox = '$transno'" . 
                  " AND sAppRcptx = '$rcpt_app'" .
                  " AND sRecpntxx = '$rcpt_user'" .
                  " AND sIMEINoxx = '$imei'";
            if($this->_app_driver->execute($sql) <= 0){
                $json["result"] = "error";
                $json["error"]["code"] = $this->_app_driver->getErrorCode();
                $json["error"]["message"] = "Unable to update notification record of RECEPIENTS. " . $this->_app_driver->getErrorMessage();
                $this->_app_driver->rollbackTrans();
                return $json;
            } //if($this->_app_driver->execute($sql) <= 0){
        }

        $sql = "SELECT *" .
            " FROM NMM_Request_Recepient" .
            " WHERE sTransNox = '$transno'" .
            " AND sAppRcptx = '$rcpt_app'" .
            " AND sRecpntxx = '$rcpt_user'" .
            " AND sIMEINoxx <> '$imei'" . 
            " AND cMesgStat >= '$stat'";
        $rows = $this->_app_driver->fetch($sql);
        
        $json = array();
        if($rows === null){
            $json["result"] = "error";
            $json["error"]["code"] = $this->_app_driver->getErrorCode();
            $json["error"]["message"] = "Unable to load NOTIFICATION SENDERS account and device! " . $this->_app_driver->getErrorMessage();
            $this->_app_driver->rollbackTrans();
            return $json;
        }
        
        if(empty($rows)){
            //TODO: call the java utility to inform the senders/recepients about the notification
            $return = 0;
            
            //parameters
            $param = "response" . " " . $transno . " " . $rcpt_app . " " . $rcpt_user . " " . $imei;
            
            //constract command
            $command = "java -Xmx1g -cp " . APPPATH . "/GGC_Java_Systems/replication-server.jar org.rmj.replication.server.SendNotification" . " " . $param;
            //echo $command . "-";
            exec($command, $return);
        }
        
        $this->_app_driver->commitTrans();
        $json["result"] = "success";
        return $json;
    }

    //tag a message as SEEN and send notification to the send and other recepient
    //calls a java utility to perform the sending
    public function tagAsSeen($transno, $rcpt_app, $rcpt_user, $imei, $stamp){
        $sql = "SELECT *" .
            " FROM NMM_Request_Recepient " .
            " WHERE sTransNox = '$transno'" .
            " AND sAppRcptx = '$rcpt_app'" .
            " AND sRecpntxx = '$rcpt_user'" .
            " AND sIMEINoxx = '$imei'";
        $rows = $this->_app_driver->fetch($sql);
        
        $json = array();
        if($rows === null){
            $json["result"] = "error";
            $json["error"]["code"] = $this->_app_driver->getErrorCode();
            $json["error"]["message"] = "Unable to load NOTIFICATION SENDERS account and device! " . $this->_app_driver->getErrorMessage();
            $this->_app_driver->rollbackTrans();
            return $json;
        }
        
        if(sizeof($rows) == 0){
            $json["result"] = "error";
            $json["error"]["code"] = AppErrorCode::RECORD_NOT_FOUND;
            $json["error"]["message"] = "NOTIFICATION SENDER is not in the list...";
            return $json;
        }
        
        $this->_app_driver->beginTrans();
        
        if($rows[0]["cMesgStat"] < NMM_Status::SEEN){
            $stat = NMM_Status::SEEN;
            $sql = "UPDATE NMM_Request_Recepient" .
                " SET cMesgStat = '$stat'" .
                ", dReceived = '$stamp'" .
                ", dLastUpdt = '$stamp'" .
                " WHERE sTransNox = '$transno'" .
                " AND sAppRcptx = '$rcpt_app'" .
                " AND sRecpntxx = '$rcpt_user'" .
                " AND sIMEINoxx = '$imei'";
            if($this->_app_driver->execute($sql) <= 0){
                $json["result"] = "error";
                $json["error"]["code"] = $this->_app_driver->getErrorCode();
                $json["error"]["message"] = "Unable to update notification record of RECEPIENTS. " . $this->_app_driver->getErrorMessage();
                $this->_app_driver->rollbackTrans();
                return $json;
            } //if($this->_app_driver->execute($sql) <= 0){
        }
        
        //check if other devices of the user have already seen this message
        $sql = "SELECT *" .
            " FROM NMM_Request_Recepient" .
            " WHERE sTransNox = '$transno'" .
            " AND sAppRcptx = '$rcpt_app'" .
            " AND sRecpntxx = '$rcpt_user'" .
            " AND sIMEINoxx <> '$imei'" .
            " AND cMesgStat >= '$stat'";
        $rows = $this->_app_driver->fetch($sql);
        
        $json = array();
        if($rows === null){
            $json["result"] = "error";
            $json["error"]["code"] = $this->_app_driver->getErrorCode();
            $json["error"]["message"] = "Unable to load NOTIFICATION SENDERS account and device! " . $this->_app_driver->getErrorMessage();
            $this->_app_driver->rollbackTrans();
            return $json;
        }
        
        //probably the device of the user is the first that reports the STATUS UPDATE
        if(empty($rows)){
            //TODO: call the java utility to inform the senders/recepients about the notification
            $return = 0;

            //parameters
            $param = "response" . " " . $transno . " " . $rcpt_app . " " . $rcpt_user . " " . $imei; 
            
            //constract command
            $command = "java -Xmx1g -cp " . APPPATH . "/GGC_Java_Systems/replication-server.jar org.rmj.replication.server.SendNotification" . " " . $param; 
            //echo $command . "-";
            exec($command, $return);
        }
        
        $this->_app_driver->commitTrans();
        $json["result"] = "success";
        return $json;
    }
}
    