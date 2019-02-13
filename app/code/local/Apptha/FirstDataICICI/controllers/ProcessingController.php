<?php

/**
 * Apptha
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the EULA
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.apptha.com/LICENSE.txt
 *
 * ==============================================================
 *                 MAGENTO EDITION USAGE NOTICE
 * ==============================================================
 * This package designed for Magento COMMUNITY edition
 * Apptha does not guarantee correct work of this extension
 * on any other Magento edition except Magento COMMUNITY edition.
 * Apptha does not provide extension support in case of
 * incorrect edition usage.
 * ==============================================================
 *
 * @category    Apptha
 * @package     Apptha_FirstDataICICI
 * @version     0.1.0
 * @author      Apptha Team <developers@contus.in>
 * @copyright   Copyright (c) 2014 Apptha. (http://www.apptha.com)
 * @license     http://www.apptha.com/LICENSE.txt
 * 
 */
class Apptha_FirstDataICICI_ProcessingController extends Mage_Core_Controller_Front_Action {

    /**
     * Get singleton of Checkout Session Model
     *
     * @return Mage_Checkout_Model_Session
     */
    protected function _getCheckout() {
        return Mage::getSingleton('checkout/session');
    }

    /**
     * Get singleton of Core Session Model
     *
     * @return Mage_Core_Model_Session
     */
     protected function _getCoreSession() {
        return Mage::getSingleton('core/session');
    }
    /**
     * Redirect to ICICI Payment Gateway Url .
     */
    public function redirectAction() {
        try {
            $session = $this->_getCheckout();
            $order = Mage::getModel('sales/order');
            $order->loadByIncrementId($session->getLastRealOrderId());
            if (!$order->getId()) {
                $session->addError(Mage::helper('firstdataicici')->__('No order for processing found'));
                parent::_redirect('checkout/cart', array('_secure' => true));
                return;
            }
            /**
             *  Include ICICI Payment Gateway Required Files.
             */
            include 'Sfa/BillToAddress.php';
            include 'Sfa/CardInfo.php';
            include 'Sfa/Merchant.php';
            include 'Sfa/MPIData.php';
            include 'Sfa/ShipToAddress.php';
            include 'Sfa/PGResponse.php';
            include 'Sfa/PostLibPHP.php';
            include 'Sfa/PGReserveData.php';

            /**
             *  Initialize the classes.
             */
            $oMPI = new MPIData();
            $oCI = new CardInfo();
            $oPostLibphp = new PostLibPHP();
            $oMerchant = new Merchant();
            $oBTA = new BillToAddress();
            $oSTA = new ShipToAddress();
            $oPGResp = new PGResponse();
            $oPGReserveData = new PGReserveData();

            /**
             * ICICI Payment Gateway Return Url  
             */
            $returnUrl = Mage::getUrl('firstdataicici/processing/status/', array('order_id' => $session->getLastRealOrderId()), array('_secure' => true));

            /*
             * Get Ip Address
             */
            $ip = Mage::helper('firstdataicici')->getRealIpAddr();

            $rand = rand();
            /*
             * Get Order Details 
             */
            $incrementId = $order->getIncrementId();
            $address = $order->getBillingAddress();
            $firstName = $address->getFirstname();
            $lastName = $address->getLastname();
            $email = $address->getEmail();
            $street = $address->getStreet(-1);
            $region = $address->getRegion();
            $city = $address->getCity();
            $postcode = $address->getPostcode();
            if (!$region) {
                $region = 'state';
            }
            $countryName = $address->getCountryModel()->getIso3Code();
            $currency = $order->getOrderCurrencyCode();
            $amount = round($order->getGrandTotal(), 2);

            /**
             * Load admin Configuration 
             */
            $settings = Mage::helper('firstdataicici')->iciciSettings();

            /**
             * ICICI Payment Gateway settings 
             */
            $oBTA->setAddressDetails($firstName, $lastName, $street, '', '', $city, $region, $postcode, $countryName, $email);

            $oSTA->setAddressDetails($street, '', '', $city, $region, $postcode, $countryName, $email);

            $oMerchant->setMerchantDetails($settings['merchant_id'], $settings['merchant_id'], $settings['merchant_id'], $ip, $rand, $incrementId, $returnUrl, "POST", $currency, "INV123", "req.Sale", $amount, "", "", "true", "", "", "");

            $oPGResp = $oPostLibphp->postSSL($oBTA, $oSTA, $oMerchant, $oMPI, $oPGReserveData);

            /**
             * check the Response Code
             */
            if ($oPGResp->getRespCode() == '000') {

                Mage::getModel('firstdataicici/status')->processingEvent($session->getLastRealOrderId());
                $RedirectionUrl = $oPGResp->getRedirectionUrl();
                parent::_redirectUrl($RedirectionUrl);
                return;
            } else {
                $this->_getCheckout()->addError($oPGResp->getRespMessage());
                parent::_redirect('checkout/cart', array('_secure' => true));
                return;
            }
        } catch (Exception $e) {
            Mage::logException($e);
            $this->_getCheckout()->addError($e->getMessage());
            parent::_redirect('checkout/cart', array('_secure' => true));
            return;
        }
        $this->loadLayout();
        $this->renderLayout();
    }

