<?
if(file_exists($_SERVER["DOCUMENT_ROOT"]."/local/php_interface/include/catalog_export/merchantgl_setup.php")){
	require($_SERVER["DOCUMENT_ROOT"]."/local/php_interface/include/catalog_export/merchantgl_setup.php");
}else{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/arturgolubev.gmerchant/load/googlemerchant_setup.php");
}
?>