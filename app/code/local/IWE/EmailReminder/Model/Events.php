<?php
class IWE_EmailReminder_Model_Events extends AW_Followupemail_Model_Events
{
    public function rentalStart($eventData){
        $ruleIds = Mage::getModel('followupemail/mysql4_rule')
            ->getRuleIdsByEventType(IWE_EmailReminder_Model_Source_Rule_Types::RULE_TYPE_RENTAL_START);
        $customerId = 0; //Need to load the customer here
        if (count($ruleIds)) {
            foreach ($ruleIds as $ruleId) {
                $canProcess = true;
                $rule = Mage::getModel('followupemail/rule')->load($ruleId);
                $salesRep = $rule->getData('sales_rep_id');
                if($salesRep != 0){
                    $dealerId = Mage::getModel('amperm/perm')->getResource()->getUserByCustomer($customerId);
                    if($dealerId != $salesRep){
                        $canProcess = false;
                    }
                }
                if($canProcess){
                    $params = array();
                    $objects = array();
                    $params['customer_id'] = $customerId;
                    Mage::getModel('followupemail/rule')->load($ruleId)->process($params, $objects);
                }
            }
        }
    }

    public function rentalEnd($eventData){
        $ruleIds = Mage::getModel('followupemail/mysql4_rule')
            ->getRuleIdsByEventType(IWE_EmailReminder_Model_Source_Rule_Types::RULE_TYPE_RENTAL_END);
        if (count($ruleIds)) {
            foreach ($ruleIds as $ruleId) {

            }
        }
    }

    public function quoteSent($eventData){
        $ruleIds = Mage::getModel('followupemail/mysql4_rule')
            ->getRuleIdsByEventType(IWE_EmailReminder_Model_Source_Rule_Types::RULE_TYPE_QUOTE_SENT);
        if (count($ruleIds)) {
            foreach ($ruleIds as $ruleId) {

            }
        }
    }

    public function quoteProcessed($eventData){
        $ruleIds = Mage::getModel('followupemail/mysql4_rule')
            ->getRuleIdsByEventType(IWE_EmailReminder_Model_Source_Rule_Types::RULE_TYPE_QUOTE_PROCESSED);
        if (count($ruleIds)) {
            foreach ($ruleIds as $ruleId) {

            }
        }
    }
}
