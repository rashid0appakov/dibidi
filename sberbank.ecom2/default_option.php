<?
require __DIR__ . '/config.php';

$sberbank_ecom2_default_option = array(
		'BANK_NAME' => $SBERBANK_CONFIG['BANK_NAME'],
		'MODULE_ID' => $SBERBANK_CONFIG['MODULE_ID'],
		'SBERBANK_PROD_URL' => $SBERBANK_CONFIG['SBERBANK_PROD_URL'],
		'SBERBANK_TEST_URL' => $SBERBANK_CONFIG['SBERBANK_TEST_URL'],
		'MODULE_VERSION' => $SBERBANK_CONFIG['MODULE_VERSION'],
		'ISO' => serialize($SBERBANK_CONFIG['ISO']),
		'RESULT_ORDER_STATUS' => 'FALSE',
		'OPTION_PHONE' => 'PHONE',
		'OPTION_EMAIL' => 'EMAIL',
		'OPTION_FIO' => 'FIO',
		'TAX_DEFAULT' => 0,
		'CALLBACK_ENABLED' => serialize(array()),
		'AUTO_REDIRECT_EXCEPTIONS' => serialize(array(
			'/personal/orders/'
		))
    );

?>