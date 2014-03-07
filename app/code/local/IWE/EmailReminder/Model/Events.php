<?php
class IWE_EmailReminder_Model_Events extends AW_Followupemail_Model_Events
{
    public function rentalFollowUpEmail($eventData) {
        $ruleTypes = array(IWE_EmailReminder_Model_Source_Rule_Types::RULE_TYPE_RENTAL_START, IWE_EmailReminder_Model_Source_Rule_Types::RULE_TYPE_RENTAL_END);
        foreach($ruleTypes as $ruleType) {
        $ruleIds = Mage::getModel('followupemail/mysql4_rule')
            ->getRuleIdsByEventType($ruleType);
        $order = $eventData->getOrder();
        $customerId = $order->getCustomerId();
        $dateOffset = $order->getData('start_datetime');
        if($ruleType == IWE_EmailReminder_Model_Source_Rule_Types::RULE_TYPE_RENTAL_END){
            $dateOffset = $order->getData('end_datetime');
        }
        if(!$customerId || !$dateOffset){
            return false;
        }

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
                    Mage::getModel('iwe_emailreminder/rule')->load($ruleId)->process($params, $objects, $dateOffset);
                }
            }
        }
        }
    }

    /*
    public function quoteSent($eventData){
    }
    */

    public function quoteProcessed($eventData){
        $ruleIds = Mage::getModel('followupemail/mysql4_rule')
            ->getRuleIdsByEventType(IWE_EmailReminder_Model_Source_Rule_Types::RULE_TYPE_QUOTE_PROCESSED);
        $customerId = 2; //Need to load the customer here
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
                    Mage::getModel('iwe_emailreminder/rule')->load($ruleId)->process($params, $objects);
                }
            }
        }
    }
}
