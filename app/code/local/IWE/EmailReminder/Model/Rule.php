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

    /*
    * Process rule
    * @param array $params Initial parameters
    * @param array $objects Additional initial parameters
    * @return bool Processing result
    */
    public function process($params, $objects = array(), $dateOffset=false)
    {
        $objects = $this->_createObjects($params, $objects);
        if (isset($objects['order']) && is_object($objects['order']) && $objects['order']->status) {
            $objects['order']->status
                = '"' . Mage::getSingleton('sales/order_config')->getStatusLabel($objects['order']->status) . '"';
        }


        if (!$this->_validated) {
            $this->validate($objects);
        }
        if ($this->_isValid) {
            $message = 'rule id=' . $this->getId() . ' validation OK';
            $subject = "validation OK";
            Mage::getSingleton('followupemail/log')->logSuccess($message, $this, $subject);

            if (!($this->getChain() && count($chain = unserialize($this->getChain())))) {
                Mage::getSingleton('followupemail/log')->logWarning(
                    'rule id=' . $this->getId() . ' has no chain or the chain is empty: "' . $this->getChain() . '"',
                    $this
                );
                return false;
            }
            if (
            Mage::getModel('followupemail/unsubscribe')->checkIsUnsubscribed(
                $objects['store_id'], $objects['customer_id'], $objects['customer_email'], $this->getId()
            )
            ) {
                $message = 'Email canceled. The customer has unsubscribed from getting this email: customer id='
                    . $objects['customer_id'] . ' email=' . $objects['customer_email'] . ' rule id=' . $this->getId();
                Mage::getSingleton('followupemail/log')->logWarning($message, $this);
                return false;
            }
            $queue = Mage::getModel('followupemail/queue');
            $sequenceNumber = 1;
            foreach ($chain as $chainItem) {
                // Generate coupon if it needed
                if ($this->getCouponEnabled()) {
                    unset($objects['has_coupon']);
                    //get content of current email template
                    $emailTemplate = $this->_getTemplate($chainItem['TEMPLATE_ID']);
                    $emailTemplateContent = $emailTemplate['content'];

                    // checking for presence standard coupon variable ( {{var coupon.code}})
                    $pattern2 = '|{{\s*var\s+coupon.code\s*}}|u';
                    Mage::app()->getLocale()->emulate($objects['store_id']);
                    $formatDate = Mage::app()->getLocale()->getDateFormat(Mage_Core_Model_Locale::FORMAT_TYPE_LONG);
                    if (preg_match_all($pattern2, $emailTemplateContent, $matches) > 0) {
                        $coupon = Mage::helper('followupemail/coupon')->createNew($this, $chainItem['DAYS']);
                        $message = 'New coupon ' . $coupon->getCouponCode() . ' is created {'
                            . print_r($coupon->getData(), true) . '}'
                        ;
                        $subject = "New coupon is created";
                        Mage::getSingleton('followupemail/log')->logSuccess($message, $this, $subject);
                        $_dateStr = Mage::helper('core')->formatDate(
                            $coupon->getExpirationDate(), Mage_Core_Model_Locale::FORMAT_TYPE_LONG
                        );
                        $coupon->setExpirationDate(Mage::app()->getLocale()->date($_dateStr)->toString($formatDate));
                        $objects['coupon'] = $coupon;

                        $message
                            =
                            'Coupon ' . $coupon->getCouponCode() . ' used {' . print_r($coupon->getData(), true) . '}';
                        $subject = "Coupon used";
                        Mage::getSingleton('followupemail/log')->logSuccess($message, $this, $subject);
                    }

                    // checking for presence extended coupon variable ( {{var coupons.__ALIAS__.code}})
                    $pattern1 = '|{{\s*var\s+coupons.(.*).code\s*}}|u';

                    if (preg_match_all($pattern1, $emailTemplateContent, $matches) > 0) {

                        // using object for access to variables from AW_Followupemail_Model_Filter::filter()
                        $coupons = new Varien_Object();

                        foreach ($matches[1] as $couponId) {
                            $coupon = Mage::helper('followupemail/coupon')->createNew($this);
                            $message = 'New coupon ' . $coupon->getCouponCode() . ' is created {'
                                . print_r($coupon->getData(), true) . '}'
                            ;
                            $subject = "New coupon is created";
                            Mage::getSingleton('followupemail/log')->logSuccess($message, $this, $subject);
                            $message = 'Coupon ' . $coupon->getCouponCode() . ' used {'
                                . print_r($coupon->getData(), true). '}'
                            ;
                            $subject = "Coupon used";
                            Mage::getSingleton('followupemail/log')->logSuccess($message, $this, $subject);
                            $_dateStr = Mage::helper('core')->formatDate(
                                $coupon->getExpirationDate(), Mage_Core_Model_Locale::FORMAT_TYPE_LONG
                            );
                            $coupon->setExpirationDate(
                                Mage::app()->getLocale()->date($_dateStr)->toString($formatDate)
                            );
                            $coupons->setData($couponId, $coupon);
                        }

                        $objects['coupons'] = $coupons;
                    }
                    Mage::app()->getLocale()->revert();
                }
                $objects['has_coupon'] = isset($objects['coupon']);

                $objects['sequence_number'] = $sequenceNumber;

                $objects['time_delay'] = $chainItem['DAYS'] * 1440 + $chainItem['HOURS'] * 60 + $chainItem['MINUTES'];
                $objects['time_delay_text'] = Mage::helper('followupemail')->getTimeDelayText(
                    $chainItem['DAYS'], $chainItem['HOURS'], $chainItem['MINUTES']
                );

                $code = AW_Followupemail_Helper_Data::getSecurityCode();
                $objects['security_code'] = $code;
                $objects['url_resume'] = $objects['store']->getUrl(
                    'followupemail/index/resume', array('code' => $code)
                );
                $objects['url_unsubscribe'] = $objects['store']->getUrl(
                    'followupemail/index/unsubscribe', array('code' => $code)
                );

                /* Store logo init */
                $objects['logo_url'] = Mage::helper('followupemail')->getLogoUrl($objects['store']->getStoreId());
                $objects['logo_alt'] = Mage::helper('followupemail')->getLogoAlt($objects['store']->getStoreId());

                //-------------------------------------------------------------------
                //Check for cross-sells functionality active
                if ($this->getCrossActive()) {
                    $objects['related'] = $this->_getCrossProducts($objects);
                }
                /** @var $productWishlistCollection Mage_Wishlist_Model_Resource_Product_Collection */
                $wishlist = Mage::getModel('wishlist/wishlist')->loadByCustomer($objects['customer_id']);
                $productWishlistCollection = Mage::getResourceModel('wishlist/item_collection');
                $productWishlistCollection->addWishlistFilter($wishlist);

                if (isset($objects['order'])) {
                    $paymentBlock = Mage::helper('payment')->getInfoBlock($objects['order']->getPayment())
                        ->setIsSecureMode(true);
                    $paymentBlock->getMethod()->setStore($objects['order']->getStoreId());
                    $objects['payment_html'] = $paymentBlock->toHtml();
                }

                if($dateOffset){
                    $dateOffset = strtotime($dateOffset);
                }
                else{
                    $dateOffset = time();
                }
                if (!$content = $this->_getContent($objects, $chainItem['TEMPLATE_ID'])) {
                    $message = "rule id={$this->getId()} has invalid templateId=" . $chainItem['TEMPLATE_ID']
                        . " in sequenceNumber=$sequenceNumber";
                    $subject = "Rule has invalid";
                    Mage::getSingleton('followupemail/log')->logError($message, $this, $subject);
                    return false;
                } else {
                    $testFlag = Mage::helper('followupemail')->__('TEST EMAIL ');
                    $queue->add(
                        $code,
                        $sequenceNumber,
                        $content['sender_name'],
                        $content['sender_email'],
                        $objects['customer_name'],
                        ($this->_isTest) ? $this->getTestRecipient() : $objects['customer_email'],
                        $this->getId(),
                        $dateOffset + $objects['time_delay'] * 60,
                        ($this->_isTest) ? $testFlag . $content['subject'] : $content['subject'],
                        ($this->_isTest) ? $testFlag . $content['content'] : $content['content'],
                        $objects['object_id'],
                        $params,
                        $content['template_styles']
                    );
                }
                $sequenceNumber++;
            }
            return true;
        }
        Mage::getSingleton('followupemail/log')->logWarning(
            'rule id=' . $this->getId() . ' is not valid for event=' . $this->getEventType() . ' reason="'
            . $this->_validationMessage . '" objectId=' . (isset($objects['object_id']) ? $objects['object_id']
                : 'none') . ', params="' . AW_Followupemail_Helper_Data::printParams($params), $this
        );

        return false;
    }
}
