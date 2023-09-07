<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();?>
<?$APPLICATION->IncludeComponent(
	"bitrix:map.yandex.view",
	"map",
	Array(
		"API_KEY" => "",
		"CONTROLS" => array("ZOOM","TYPECONTROL","SCALELINE"),
		"INIT_MAP_TYPE" => "MAP",
		"MAP_DATA" => "a:4:{s:10:\"yandex_lat\";d:55.794598431891025;s:10:\"yandex_lon\";d:49.140850077867995;s:12:\"yandex_scale\";i:10;s:10:\"PLACEMARKS\";a:1:{i:0;a:3:{s:3:\"LON\";d:49.140850077868;s:3:\"LAT\";d:55.787248009134;s:4:\"TEXT\";s:28:\"Магазин DiBiDiShop.ru\";}}}",
		"MAP_HEIGHT" => "100%",
		"MAP_ID" => "",
		"MAP_WIDTH" => "100%",
		"OPTIONS" => array("ENABLE_DBLCLICK_ZOOM","ENABLE_DRAGGING"),
		"USE_REGION_DATA" => "Y"
	)
);?>