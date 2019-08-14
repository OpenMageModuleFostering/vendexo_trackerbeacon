<?php
/*  Class containing Magento-specific functionality to integrate
    with the VendexoTracker class, with the aim of
    notifying Vendexo of a sale so that the appropriate
    referral commission for affiliates can be calculated.

    Copyright (C) 2016 Vendexo
*/

require_once(dirname(__DIR__) . '/lib/vendexotracker.php');

class Vendexo_TrackerBeacon_Block_Beacon extends Mage_Core_Block_Template {
    // This is a backing object referenced in the tracking beacon
    // template (.phtml) file to obtain dynamic content such
    // as the shop code, vxt code, order id and order amount.

    /*  renderLastOrderTracker
        Renders a sale tracker for the just-completed order.
    */
    public function renderLastOrderTracker()
    {
        $order = $this->getLastOrder();
        if ($order) {
            return $this->renderTrackerSale($order);
        } else {
            return '';
        }
    }


    /*  renderTrackerSale:
        Method to return a string containing the rendered HTML markup
        for the tracker.
        Parameters:
          - $order: The order object.
        Returns:
          A string containing the tracker markup. This can,
          for example, be added to a context array for passing
          into an order confirmation template.
    */
    public function renderTrackerSale($order) {
        $shop_code = $this->getShopCode();
        $vxt_code = $this->getVxtCode();
        $secret = $this->getShopSecret();
        $ctr_id = '';

        // Get CTR ID from Magento's session, if available:
        $session = Mage::getSingleton('core/session');
        if ($session->hasVxafCtr()) {
            $ctr_id = $session->getVxafCtr();
            // Mage::log("Vendexo_TrackerBeacon_Block_Beacon.render_tracker_sale ctr_id from session: " . $ctr_id);
        }
        // Use the regular cookie value, if available
        if (!empty($_COOKIE['vxaf_ctr'])) {
            $ctr_id = $_COOKIE['vxaf_ctr'];
            // Mage::log("Vendexo_TrackerBeacon_Block_Beacon.render_tracker_sale ctr_id from cookie: " . $ctr_id);
        }
        $client_ip_address = $_SERVER['REMOTE_ADDR'];
        $order_ref = $order->getIncrementId();
        $subtotal = $order->getBaseSubtotal();
        $discount = $order->getBaseDiscountAmount();
        $amount = $subtotal + $discount;
        $currency_code = $order->getBaseCurrencyCode();
        $buyer_email = $order->getCustomerEmail();

        $coupon_codes = array();
        $coupon_code = $order->getCouponCode();
        if ($coupon_code) {
            $coupon_codes[] = $coupon_code;
        }
        $basket_items = $this->getBasketItems($order);
        $tracker_version = (string)Mage::helper('vendexo_trackerbeacon')->getExtensionVersion();

        $vendexo_tracker = new VendexoTracker();
        $vendexo_tracker_rendered = $vendexo_tracker->render_tracker(
            $shop_code, $vxt_code, $ctr_id, $client_ip_address,
            $amount, $currency_code, $buyer_email,
            $order_ref, '', '',
            $coupon_codes, $basket_items, $secret, $tracker_version);
        return $vendexo_tracker_rendered;
    }

    /*  getBasketItems:
        Return details of the ordered products in the form required
        for the tracker.
    */
    private function getBasketItems($order) {
        $order_items = $order->getAllVisibleItems();
        $basket_items = array();
        foreach($order_items as $item) {
            $basket_items[] = array(
                'id' => $item->productId,
                'name' => $item->name,
                'product_group_id' => 0, //TODO: Find suitable field, if any
                'qty' => (int)$item->qtyOrdered,
                'price' => $item->basePrice,
            );
        }
        return $basket_items;
    }

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

    public function getShopSecret() {
        return Mage::getStoreConfig('sales_affiliate_networks/vendexo/shop_secret');
    }

    /*
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
    */


}
?>
