<?php
$installer = $this;

$installer->startSetup();
try {
    $installer->run("
        ALTER TABLE `{$this->getTable('followupemail/rule')}` ADD `sales_rep_id` INT( 10 )  NULL DEFAULT '0';
    ");
} catch (Exception $e) {
    Mage::logException($e);
}
$installer->endSetup();