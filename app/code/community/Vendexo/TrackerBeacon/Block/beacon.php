<?php
class Vendexo_TrackerBeacon_Block_Beacon extends Mage_Core_Block_Template {
    // This is a backing object referenced in the tracking beacon
    // template (.phtml) file to obtain dynamic content such
    // as the shop code, vxt code, order id and order amount.

    private function getLastOrder() {
        $order = null;
        $orderId = Mage::getSingleton('checkout/session')->getLastOrderId();
        if ($orderId) {
            $order = Mage::getModel('sales/order')->load($orderId);
        }
        return $order;
    }

    public function getShopCode() {
        return Mage::getStoreConfig('sales_affiliate_networks/vendexo/affiliate_program_code');
    }

    public function getVxtCode() {
        return Mage::getStoreConfig('sales_affiliate_networks/vendexo/vxt_code');
    }

    public function getOrderId() {
        $order = $this->getLastOrder();
        if ($order) {
            return $order->getIncrementId();
        } else {
            return null;
        }
    }

    public function getOrderAmount() {
        $order = $this->getLastOrder();
        if ($order) {
            return $order->getBaseSubtotal();
        } else {
            return null;
        }
    }

    public function getCurrencyCode() {
        $order = $this->getLastOrder();
        if ($order) {
            return $order->getBaseCurrencyCode();
        } else {
            return null;
        }
    }


}
?>
