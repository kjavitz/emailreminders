<?php

class ITwebexperts_Emailremainders_Model_Resource_Email_Collection extends Mage_Core_Model_Resource_Db_Collection_Abstract
{
    /**
     * Collection resource initialization
     */
    protected function _construct()
    {
        $this->_init('emailremainders/email');
    }
}