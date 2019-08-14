<?php
/*  Class to generate the code to notify Vendexo of a sale
    so that the appropriate referral commission for affiliates
    can be calculated.

    Copyright (C) 2016 Vendexo
*/


class VendexoTracker {
    const MAX_NAME_LENGTH = 20;
    const TRACK_URL_DOMAIN = 'track.vendexo.com';
    const TRACK_URL_PATH = '/aff/track/';

    public function get_track_url_domain() {
        return self::TRACK_URL_DOMAIN;
    }

    public function get_track_url_path() {
        return self::TRACK_URL_PATH;
    }

    /* get_tracking_params:
        Returns an array of two-element arrays. The two-element arrays
        consist of a name and value. Using this format enables
        the parameters to contain multiple entries where the parameter
        name is the same (e.g. "couponCode").
        Parameters:
          $shop_code: The ID code of the shop/affiliate program.
          $ctr_id: The tracking ID, if known, otherwise null or ''.
          $client_ip_address: The user's IP address.
          $order_amount: The value of the order (ideally excluding shipping
                and tax).
                Set to null or '' for notifying goals.
          $currency_code: The three-letter code of the order currency.
          $buyer_email: The e-mail address of the customer/user who placed
                the order or achieved the goal.
          $order_id: The ID of the order (as you would identify it on your
                control panel).
                Set to null or '' for notifying goals.
          $goal_code: For goals, this is the ID code identifying the goal type.
                Set to '' for sales.
          $goal_id: For goals, this is the unique ID identifying the goal
                achievment by the user. Set to '' for sales.
          $coupon_codes: An array of the coupon codes (promo codes) used with
                the order.
          $basket_items: An array containing details of the items purchased.
            Each element to be an associative array in the following format:
                'id':   The id of the product
                'name': The name of the product
                'product_group_id': The ID of the product group (to identify
                        commission payable) or else '' if using the default.
                'qty':  The quantity purchased
                'price': The product's unit price (in the currency specified
                        above)
          $secret: A secret code, provided by Vendexo, used to generate a
                digital signature for security purposes.
          $tracker_version: The version of the Vendexo tracker plugin/extension.
    */
    public function get_tracking_params($shop_code, $ctr_id,
            $client_ip_address, $order_amount, $currency_code,
            $buyer_email, $order_id, $goal_code, $goal_id,
            $coupon_codes, $basket_items, $secret, $tracker_version) {

        $ctr_id = ($ctr_id)? $ctr_id : '';
        $client_ip_address = ($client_ip_address)? $client_ip_address : ((isset($_SERVER['REMOTE_ADDR']))? $_SERVER['REMOTE_ADDR'] : '');
        $amount = ($order_amount)? $order_amount : '0.00';
        $currency_code = strtoupper($currency_code);
        $email_hash = hash('sha256', strval($buyer_email));
        $coupon_codes = ($coupon_codes)? $coupon_codes : array();
        $basket_items = ($basket_items)? $basket_items : array();
        if ($order_id) {            // Sale
            $action = 'sale';
        } else {                    // Goal
            $action = 'goal';
        }
    
        $params = array(            // Element order is important (used in signing)
            array('tv', $tracker_version),
            array('action', $action),
            array('shopCode', $shop_code),
            array('ctr', $ctr_id),
            array('clientIPAddress', $client_ip_address),
            array('amount', $amount),
            array('currency', $currency_code),
            array('emailHash', $email_hash)
        );
        if ($order_id) {            // Sale
            $params[] = array('orderRef', $order_id);
        } else {                    // Goal
            $params[] = array('gc', $goal_code);
            $params[] = array('gr', $goal_id);
        }
        // Coupon codes:
        foreach($coupon_codes as $cc) {
            $params[] = array('couponCode', $cc);
        }
        // Basket items:
        $i = 0;
        foreach($basket_items as $item) {
            $item_id = (isset($item['id']))? $item['id'] : '';
            $item_name = (isset($item['name']))? mb_substr($item['name'], 0, self::MAX_NAME_LENGTH) : '';
            $item_product_group_id = (isset($item['product_group_id']))? $item['product_group_id'] : '';
            $item_qty = (isset($item['qty']))? $item['qty'] : 1;
            $item_price = (isset($item['price']))? $item['price'] : '0.00';
    
            $index = strval($i);
            $params[] = array('itmId' . $index, $item_id);
            $params[] = array('itmName' . $index, $item_name);
            $params[] = array('itmPGId' . $index, $item_product_group_id);
            $params[] = array('itmQty' . $index, $item_qty);
            $params[] = array('itmPrice' . $index, $item_price);
            $i++;
        }
    
        // Sign it:
        $sig_items = array();
        foreach($params as $tuple) {
            $sig_items[] = $tuple[1];       // The parameter value
        }
        $params[] = array('sig', $this->make_list_signature($sig_items, $secret));
        return $params;
    }
    

