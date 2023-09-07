<?php

include dirname(__FILE__) . "/install/version.php";

$SBERBANK_CONFIG = array(
	'MODULE_ID' => 'sberbank.ecom2',
	'BANK_NAME' => 'Sberbank',
	'SBERBANK_PROD_URL' => 'https://securepayments.sberbank.ru/payment/rest/',
	'SBERBANK_TEST_URL' => 'https://3dsec.sberbank.ru/payment/rest/',
	'ISO' => array(
	    'USD' => 840,
	    'EUR' => 978,
	    'RUB' => 643,
	    'RUR' => 643,
	    'BYN' => 933
	),
	'MODULE_VERSION' => $arModuleVersion['VERSION'],
	'CALLBACK_BROADCAST' => false
);

