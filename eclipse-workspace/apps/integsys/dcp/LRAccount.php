<?php 
require_once 'config.php';
require_once 'Nautilus.php';
require_once 'CommonUtil.php';

class LRAccount{
    private $_app;
    
    private $_acctstat;
    private $_message;
    
    public function __construct($app, $acctstat){
        $this->_app = $app;
        $this->acctstat = $acctstat;
        date_default_timezone_set('Asia/Singapore');
    }
    
    function __destruct() {
        $this->_app = null;
    }
    
    public function OpenAccount($acctnmbr){
        if ($this->_app == null){
            $this->_message = "Application driver  was not set.";
            return false;
        }
        
        $condition = "a.cAcctStat = " . CommonUtil::toSQL($this->acctstat) .
                        " AND a.sAcctNmbr = " . CommonUtil::toSQL($acctnmbr);
        
        $sql = CommonUtil::addcondition($this::getSQL_Account(), $condition);
       
        if(null === $rows = $this->_app->fetch($sql)){
            $this->_message = $this->_app->getErrorMessage();
            return false;
        } elseif(empty($rows)){W
            $this->_message = "No record found.";
            return false;
        }
        
        
    }
    
    private function getSQL_Account(){
        $sql = "SELECT" .
                    "  a.sAcctNmbr" .
                    ", a.sApplicNo" .
                    ", CONCAT(b.sLastName, ', ', b.sFrstName, IF(IFNull(b.sSuffixNm, '') = '', ' ', CONCAT(' ', b.sSuffixNm, ' ')), b.sMiddName) xFullName" .
                    ", CONCAT(b.sAddressx, ', ', c.sTownName, ', ', d.sProvName, ' ', c.sZippCode) xAddressx" .
                    ", a.sRemarksx" .
                    ", CONCAT(g.sBrandNme, ' ', f.sModelNme) as xModelNme" .
                    ", e.sEngineNo" .
                    ", e.sFrameNox" .
                    ", h.sColorNme" .
                    ", CONCAT(b.sLastName, ', ', b.sFrstName, ' ', b.sMiddName) xCCounNme" .
                    ", j.sRouteNme" .
                    ", CONCAT(p.sLastName, ', ', p.sFrstName, ' ', p.sMiddName) xCollectr" .
                    ", CONCAT(q.sLastName, ', ', q.sFrstName, ' ', q.sMiddName) xManagerx" .
                    ", m.sBranchNm xCBranchx" .
                    ", a.dPurchase" .
                    ", a.dFirstPay" .
                    ", a.nAcctTerm" .
                    ", a.dDueDatex" .
                    ", a.nGrossPrc" .
                    ", a.nDownPaym" .
                    ", a.nCashBalx" .
                    ", a.nPNValuex" .
                    ", a.nMonAmort" .
                    ", a.nPenaltyx" .
                    ", a.nRebatesx" .
                    ", a.nLastPaym" .
                    ", a.dLastPaym" .
                    ", a.nPaymTotl" .
                    ", a.nRebTotlx" .
                    ", a.nDebtTotl" .
                    ", a.nCredTotl" .
                    ", a.nAmtDuexx" .
                    ", a.nABalance" .
                    ", a.nDownTotl" .
                    ", a.nCashTotl" .
                    ", a.nDelayAvg" .
                    ", a.cRatingxx" .
                    ", a.cAcctstat" .
                    ", a.sClientID" .
                    ", a.sExAcctNo" .
                    ", a.sSerialID" .
                    ", a.cMotorNew" .
                    ", a.dClosedxx" .
                    ", a.cActivexx" .
                    ", a.nLedgerNo" .
                    ", a.cLoanType" .
                    ", b.sTownIDxx" .
                    ", a.sRouteIDx" .
                    ", a.nPenTotlx" .
                    ", i.sTransNox" .
                    ", a.sModified" .
                    ", a.dModified" .
                    ", CONCAT(n.sLastName, ', ', n.sFrstName, IF(IFNull(n.sSuffixNm, '') = '', ' ', CONCAT(' ', n.sSuffixNm, ' ')), n.sMiddName) xCoCltNm1" .
                    ", CONCAT(o.sLastName, ', ', o.sFrstName, IF(IFNull(o.sSuffixNm, '') = '', ' ', CONCAT(' ', o.sSuffixNm, ' ')), o.sMiddName) xCoCltNm2" .
                    ", a.sCoCltID1" .
                    ", a.sCoCltID2" .
                    ", CONCAT(r.sLastName, ', ', r.sFrstName, ' ', r.sMiddName) zCollectr" .
                    ", CONCAT(s.sLastName, ', ', s.sFrstName, ' ', s.sMiddName) zManagerx" .
                    ", t.nLatitude" .
                    ", t.nLongitud" .
                    ", b.sBrgyIDxx" .
                " FROM MC_AR_Master  a" .
                        " LEFT JOIN MC_Serial e" .
                        " LEFT JOIN MC_Model f" .
                        " LEFT JOIN Brand g ON f.sBrandIDx = g.sBrandIDx" .
                            " ON e.sModelIDx = f.sModelIDx" .
                        " LEFT JOIN Color h ON e.sColorIDx = h.sColorIDx" .
                            " ON a.sSerialID = e.sSerialID" .
                        " LEFT JOIN MC_Credit_Application i ON a.sApplicNo = i.sTransNox" .
                        " LEFT JOIN Client_Master n ON a.sCoCltID1 = n.sClientID" .
                        " LEFT JOIN Client_Master o ON a.sCoCltID2 = o.sClientID" .
                        " LEFT JOIN Client_Coordinates t ON a.sClientID = t.sClientID" .
                        " LEFT JOIN Route_Area j" .
                        " LEFT JOIN Employee_Master001 k" .
                            " LEFT JOIN Client_Master p" .
                                    " ON k.sEmployID = p.sClientID" .
                            " ON j.sCollctID = k.sEmployID" .
                        " LEFT JOIN Employee_Master001 l" .
                            " LEFT JOIN Client_Master q" .
                                " ON l.sEmployID = q.sClientID" .
                            " ON j.sManagrID = l.sEmployID" .
                        " LEFT JOIN Branch m ON j.sBranchCd = m.sBranchCd" .
                        " LEFT JOIN Employee_Master r ON j.sCollctID = r.sEmployID" .
                            " LEFT JOIN Employee_Master s" .
                                " ON j.sManagrID = s.sEmployID" .
                            " ON a.sRouteIDx = j.sRouteIDx" .
                    ", Client_Master b" .
                    ", TownCity c" .
                    ", Province d" .
                " WHERE a.sClientID = b.sClientID" .
                    " AND b.sTownIDxx = c.sTownIDxx" .
                    " AND c.sProvIDxx = d.sProvIDxx" .
                    " AND a.cLoanType <> '4'" .
                    " AND a.cAcctStat = '0'";
        
    }
}
?>