    /*
        get_tracking_params_str:
        Returns a string consisting of the urlencoded tracking parameters.
        For parameter descriptions see the descriptions for
        the get_tracking_params method above.
    */
    public function get_tracking_params_str($shop_code, $ctr_id,
            $client_ip_address, $order_amount, $currency_code,
            $buyer_email, $order_id, $goal_code, $goal_id,
            $coupon_codes, $basket_items, $secret, $tracker_version) {

        return $this->urlencode_params($this->get_tracking_params(
            $shop_code, $ctr_id, $client_ip_address, $order_amount,
            $currency_code, $buyer_email, $order_id, $goal_code, $goal_id,
            $coupon_codes, $basket_items, $secret, $tracker_version));
    }


    /*
        render_tracker:
        Render the tracker and return it as a string .
        For parameter descriptions see the descriptions for
        the get_tracking_params method above.
        The $vxt_code parameter is a code provided by Vendexo.
    */
    public function render_tracker($shop_code, $vxt_code, $ctr_id,
            $client_ip_address, $order_amount, $currency_code,
            $buyer_email, $order_id, $goal_code, $goal_id,
            $coupon_codes, $basket_items, $secret, $tracker_version) {

        $param_str = $this->get_tracking_params_str($shop_code, $ctr_id,
            $client_ip_address, $order_amount, $currency_code,
            $buyer_email, $order_id, $goal_code, $goal_id,
            $coupon_codes, $basket_items, $secret, $tracker_version);
    
        $tracker_lines = array(
            '<script type="text/javascript">',
            '(function() {',
                'var lProtocol;',
                "if (window.location.protocol.toLowerCase() == 'https:') {",
                    "lProtocol = 'https:';",
                "} else {",
                    "lProtocol = 'http:';",
                "}",
                'var lUrl = lProtocol + "//' . $this->get_track_url_domain() . $this->get_track_url_path() . 'sale/v1/js/' . $shop_code . '/' . $vxt_code . '/?' . $param_str . '";',
                "var s = document.createElement('script');",
                "s.type = 'text/javascript';",
                "s.async = 1;",
                "s.src = lUrl;",
                "var s0 = document.getElementsByTagName('script')[0];",
                "s0.parentNode.insertBefore(s, s0);",
            '}());',
            '</script>',
            '<noscript><img src="https://' . $this->get_track_url_domain() . $this->get_track_url_path() . 'sale/v1/img/' . $shop_code . '/' . $vxt_code . '/?' . $param_str . '" alt="." width="1" height="1" style="border: 0 none;" /></noscript>'
        );
        return implode("\n", $tracker_lines);
    }
    
    protected function urlencode_params($obj) {
        if (is_array($obj)) {
            if (!count($obj)) {
                return '';
            }
            if (isset($obj[0])) {
                if (is_array($obj[0])) {    // $obj is a list of tuples: (key, value) pairs
                    $vals = array();
                    foreach($obj as $tuple) {
                        if (is_array($tuple[1])) {  // Value is a list of values
                            foreach($tuple[1] as $val) {
                                $vals[] = urlencode($tuple[0]) . '=' . urlencode($val);
                            }
                        } else {
                            $vals[] = urlencode($tuple[0]) . '=' . urlencode($tuple[1]);
                        }
                    }
                    return implode('&', $vals);
                } else {
                    throw new Exception("obj has an unsupported structure.");
                }
            } else {                        // An associative array, keys = param names
                $vals = array();
                foreach($obj as $key => $value) {
                    if (is_array($value)) { // Value is a list of values
                        foreach($value as $val) {
                            $vals[] = urlencode($key) . '=' . urlencode($val);
                        }
                    } else {
                        $vals[] = urlencode($key) . '=' . urlencode($value);
                    }
                }
                return implode('&', $vals);
            }
        } else {
            return urlencode(strval($obj));
        }
    }
    
    protected function make_list_signature($sig_items, $secret) {
        $salt = $this->get_random_string(16);
        return $this->build_list_signature($sig_items, $secret, $salt);
    }
    
    protected function build_list_signature($sig_items, $secret, $salt) {
        $terms = array();
        foreach($sig_items as $value) {
            $terms[] = strval($value);
        }
        $plain = implode('', $terms);
        $signature = $this->base64_hmac($salt . 'signer', $plain, $secret);
        return $salt . '$' . $signature;
    }
    
    protected function get_random_string($len) {
        $buf = array();
        $chars = array_merge(range('A', 'Z'), range('a', 'z'), range('0', '9'));
        $max = count($chars) - 1;
        for ($i = 0; $i < $len; $i++) {
            $buf[] = $chars[mt_rand(0, $max)];
        }
        return implode('', $buf);
    }
    
    protected function base64_hmac($salt, $value, $secret) {
        return $this->urlsafe_b64encode_nopad($this->salted_hmac($salt, $value, $secret));
    }
    
    protected function urlsafe_b64encode_nopad($s) {
        return str_replace('=', '', strtr(base64_encode($s), "+/", "-_"));
    }
    
    protected function salted_hmac($salt, $value, $secret) {
        $key = sha1($salt . $secret, true);
        return hash_hmac('sha1', $value, $key, true);
    }
}
    
?>
