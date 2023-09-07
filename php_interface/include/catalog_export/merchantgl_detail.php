<?
if(file_exists($_SERVER["DOCUMENT_ROOT"]."/local/php_interface/include/catalog_export/merchantgl_detail.php")){
	require($_SERVER["DOCUMENT_ROOT"]."/local/php_interface/include/catalog_export/merchantgl_detail.php");
}else{
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/arturgolubev.gmerchant/load/googlemerchant_detail.php");
}
?>