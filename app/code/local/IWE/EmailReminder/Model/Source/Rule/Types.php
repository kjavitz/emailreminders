<?php
class IWE_EmailReminder_Model_Source_Rule_Types extends AW_Followupemail_Model_Source_Rule_Types
{
    const RULE_TYPE_RENTAL_START = 'rental_start';
    const RULE_TYPE_RENTAL_END = 'rental_end';
    const RULE_TYPE_QUOTE_SENT = 'quote_sent';
    const RULE_TYPE_QUOTE_PROCESSED = 'quote_processed';

    public static function toShortOptionArray($extended = false)
    {
        $helper = Mage::helper('followupemail');
        $result = parent::toShortOptionArray($extended);

        $result[self::RULE_TYPE_RENTAL_START] = $helper->__('Rental start date');
        $result[self::RULE_TYPE_RENTAL_END] = $helper->__('Rental end date');
        $result[self::RULE_TYPE_QUOTE_SENT] = $helper->__('Quote is sent');
        $result[self::RULE_TYPE_QUOTE_PROCESSED] = $helper->__('Quote is processed');

        return $result;
    }

    public static function toOptionArray($extended = false)
    {
        $options = self::toShortOptionArray($extended);
        $res = array();

        foreach ($options as $k => $v) {
            $res[] = array(
                'value' => $k,
                'label' => $v
            );
        }

        return $res;
    }

}