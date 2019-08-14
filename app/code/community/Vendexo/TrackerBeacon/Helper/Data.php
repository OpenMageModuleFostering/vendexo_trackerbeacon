<?php
/*  Class containing Magento-specific functionality to integrate
    with the VendexoTracker class, with the aim of
    notifying Vendexo of a sale so that the appropriate
    referral commission for affiliates can be calculated.

    Copyright (C) 2016 Vendexo
*/


class Vendexo_TrackerBeacon_Helper_Data extends Mage_Core_helper_Abstract
{
    public function getExtensionVersion()
    {
        return (string)Mage::getConfig()->getNode()->modules->Vendexo_TrackerBeacon->version;
    }
}

?>

