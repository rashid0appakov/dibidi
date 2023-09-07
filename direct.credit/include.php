<?php
use Bitrix\Main;
use Bitrix\Main\Loader;
require dirname(__FILE__) ."/config.php";

Loader::registerAutoLoadClasses(
	$SBERBANK_CONFIG['MODULE_ID'],
	array(
        '\Sberbank\Payments\Gateway' => 'lib/rbs/Gateway.php',
	)
);

?>