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
class Apptha_FirstDataICICI_Model_Payment extends Mage_Payment_Model_Method_Abstract {

    /**
     * unique internal payment method identifier
     */
    protected $_code = 'firstdataicici';
    protected $_formBlockType = 'firstdataicici/form';
    protected $_infoBlockType = 'firstdataicici/info';

    /**
     * Availability options
     */
    protected $_isGateway              = true;
    protected $_canRefund              = true;
    protected $_canVoid                = true;
    protected $_canUseInternal         = true;
    protected $_canUseCheckout         = true;
    protected $_canUseForMultishipping = false;
    
    /*
     * Initilize order place redirect url  
     * 
     * @return url order redirect url 
     */

    public function getOrderPlaceRedirectUrl() {
        return Mage::getUrl('firstdataicici/processing/redirect/', array('_secure' => true));
    }

}

?>