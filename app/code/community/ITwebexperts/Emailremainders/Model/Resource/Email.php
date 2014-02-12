<?php

class ITwebexperts_Emailremainders_Model_Resource_Email extends Mage_Core_Model_Resource_Db_Abstract
{
    /**
     * Define main table
     *
     */
    protected function _construct()
    {
        $this->_init('emailremainders/email', 'email_id');
    }
}