    /**
     * Action to which the transaction details will be posted after the payment process is complete.
     * 
     */
    public function statusAction() {

        /**
         * Get Response from ICICI Payment Gateway
         */
        $params = $this->getRequest()->getParams();
        /**
         * To check real order id
         */
        $session = $this->_getCheckout();
        $order = Mage::getModel('sales/order')->loadByIncrementId($session->getLastRealOrderId());

        if ($order->getIncrementId() != $params['order_id']) {
               $session->addError(Mage::helper('firstdataicici')->__('No order for processing found'));
                parent::_redirect('checkout/cart', array('_secure' => true));
                return;
        }

        /**
         *  Include ICICI Payment Gateway Required Files.
         */
        include 'Sfa/EncryptionUtil.php';
        /**
         * Load admin Configuration 
         */
        $settings = Mage::helper('firstdataicici')->iciciSettings();
        $strMerchantId = $settings['merchant_id'];
        $astrFileName = $settings['merchant_key'];
        /**
         *  Initialize Payment Variables.
         */
        $ResponseCode = "";
        $Message = "";

        if ($params) {

            if ($params['DATA'] == null) {
                $this->_getCheckout()->addError(Mage::helper('firstdataicici')->__('Invalid data'));
                parent::_redirect('checkout/cart', array('_secure' => true));
                return;
            }
            $astrResponseData = $params['DATA'];
            $astrDigest = $params['EncryptedData'];
            $oEncryptionUtilenc = new EncryptionUtil();
            $astrsfaDigest = $oEncryptionUtilenc->getHMAC($astrResponseData, $astrFileName, $strMerchantId);

            if (strcasecmp($astrDigest, $astrsfaDigest) == 0) {
                parse_str($astrResponseData, $output);
                if (array_key_exists('RespCode', $output) == 1) {
                    $ResponseCode = $output['RespCode'];
                }
                if (array_key_exists('Message', $output) == 1) {
                    $Message = $output['Message'];
                }
            }
        }
        
        /**
         * check the Response Code
         */
         if ($ResponseCode == 0) {
            /**
             * Process Success Event
             */
            $status = Mage::getModel('firstdataicici/status')->processSuccessEvent($params);
            if (!empty($status)) {
                $this->_redirect('checkout/onepage/success', array('_secure' => true));
                return;
            } else {
                 /**
                 * Failed Order Event 
                 */
                Mage::getModel('firstdataicici/status')->failedEvent($params);
                $this->_getCoreSession()->addError(Mage::helper('firstdataicici')->__('You transaction could not be completed'));
                $this->_redirect('checkout/onepage/failure', array('_secure' => true));
                return;
            }
        } else {
            /**
             * cancel Order Event 
             */
            if (strtolower(trim($Message)) == 'transaction cancelled') {
                
                Mage::getModel('firstdataicici/status')->cancelEvent($params);
                $this->_getCoreSession()->addError($Message);
                $this->_redirect('checkout/onepage/failure', array('_secure' => true));
                return;
            } else {
                /**
                 * Failed Order Event 
                 */
                Mage::getModel('firstdataicici/status')->failedEvent($params);
                $this->_getCoreSession()->addError($Message);
                $this->_redirect('checkout/onepage/failure', array('_secure' => true));
                return;
            }
        }
    }

}
