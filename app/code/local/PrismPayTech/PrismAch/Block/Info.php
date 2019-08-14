<?php
/**
 * Magento
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Open Software License (OSL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/osl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@magentocommerce.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade Magento to newer
 * versions in the future. If you wish to customize Magento for your
 * needs please refer to http://www.magentocommerce.com for more information.
 *
 * @category    Mage
 * @package     Mage_Payment
 * @copyright   Copyright (c) 2014 Magento Inc. (http://www.magentocommerce.com)
 * @license     http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */

/**
 * Credit card generic payment info
 */
class PrismPayTech_PrismAch_Block_Info extends Mage_Payment_Block_Info
{
    /*protected function _construct()
    {
        //parent::_construct();
        //$this->setTemplate('payment/form/cc.phtml');
                    //Mage::log("PrismPayTech_PrismPay_Block_Info");

    }*/


    /**
     * Retrieve credit card type name
     *
     * @return string
     */
   
    /**
     * Prepare credit card related payment info
     *
     * @param Varien_Object|array $transport
     * @return Varien_Object
     */
    protected function _prepareSpecificInformation($transport = null)
    {
        if (null !== $this->_paymentSpecificInformation) {
            return $this->_paymentSpecificInformation;
        }
        $transport = parent::_prepareSpecificInformation($transport);
        $data = array();
        
        
        //print_r($this->getInfoInstance());
        /*print"<pre>";
         $data = $this->getRequest()->getPost('name', array());
        //$result = $this->getOnepage()->savePayment($data);
        //$payment->importData($data);
        print_r($data);
        //print_r($result);
         print_r($this->getInfo());
         echo "===================<br>";
        print_r($transport->getData("name"));
         echo "===================<br>";
        print_r($transport->getData("payment"));
         echo "===================<br>";
        print_r($transport->getData("payment[ckname]"));
         echo "===================<br>";
        print_r($_REQUEST);
        
         echo "===================<br>";
        
        print_r($this->getMethod()->getInfoInstance()->getData("payment[ckname]"));
         echo "===================";
        
        print_r($this->getMethod()->getInfoInstance()->getData("ckname"));
       
        print"</pre>";*/
        $data[Mage::helper('payment')->__('Name')] = "********";
        $data[Mage::helper('payment')->__('Routing Number')] = "********";
        $data[Mage::helper('payment')->__('Account Number')] = "********";
        $data[Mage::helper('payment')->__('Cheque Number')] = "********";
        /*if ($ccType = $this->getCcTypeName()) {
            $data[Mage::helper('payment')->__('Credit Card Type')] = $ccType;
        }
        if ($this->getInfo()->getCcLast4()) {
            $data[Mage::helper('payment')->__('Credit Card Number')] = sprintf('xxxx-%s', $this->getInfo()->getCcLast4());
        }
        if (!$this->getIsSecureMode()) {
            if ($ccSsIssue = $this->getInfo()->getCcSsIssue()) {
                $data[Mage::helper('payment')->__('Switch/Solo/Maestro Issue Number')] = $ccSsIssue;
            }
            $year = $this->getInfo()->getCcSsStartYear();
            $month = $this->getInfo()->getCcSsStartMonth();
            if ($year && $month) {
                $data[Mage::helper('payment')->__('Switch/Solo/Maestro Start Date')] =  $this->_formatCardDate($year, $month);
            }
        }*/
        //print_r("<pre>");
        //print_r ($data);
        //die;
        //mail()
        return $transport->setData(array_merge($data, $transport->getData()));
    }

    /**
     * Format year/month on the credit card
     *
     * @param string $year
     * @param string $month
     * @return string
     */
    protected function _formatCardDate($year, $month)
    {
        return sprintf('%s/%s', sprintf('%02d', $month), $year);
    }
}
