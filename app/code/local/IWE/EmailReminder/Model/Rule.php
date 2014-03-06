<?php
class IWE_EmailReminder_Model_Rule extends AW_Followupemail_Model_Rule
{
    public function load($id, $field = null)
    {
        parent::load($id);
        $this->_validated = true;
        $this->_isValid = true;
        return $this;
    }

    /*
     * Validates rule basing on product properties
     * @param array $params Parameters to inspect
     * @return bool|string Check result
     */
    protected function _validate($params)
    {
        $this->_validated = true;

        if (true !== $res = $this->validateByCustomer($params)) {
            return $res;
        }

        // MSS check
        $mssRuleId = false;
        if (Mage::helper('followupemail')->isMSSInstalled()
            && $mssRuleId = $this->getMssRuleId()
        ) {
            if (isset($params['customer'])) {
                if (!Mage::getModel('marketsuite/api')->checkRule($params['customer'], $mssRuleId)) {
                    return 'MSS rule d=' . $mssRuleId . ' validation failed';
                }
                $mssRuleId = false; // preventing further MSS checks
            }
        }

        // Check is customer is unsubscribed for this rule
        if (isset($params['customer']) && $params['customer']->getId()) {

            if (in_array($params['customer']->getId(), $this->getData('unsubscribed_customers'))) {
                return Mage::helper('followupemail')->__(
                    'Customer with ID %s is unsubscribed from rule %s', $params['customer']->getId(), $this->getId()
                );
            }
        }

        switch ($this->getEventType()) {
            case AW_Followupemail_Model_Source_Rule_Types::RULE_TYPE_WISHLIST_SHARED :
            case AW_Followupemail_Model_Source_Rule_Types::RULE_TYPE_WISHLIST_PRODUCT_ADD :
                return $this->validateByProduct($params);
                break;

            case AW_Followupemail_Model_Source_Rule_Types::RULE_TYPE_ABANDONED_CART_NEW :
                if ($mssRuleId
                    && isset($params['quote'])
                    && !Mage::getModel('marketsuite/api')->checkRule($params['quote'], $mssRuleId)
                ) {
                    return 'MSS rule d=' . $mssRuleId . ' validation failed';
                }

                return $this->validateOrderOrCart($params, 'quote');
                break;
            //adding our custom events here
            case IWE_EmailReminder_Model_Source_Rule_Types::RULE_TYPE_RENTAL_START :
            case IWE_EmailReminder_Model_Source_Rule_Types::RULE_TYPE_RENTAL_END :
            //case IWE_EmailReminder_Model_Source_Rule_Types::RULE_TYPE_QUOTE_SENT :
            case IWE_EmailReminder_Model_Source_Rule_Types::RULE_TYPE_QUOTE_PROCESSED :
            //adding our custom events here
            case AW_Followupemail_Model_Source_Rule_Types::RULE_TYPE_CUSTOMER_NEW :
            case AW_Followupemail_Model_Source_Rule_Types::RULE_TYPE_CUSTOMER_LOGGED_IN :
            case AW_Followupemail_Model_Source_Rule_Types::RULE_TYPE_CUSTOMER_LAST_ACTIVITY :
            case AW_Followupemail_Model_Source_Rule_Types::RULE_TYPE_CUSTOMER_BIRTHDAY :
            case AW_Followupemail_Model_Source_Rule_Types::RULE_TYPE_CUSTOMER_NEW_SUBSCRIPTION :
                // return $this->validateByCustomer($params);
                return true;
                break;

            case AW_Followupemail_Model_Source_Rule_Types::RULE_TYPE_CUSTOMER_CAME_BACK_BY_LINK :
                // return $this->validateByCustomer($params);
                return true;
                break;

            case AW_Followupemail_Model_Source_Rule_Types::RULE_TYPE_CUSTOMER_GROUP_CHANGED:
                return $this->validateByCustomer($params);
                break;

            default :
                if ($this->_orderStatus) {
                    if ($mssRuleId
                        && isset($params['order'])
                        && !Mage::getModel('marketsuite/api')->checkRule($params['order'], $mssRuleId)
                    ) {
                        return 'MSS rule d=' . $mssRuleId . ' validation failed';
                    }

                    return $this->validateOrderOrCart($params, 'order');
                }
                break;
        }
        return 'Unknown event';
    }
}
