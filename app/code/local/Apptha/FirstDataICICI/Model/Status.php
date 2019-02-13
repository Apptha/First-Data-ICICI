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
class Apptha_FirstDataICICI_Model_Status extends Mage_Core_Model_Abstract {

    /**
     * Process Success Event
     *
     * @param array $params
     * @return bool
     */
    public function processSuccessEvent($params) {
        /**
         * load order with Order Id 
         */
        $_order = Mage::getModel('sales/order')->loadByIncrementId($params['order_id']);
        parse_str($params['DATA'], $output);
        $Message = $output['Message'];
        $txnid = $output['TxnID'];
        /**
         *  save last transaction ID from ICICI Payment Gateway 
         */
        $_order->getPayment()->setLastTransId($txnid);
        /**
         * Create invoice 
         */
        $this->_createInvoice($_order);
        /**
         *  send new order email
         */
        $_order->sendNewOrderEmail();
        $_order->setEmailSent(true);
        /**
         *  Save Order history 
         */
        $history = $_order->addStatusHistoryComment($Message, false);
        $history->setIsCustomerNotified(true);
        $_order->save();
        return true;
    }

    /**
     * Builds invoice for order
     * @param array $_order
     */
    protected function _createInvoice($_order) {
        if (!$_order->canInvoice()) {
            return false;
        }
        $invoice = $_order->prepareInvoice();
        $invoice->getOrder()->setData('state', Mage_Sales_Model_Order::STATE_COMPLETE);
        $invoice->getOrder()->setStatus(Mage_Sales_Model_Order::STATE_COMPLETE);
        $_order->addRelatedObject($invoice);
    }

    /**
     * Processing Order Event 
     * @param array $order_id
     */
    public function processingEvent($order_id) {
        /**
         * load order with Order Id 
         */
        $_order = Mage::getModel('sales/order')->loadByIncrementId($order_id);
        $Message = Mage::helper('firstdataicici')->__('The customer was redirected to ICICI.');
        $_order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, $Message);
        $_order->save();
    }

    /**
     * Failed Order Event 
     * @param array $params
     */
    public function failedEvent($params) {
        /**
         * load order with Order Id 
         */
        $_order = Mage::getModel('sales/order')->loadByIncrementId($params['order_id']);
        parse_str($params['DATA'], $output);
        $Message = $output['Message'];
        $_order->setState(Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, Mage_Sales_Model_Order::STATE_PENDING_PAYMENT, $Message);
        $_order->save();
    }
    /**
     * Cancel Order Event 
     * @param array $params
     */
    public function cancelEvent($params) {
        /**
         * load order with Order Id 
         */
        $_order = Mage::getModel('sales/order')->loadByIncrementId($params['order_id']);
        parse_str($params['DATA'], $output);
        $Message = $output['Message'];
        $_order->setState(Mage_Sales_Model_Order::STATE_CANCELED, Mage_Sales_Model_Order::STATE_CANCELED, $Message);
        $_order->save();
    }
}