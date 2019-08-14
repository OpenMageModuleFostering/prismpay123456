<?php

/**
 * Our test CC module adapter
 */
class PrismPayTech_PrismAch_Model_PaymentMethod extends PrismPayTech_PrismPay_Model_PaymentHelper {

   protected $_code = 'prismach';
   protected $_formBlockType = 'prismach/form';
   protected $_infoBlockType = 'prismach/info';
    
    public function __construct() {
        parent::__construct();
        Mage::log("PrismPayTech_PrismAch_Model_PaymentMethod");

    }
    
    /**
     * Here you will need to implement authorize, capture and void public methods
     *
     * @see examples of transaction specific public methods such as
     * authorize, capture and void in Mage_Paygate_Model_Authorizenet
     * 
     * In this function i charge credit card and mark order as completed
     */
    public function assignData($data)
        {
        
            // Call parent assignData
            parent::assignData($data);

            // Get Mage_Payment_Model_Info instance from quote 
            $info = $this->getInfoInstance();

            // Add some arbitrary post data to the Mage_Payment_Model_Info instance 
            // so it is saved in the DB in the 'additional_information' field        
            $info->setAdditionalInformation(
                'name', 
                "faisalsharifshamsi");
               Mage::log("Payment Method assignData "); 
            return $this;
        }
    public function authorize(Varien_Object $payment, $amount) {
        $order = $payment->getOrder();
        try {
            Mage::log('PrismAch Authorize!');
            
            $ckname=$_REQUEST["payment"]["ckname"];
            $ckaba=$_REQUEST["payment"]["ckaba"];
            $ckacct=$_REQUEST["payment"]["ckacct"];
            $ckno =$_REQUEST["payment"]["ckno"];
            $ckaccttype =$_REQUEST["payment"]["ckaccttype"];
            $ckaccttypedesc =$_REQUEST["payment"]["ckaccttypedesc"];
            $cktype =$_REQUEST["payment"]["cktype"];
            
            $ipAddress = $_SERVER['REMOTE_ADDR'];
          
            $totals = number_format($amount, 2, '.', '');
            $fields = array(
                "service" => "14",
                "acctid" => $this->__accountID,
                "subid" => $this->__subAccountID,
                "merchantpin" => $this->__merchantpin,
                "ckname" => $ckname,
                "ckaba" => $ckaba,
                "ckacct" => $ckacct,
                "ckno" => $ckno,
               // "ckaccttype" => $ckaccttype,
                "ckaccttype" => $ckaccttype,
                "ckaccttypedesc" => $ckaccttypedesc,
                "cktype" => $cktype,
                'ipaddress' => $_SERVER['REMOTE_ADDR'],
                'currencycode' => $order->getBaseCurrencyCode(),
                'amount' => $totals,
            );



            $fields_string = "<?xml version=\"1.0\"?><interface_driver><trans_catalog><transaction name=\"ach\"><inputs>";

            foreach ($fields as $key => $value) {
                $fields_string .= '<' . $key . '>' . $value . '</' . $key . '>';
            }
            $fields_string .='</inputs></transaction></trans_catalog></interface_driver>';

            if ($this->__debugMode == 0) {
                   $data=$this->curlHelper($payment, $fields_string); 
               
            }
        } catch (Exception $e) {

            $payment->setStatus(self::STATUS_ERROR);
            $payment->setAmount($amount);
            $payment->setLastTransId($orderId);
            $this->setStore($payment->getOrder()->getStoreId());
            Mage::throwException($e->getMessage());
        }
        if ($this->__debugMode == 0) {
            $xmlResponse = new SimpleXmlElement($data); //Simple way to parse xml, Magento might have an equivalent class
            $outputs = $xmlResponse->trans_catalog->transaction->outputs;
        }

        $orderId=$order->getId();
        $transactionId=$outputs->historyid;
        if ($outputs->status == "Approved") {
            $this->setStore($payment->getOrder()->getStoreId());
            $payment->setStatus(self::STATUS_APPROVED);
            $payment->setAmount($amount);
            $payment->setLastTransId($orderId);
            
            $payment->setTransactionId($transactionId);
            $payment->setIsTransactionClosed(0);
            $payment->setParentTransactionId($transactionId);
            $payment->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, 
                    array(
                        'status'=>"".$outputs->status."",
                        'authcode'=>"".$outputs->authcode."",
                        "historyid"=>"".$outputs->historyid."",
                        "orderid"=>"".$outputs->orderid."",
                        "refcode"=>"".$outputs->refcode."",
                        "total"=>"".$outputs->total."",
                        "merchantordernumber"=>"".$outputs->merchantordernumber."",
                        "accountname"=>"".$outputs->accountname."",
                        "ckname"=>"".$ckname."",
                        "ckaba"=>"".$ckaba."",
                        "ckacct"=>"".$ckacct."",
                        "ckno"=>"".$ckno."",
                        "ckaccttype"=>"".$ckaccttype."",
                        "ckaccttypedesc"=>"".$ckaccttypedesc."",
                        "cktype"=>"".$cktype."",
                        )
                    );
    
    
        } else {
            $payment->setStatus(self::STATUS_ERROR);
            $payment->setAmount($amount);
            $payment->setLastTransId($orderId);
            $this->setStore($payment->getOrder()->getStoreId());
            if ($this->__debugMode == 1) {
                Mage::throwException("XMl=" . $fields_string . "\n\nAccount=" . $this->__accountID . "\n\nSub Account=" . $this->__subAccountID . "\n\nTest Mode=" . $this->__testMode . "\n\nDebuge Mode=" . $this->__debugMode);
            } else {
                Mage::throwException($outputs->result);
            }
        }
        return $this;
    }
   
    public function capture(Varien_Object $payment, $amount) {
        
        // this function is not in used /////
        
        
    }
    public function validate()
    {
        /*
        * calling parent validate function
        */
        //parent::validate();
        Mage::log("Prism Profile Validate");

        return $this;
    }
   
    /**
     * Payment refund
     *
     * @param \Magento\Framework\Object $payment
     * @param float $amount
     * @return $this
     * @throws \Magento\Framework\Model\Exception
     */
    
    public function refund(Varien_Object $payment, $amount) {
        $order = $payment->getOrder();
        try {
            Mage::log('PrismPay Profile Refund!');
            
            
                $temp_transaction_id=$payment->getLastTransId();
                $dash_pos = strpos($temp_transaction_id, "-");
                $transaction_id=substr($temp_transaction_id,0,$dash_pos);
                if($transaction_id=="")
                {
                    Mage::throwException("Unable to create memo , transaction id not found");
                    return $this;
                    
                }
                $transactionData=$payment->getTransaction($transaction_id)->getData();
                $order_id=@$transactionData["additional_information"]["raw_details_info"]["orderid"];
                $historyid=@$transactionData["additional_information"]["raw_details_info"]["historyid"];
                $refcode=@$transactionData["additional_information"]["raw_details_info"]["refcode"];
      
        
            $totals = number_format($amount, 2, '.', '');
            $fields = array(
                "service" => "16",
                "acctid" => $this->__accountID,
                "subid" => $this->__subAccountID,
                "merchantpin" => $this->__merchantpin,
                "historykeyid" => $historyid,
                "orderkeyid" => $order_id,
                'amount' => $totals,
            );



            $fields_string = "<?xml version=\"1.0\"?><interface_driver><trans_catalog><transaction name=\"ach\"><inputs>";

            foreach ($fields as $key => $value) {
                $fields_string .= '<' . $key . '>' . $value . '</' . $key . '>';
            }
            $fields_string .='</inputs></transaction></trans_catalog></interface_driver>';
            
            Mage::log('Data '.$fields_string);
            
            if ($this->__debugMode == 0) {
                   $data=$this->curlHelper($payment, $fields_string); 
               
            }
        } catch (Exception $e) {

            $payment->setStatus(self::STATUS_ERROR);
            $payment->setAmount($amount);
            $payment->setLastTransId($orderId);
            $this->setStore($payment->getOrder()->getStoreId());
            Mage::throwException($e->getMessage());
        }
            $xmlResponse = new SimpleXmlElement($data); //Simple way to parse xml, Magento might have an equivalent class
            $outputs = $xmlResponse->trans_catalog->transaction->outputs;


        //error_log($contents);
       
        $transactionId=$outputs->historyid;
        if ($outputs->status == "Approved") {
                   Mage::log('Refund Request Approved');
         
            $payment->setTransactionId($transactionId."-refund");
            $payment->setIsTransactionClosed(1);
            $payment->setParentTransactionId($transaction_id);
            $payment->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, 
                    array(
                        'status'=>"".$outputs->status."",
                        'authcode'=>"".$outputs->authcode."",
                        "historyid"=>"".$outputs->historyid."",
                        "orderid"=>"".$outputs->orderid."",
                        "refcode"=>"".$outputs->refcode."",
                        "total"=>"".$outputs->total."",
                        "merchantordernumber"=>"".$outputs->merchantordernumber."",
                        "accountname"=>"".$outputs->accountname."",
                        )
                    );
    
    
        } else {
                               Mage::log('Refund Request Decline');

            $payment->setTransactionId($transactionId."-refund");
            $payment->setIsTransactionClosed(1);
            $payment->setParentTransactionId($transactionId);
            $payment->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, 
                    array(
                        'status'=>"".$outputs->status."",
                        'authcode'=>"".$outputs->authcode."",
                        "historyid"=>"".$outputs->historyid."",
                        "orderid"=>"".$outputs->orderid."",
                        "refcode"=>"".$outputs->refcode."",
                        "total"=>"".$outputs->total."",
                        "merchantordernumber"=>"".$outputs->merchantordernumber."",
                        "accountname"=>"".$outputs->accountname."",
                        )
                    );
            Mage::throwException($outputs->result);
        }
        return $this;
    }

    public function cancel(Varien_Object $payment) {

        // void the order if canceled
        Mage::log('Order: cancel!');

        return $this;
    }

    public function void(Varien_Object $payment) {


        Mage::log('Order: void!');

        /* Whatever you call to void a payment in your gateway */
    }

}

?>
