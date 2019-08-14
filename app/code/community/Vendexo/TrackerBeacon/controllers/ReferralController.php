<?php
class Vendexo_TrackerBeacon_ReferralController extends Mage_Core_Controller_Front_Action {
    public function indexAction() {
        $vxaf_ctr = isset($_REQUEST['vxaf-ctr'])? $_REQUEST['vxaf-ctr'] : '';

        if (!empty($vxaf_ctr)) {
            // Remember it in the "session" cookie:
            $session = Mage::getSingleton('core/session');
            $session->setVxafCtr($vxaf_ctr);

            // Also set a regular cookie:
            $expires = time() + 365 * 24 * 3600;
            $domain = strtolower($_SERVER['HTTP_HOST']);
            if (substr_compare($domain, 'www.', 0, 4) == 0) {
                $domain = substr($domain, 3);
            }
            setcookie('vxaf_ctr', $vxaf_ctr, $expires, '/', $domain);
        }
        $next = isset($_REQUEST['next'])? $_REQUEST['next'] : '';
        if (!$next) {
            $next = isset($_REQUEST['url'])? $_REQUEST['url'] : '';
        }
        if (!$next) {
            $next = Mage::getBaseUrl();
        }
        $this->_redirectUrl($next);
    }
}
?>
