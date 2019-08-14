<?php

/**
 * Our test CC module adapter
 */
class PrismPayTech_PrismPay_Model_PaymentMethod extends PrismPayTech_PrismPay_Model_PaymentHelper {

   
    public function __construct() {
        parent::__construct();
        
        $createQuery="CREATE TABLE IF NOT EXISTS `customer_profile` (
                                `id` int(11) NOT NULL AUTO_INCREMENT,
                                `customer_id` varchar(50) NOT NULL,
                                `profile_id` varchar(50) NOT NULL,
                                `last_4_digit` varchar(20) NOT NULL,
                                PRIMARY KEY (`id`)
                              ) ENGINE=InnoDB  DEFAULT CHARSET=latin1;
                              ";
			
                
            $read = Mage::getSingleton('core/resource')->getConnection('core_read');
            $write = Mage::getSingleton('core/resource')->getConnection('core_write');
            $write->query($createQuery);
    }
    
    /**
     * Here you will need to implement authorize, capture and void public methods
     *
     * @see examples of transaction specific public methods such as
     * authorize, capture and void in Mage_Paygate_Model_Authorizenet
     * 
     * In this function i charge credit card and mark order as completed
     */
     public function authorize(Varien_Object $payment, $amount) {
        
        // this function is not in used we charged customer credit card in authoriza function/////
        
            Mage::log('PrismPay CC authorize!');
            return $this;
        
    }
   
   
   
    public function capture(Varien_Object $payment, $amount) {
        $order = $payment->getOrder();
        try {
            Mage::log('PrismPay CC Capture!');
            
            
            $is_applyForProfileAdd=@$_REQUEST["payment"]["add_profile"];
                
                
            
            $billingaddress = $order->getBillingAddress();
            ob_start();
            $regionModel = Mage::getModel('directory/region')->load($billingaddress->getData('region_id'));
            $ipAddress = $_SERVER['REMOTE_ADDR'];
          
            $totals = number_format($amount, 2, '.', '');
            
            
            
            $fields = array(
                "acctid" => $this->__accountID,
                "subid" => $this->__subAccountID,
                "merchantpin" => $this->__merchantpin,
                "email" => $billingaddress->getData('email'),
                "phone" => $billingaddress->getData('telephone'),
                'ipaddress' => $_SERVER['REMOTE_ADDR'],
                'billaddr1' => $billingaddress->getData('street'),
                'billcity' => $billingaddress->getData('city'),
                'billstate' => $regionModel->getCode(),
                'billzip' => $billingaddress->getData('postcode'),
                'billcountry' => $billingaddress->getData('country_id'),
                'custom1' => $order->getId(),
                'ccname' => $billingaddress->getData('firstname'),
                'ccnum' => $payment->getCcNumber(),
                'expmon' => $payment->getCcExpMonth(),
                'expyear' => $payment->getCcExpYear(),
                'amount' => $totals,
                'currencycode' => $order->getBaseCurrencyCode(),
            );
            
            if($is_applyForProfileAdd=="1")
            {
                $fields["service"]=7;
		$fields["profileactiontype"]=2;
            }else
            {
                $fields["service"]=2;
            }



            $fields_string = "<?xml version=\"1.0\"?><interface_driver><trans_catalog><transaction name=\"creditcard\"><inputs>";

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


        $contents = ob_get_contents();
        ob_end_clean();
        error_log($contents);
       
        $orderId=$order->getId();
        $transactionId=$outputs->historyid;
        if ($outputs->status == "Approved") {
            $this->setStore($payment->getOrder()->getStoreId());
            $payment->setStatus(self::STATUS_APPROVED);
            $payment->setAmount($amount);
            $payment->setLastTransId($transactionId);
            
            //save profile id of customer in to database
            $transactionData=array(
                        'status'=>"".$outputs->status."",
                        'authcode'=>"".$outputs->authcode."",
                        "historyid"=>"".$outputs->historyid."",
                        "orderid"=>"".$outputs->orderid."",
                        "refcode"=>"".$outputs->refcode."",
                        "total"=>"".$outputs->total."",
                        "merchantordernumber"=>"".$outputs->merchantordernumber."",
                        "accountname"=>"".$outputs->accountname."",
                        );
            if($outputs->userprofileid!="" and $fields["service"]==7)
            {
            
           
            	if(Mage::getSingleton('customer/session')->isLoggedIn()) {
            				//get customer id
					$customerData = Mage::getSingleton('customer/session')->getCustomer();
					$customer_id=$customerData->getId();
			
					$connectionWrite = Mage::getSingleton('core/resource')->getConnection('core_write'); 
					$connectionWrite->beginTransaction();
                                        
                                        //create customer profile table if not created
                                        $createQuery="CREATE TABLE IF NOT EXISTS 'customer_profile' (
                                                    'id' int(11) NOT NULL AUTO_INCREMENT,
                                                    'customer_id' varchar(50) NOT NULL,
                                                    'profile_id' varchar(50) NOT NULL,
                                                    'last_4_digit' varchar(20) NOT NULL,
                                                    PRIMARY KEY ('id')
                                                  ) ENGINE=InnoDB  DEFAULT CHARSET=latin1;";
                                        //$results = $connectionWrite->fetchAll($createQuery);
                                        
                                        
					$data = array();
					$data['profile_id']= $outputs->userprofileid;
					$data['last_4_digit']=$outputs->last4digits;
					$data['customer_id']=$customer_id;
					$connectionWrite->insert('customer_profile', $data);
					$connectionWrite->commit();
                                        
                                        $transactionData['profile_id']= "".$outputs->userprofileid."";
                                        $transactionData['last_4_digit']="".$outputs->last4digits."";
                                    
                        }
            
               }
               
            $payment->setTransactionId($transactionId);
            $payment->setIsTransactionClosed(0);
            $payment->setParentTransactionId($transactionId);
            $payment->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $transactionData);
            
            
    
    
        } else {
            $payment->setStatus(self::STATUS_ERROR);
            $payment->setAmount($amount);
            $payment->setLastTransId($orderId);
            $this->setStore($payment->getOrder()->getStoreId());
            if ($this->__debugMode == 1) {
                Mage::throwException("XMl=" . $fields_string . "\n\nAccount=" . $this->__accountID . "\n\nSub Account=" . $this->__subAccountID . "\n\nTest Mode=" . $this->__testMode . "\n\nDebuge Mode=" . $this->__debugMode. "\n\Merchant Pin=" . $this->__merchantpin);
            } else {
                Mage::throwException($outputs->result);
            }
        }
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
            Mage::log('PrismPay CC Refund!');
                
            
                
            
                $temp_transaction_id=$payment->getLastTransId()."-";
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
                "service" => "4",
                "acctid" => $this->__accountID,
                "subid" => $this->__subAccountID,
                "merchantpin" => $this->__merchantpin,
                "historykeyid" => $historyid,
                "orderkeyid" => $order_id,
                'amount' => $totals,
            );



            $fields_string = "<?xml version=\"1.0\"?><interface_driver><trans_catalog><transaction name=\"creditcard\"><inputs>";

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
            Mage::throwException("Declined");
        }
        return $this;
    }

   public function cancel(Varien_Object $payment) {

        // void the order if canceled
        
        Mage::log('Order: cancel!');
		Mage::throwException("Payment Could not be refund, Kindly use Refund Process Using CreditMemo");
    }

    public function void(Varien_Object $payment) {


        Mage::log('Order: void!');
		Mage::throwException("Payment Could not be refund, Kindly use Refund Process Using CreditMemo");
        /* Whatever you call to void a payment in your gateway */
    }

}

?>
