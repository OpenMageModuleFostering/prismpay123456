<?php

/**
 * Our test CC module adapter
 */
class PrismPayTech_PrismPay_Model_PaymentHelper extends Mage_Paygate_Model_Authorizenet {

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
    protected $_isGateway = true;

    /**
     * Can authorize online?
     */
    protected $_canAuthorize = true;

    /**
     * Can capture funds online?
     */
    protected $_canCapture = true;

    /**
     * Can capture partial amounts online?
     */
    protected $_canCapturePartial = false;

    /**
     * Can refund online?
     */
    protected $_canRefund = true;

    /**
     * Can void transactions online?
     */
    protected $_canVoid = true;

    /**
     * Can use this payment method in administration panel?
     */
    protected $_canUseInternal = true;

    /**
     * Can show this payment method as an option on checkout payment page?
     */
    protected $_canUseCheckout = true;

    /**
     * Is this payment method suitable for multi-shipping checkout?
     */
    protected $_canUseForMultishipping = true;

    /**
     * Can save credit card information for future processing?
     */
    protected $_canRefundInvoicePartial = true;
    protected $_canFetchTransactionInfo = true;
    protected $_canReviewPayment = true;
    protected $_formBlockType = 'prismpay/form';
    protected $_infoBlockType = 'prismpay/info';
    protected $_canSaveCc = false;
    
    
    public $__debugMode;
    public $__testMode;
    public $__accountID;
    public $__subAccountID;
    public $__merchantpin;
    
    
    public function __construct() {
        parent::__construct();
        
          Mage::log('PrismPay Payment Helper Class Called!');
        
          $store = Mage::app()->getStore();
          $storeId = Mage::app()->getStore()->getStoreId();
          $this->__debugMode = Mage::getStoreConfig('payment/prismpay/debug', $storeId);
          $this->__testMode = Mage::getStoreConfig('payment/prismpay/test', $storeId);
          $this->__merchantpin = Mage::getStoreConfig('payment/prismpay/merchantpin', $storeId);
          
          if($this->__testMode==1)
          {
              $this->__accountID="py7l4";
              $this->__subAccountID="";
              
          }else
          {
              $this->__accountID = Mage::getStoreConfig('payment/prismpay/account_id', $storeId);
              $this->__subAccountID = Mage::getStoreConfig('payment/prismpay/sub_account_id', $storeId);
          }
        
    }
    
    
    public function curlHelper(Varien_Object $payment,$xmlString)
    {
        
                $ch = curl_init();
                curl_setopt($ch, CURLOPT_URL, 'https://trans.myprismpay.com/cgi-bin/ProcessXML.cgi');
                curl_setopt($ch, CURLOPT_HEADER, false);
                curl_setopt($ch, CURLINFO_HEADER_OUT, false);
                curl_setopt($ch, CURLOPT_HTTPHEADER, Array("Content-Type: application/xml; charset=utf-8"));
                //curl_setopt($ch, CURLOPT_USERPWD, 'myusername:mypassword');
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlString);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);

                return $data = curl_exec($ch); //This value is the string returned from the bank...

                if (!$data) {
                    throw new Exception(curl_error($ch));
                }
                $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                if ($httpcode && substr($httpcode, 0, 2) != "20") { //Unsuccessful post request...
                    throw new Exception("Returned HTTP CODE: " . $httpcode . " for this URL: " . $urlToPost);
                }
                curl_close($ch);
            
    }
    
    
    public function canRefund() {

        return true;
    }
    
    public function canSaveCard()
    {
        /*if (Mage::getModel('braintree_payments/paymentmethod')->useVault() && 
            (Mage::getSingleton('customer/session')->isLoggedIn() || 
            Mage::getSingleton('checkout/type_onepage')->getCheckoutMethod() 
                == Mage_Checkout_Model_Type_Onepage::METHOD_REGISTER)){
            
            return true;
        }*/
        return true;
    }
    

   
}

?>
