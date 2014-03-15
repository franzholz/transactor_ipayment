<?php

/***************************************************************
 * Extension Manager/Repository config file for ext "transactor_ipayment".
 ***************************************************************/


$EM_CONF[$_EXTKEY] = array(
	'title' => 'Transactor iPayment Gateway',
	'description' => 'Provides the possibility to transact payments via iPayment using the Payment Transactor extension.',
	'category' => 'misc',
	'author' => 'Franz Holzinger',
	'author_email' => 'franz@ttproducts.de',
	'shy' => '',
	'dependencies' => 'transactor',
	'conflicts' => '',
	'priority' => '',
	'module' => '',
	'state' => 'stable',
	'internal' => '',
	'uploadfolder' => 0,
	'createDirs' => '',
	'modify_tables' => '',
	'clearCacheOnLoad' => 0,
	'lockType' => '',
	'author_company' => '',
	'version' => '0.0.2',
	'constraints' => array(
		'depends' => array(
			'typo3' => '4.5.0-6.2.0',
			'php' => '5.2.0-0.0.0',
			'transactor' => '0.1.0-',
		),
		'conflicts' => array(
		),
		'suggests' => array(
		),
	),
	'_md5_values_when_last_written' => 'a:11:{s:9:"ChangeLog";s:4:"a0ce";s:10:"README.txt";s:4:"ee2d";s:39:"class.tx_transactoripayment_gateway.php";s:4:"83bb";s:21:"ext_conf_template.txt";s:4:"1758";s:12:"ext_icon.gif";s:4:"f2b9";s:17:"ext_localconf.php";s:4:"2307";s:13:"locallang.php";s:4:"5bc0";s:18:"paymentmethods.xml";s:4:"f83d";s:14:"doc/manual.sxw";s:4:"8c3e";s:45:"model/class.tx_transactoripayment_gateway.php";s:4:"06b8";s:23:"res/iPayment-header.jpg";s:4:"ab89";}',
	'suggests' => array(
	),
);

?>
