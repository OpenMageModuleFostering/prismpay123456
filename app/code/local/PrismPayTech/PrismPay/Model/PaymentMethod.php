<?php

/**
* Our test CC module adapter
*/
class PrismPayTech_PrismPay_Model_PaymentMethod extends Mage_Payment_Model_Method_Cc
{
    /**
    * unique internal payment method identifier
    *
    * @var string [a-z0-9_]
    */
    protected $_code = 'prismpay';

       /**
     * Here are examples of flags that will determine functionality availability
     * of this module to be used by frontend and backend.
     *
     * @see all flags and their defaults in Mage_Payment_Model_Method_Abstract
     *
     * It is possible to have a custom dynamic logic by overloading
     * public function can* for each flag respectively
     */

    /**
     * Is this payment method a gateway (online auth/charge) ?
     */
    protected $_isGateway               = true;

    /**
     * Can authorize online?
     */
    protected $_canAuthorize            = true;

    /**
     * Can capture funds online?
     */
    protected $_canCapture              = true;

    /**
     * Can capture partial amounts online?
     */
    protected $_canCapturePartial       = false;

    /**
     * Can refund online?
     */
    protected $_canRefund               = false;

    /**
     * Can void transactions online?
     */
    protected $_canVoid                 = true;

    /**
     * Can use this payment method in administration panel?
     */
    protected $_canUseInternal          = true;

    /**
     * Can show this payment method as an option on checkout payment page?
     */
    protected $_canUseCheckout          = true;

    /**
     * Is this payment method suitable for multi-shipping checkout?
     */
    protected $_canUseForMultishipping  = true;

    /**
     * Can save credit card information for future processing?
     */
    protected $_canSaveCc = false;

    /**
     * Here you will need to implement authorize, capture and void public methods
     *
     * @see examples of transaction specific public methods such as
     * authorize, capture and void in Mage_Paygate_Model_Authorizenet
     */


	public function authorize(Varien_Object $payment, $amount)
    {
    	$order = $payment->getOrder();
		try {
		
		
			//config variables 
			
			$config_debug = Mage::getStoreConfig('payment/prismpay/debug', $order->getStoreId());
        	$config_test = Mage::getStoreConfig('payment/prismpay/test', $order->getStoreId());
        	
        	$account_id="";
        	$sub_account_id="";
        	
        	if($config_test==1)
        	{
	        	$account_id="py7l4";
	        	$sub_account_id="";
	        	
        	}else
        	{
        		$config_account_id = Mage::getStoreConfig('payment/prismpay/account_id', $order->getStoreId());
      		  	$config_sub_account_id = Mage::getStoreConfig('payment/prismpay/sub_account_id', $order->getStoreId());
        		
        		$account_id=$config_account_id;
            	$sub_account_id=$config_sub_account_id;
	    	}
        	
        	
        	
        	
        	
    		$billingaddress = $order->getBillingAddress();
			ob_start();
			$regionModel = Mage::getModel('directory/region')->load($billingaddress->getData('region_id'));
			$dob = Mage::getModel('customer/customer')->load($order->getCustomerId())->getDob();
			$devMode = false;
			$ipAddress = $_SERVER['REMOTE_ADDR'];
			if($devMode){
				$ipAddress = "192.168.1.1";
			}
			$dobStr = "";
			if(!isset($dob)){
				$dob = $order->getCustomerDob();
			}
			if(isset($dob)){
				$dobStr = str_replace("00:00:00", "", $dob);
				$dobStr = str_replace("-", "", $dobStr);
				$dobStr = trim($dobStr);
			}

			$totals = number_format($amount, 2, '.', '');
			$fields = array(
                "service"		=> "2",
                "acctid"		=> $account_id,
                "subid"			=> $sub_account_id,
                "email"			=> $billingaddress->getData('email'),
                "phone"			=> $billingaddress->getData('telephone'),
                'ipaddress'		=> $_SERVER['REMOTE_ADDR'],
                'billaddr1'		=> $billingaddress->getData('street'),
                'billcity'		=> $billingaddress->getData('city'),
                'billstate'		=> $regionModel->getCode(),
                'billzip'		=> $billingaddress->getData('postcode'),
                'billcountry'	=> $billingaddress->getData('country_id'),
                'custom1'		=> $order->getId(),
                'ccname'		=> $billingaddress->getData('firstname'),
                'ccnum'			=> $payment->getCcNumber(),
                'expmon'		=> $payment->getCcExpMonth(),
                'expyear'		=> $payment->getCcExpYear(),
                'amount'		=> $totals,
                'currencycode'	=> $order->getBaseCurrencyCode(),
			);
			
			
			
			$fields_string = "<?xml version=\"1.0\"?><interface_driver><trans_catalog><transaction name=\"creditcard\"><inputs>";

			foreach($fields as $key=>$value) {
				$fields_string .= '<'.$key.'>'.$value.'</'.$key.'>';
			}
			$fields_string .='</inputs></transaction></trans_catalog></interface_driver>';
			
			if($config_debug==0)
			{
			
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, 'https://trans.myprismpay.com/cgi-bin/ProcessXML.cgi');
				curl_setopt($ch, CURLOPT_HEADER, false);
				curl_setopt($ch, CURLINFO_HEADER_OUT, false);
				curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: application/xml; charset=utf-8"));
				//curl_setopt($ch, CURLOPT_USERPWD, 'myusername:mypassword');
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
				curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
				curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
	
				$data = curl_exec($ch); //This value is the string returned from the bank...

				if (!$data) {
					throw new Exception(curl_error($ch));
				}
				$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
				if ($httpcode && substr($httpcode, 0, 2) != "20") { //Unsuccessful post request...
					throw new Exception("Returned HTTP CODE: " . $httpcode . " for this URL: " . $urlToPost);
				}
				curl_close($ch);
            }
            
            
        } catch (Exception $e) {
            $payment->setStatus(self::STATUS_ERROR);
            $payment->setAmount($amount);
            $payment->setLastTransId($orderId);
            $this->setStore($payment->getOrder()->getStoreId());
            Mage::throwException($e->getMessage());
           // Mage::throwException($fields_string);
           
           if($config_debug==1)
			{
					
					Mage::throwException($fields_string);
					Mage::throwException($account_id);
           
			
			}
        }
        	if($config_debug==0)
			{
        		$xmlResponse = new SimpleXmlElement($data); //Simple way to parse xml, Magento might have an equivalent class
        		$outputs=$xmlResponse->trans_catalog->transaction->outputs;
        	
        	}
        	
        	
			$contents = ob_get_contents();
			ob_end_clean();
			error_log($contents);
            $isPaymentAccepted = $outputs->status == "Approved";
        if ($isPaymentAccepted) {
            $this->setStore($payment->getOrder()->getStoreId());
            $payment->setStatus(self::STATUS_APPROVED);
            $payment->setAmount($amount);
            $payment->setLastTransId($orderId);
        } else {
            $payment->setStatus(self::STATUS_ERROR);
            $payment->setAmount($amount);
            $payment->setLastTransId($orderId);
            $this->setStore($payment->getOrder()->getStoreId());
            if($config_debug==1)
			{
					 Mage::throwException("XMl=".$fields_string."\n\nAccount=".$account_id."\n\nSub Account=".$sub_account_id."\n\nTest Mode=".$config_test."\n\nDebuge Mode=".$config_debug);
			}else
			{
           	 Mage::throwException("Payment Declined");
           	}
            
        }
        return $this;


    }


}

?>
