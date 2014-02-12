<?php

class ITwebexperts_Emailremainders_Model_Observer
{
    /**
     * Adds "Emails" tab to the customer edit page in admin.
     *
     * @param Varien_Event_Observer $observer
     */
    public function onCustomerEmailsTabAdd(Varien_Event_Observer $observer)
    {
        $block = $observer->getEvent()->getBlock();
        if (!($block instanceof Mage_Adminhtml_Block_Customer_Edit_Tabs)) {
            return;
        }
        $block->addTabAfter('emailremainders', array(
            'label' => $this->getModuleHelper()->__('Emails'),
            'content' => '',
        ), 'tags');
    }

    /**
     * Returns default module helper.
     *
     * @return ITwebexperts_Emailremainders_Helper_Data
     */
    public function getModuleHelper()
    {
        return Mage::helper('emailremainders');
    }
}