<?php



$installer = $this;
/* @var $installer Mage_Ecomcharge_Model_Mysql4_Setup */

$installer->startSetup();

$installer->run("CREATE TABLE IF NOT EXISTS `ecomcharge_transaction` (`id_ecomcharge_transaction` int(11) NOT NULL AUTO_INCREMENT,
		`type` enum('payment','refund','authorization') NOT NULL, `id_ecomcharge_customer` int(10) unsigned NOT NULL, `id_cart` int(10) unsigned NOT NULL,
		`id_order` int(10) unsigned NOT NULL, `ecom_uid` varchar(60) NOT NULL, `amount` decimal(10,4) NOT NULL, `status` enum('incomplete','failed','successful') NOT NULL,
		`currency` varchar(3) NOT NULL,  `mode` enum('live','test') NOT NULL,`id_refund` varchar(32) , `refund_amount` decimal(10,4),`au_ecom_uid` varchar(60),`ecom_token` varchar(100), `date_add` datetime NOT NULL,  PRIMARY KEY (`id_ecomcharge_transaction`), KEY `idx_transaction` (`type`,`id_order`,`status`)) ENGINE=InnoDB DEFAULT CHARSET=utf8; ");


$installer->run("
CREATE TABLE IF NOT EXISTS `ecomcharge_api_debug` (
  `debug_id` int(10) unsigned NOT NULL auto_increment,
  `transaction_id` varchar(255) NOT NULL default '',
  `debug_at` timestamp NOT NULL default CURRENT_TIMESTAMP on update CURRENT_TIMESTAMP,
  `request_body` text,
  `response_body` text,
  PRIMARY KEY  (`debug_id`),
  KEY `debug_at` (`debug_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

    ");
	
$installer->endSetup();


