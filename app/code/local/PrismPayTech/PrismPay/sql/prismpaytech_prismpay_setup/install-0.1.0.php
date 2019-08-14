<?php
$installer = $this;
$installer->startSetup();
$installer->run("
    CREATE TABLE IF NOT EXISTS `prismpay_customer_profile` (
                                `id` int(11) NOT NULL AUTO_INCREMENT,
                                `customer_id` varchar(50) NOT NULL,
                                `profile_id` varchar(50) NOT NULL,
                                `last_4_digit` varchar(20) NOT NULL,
                                PRIMARY KEY (`id`)
                              ) ENGINE=InnoDB  DEFAULT CHARSET=latin1;
                              
");
$installer->endSetup();

?>