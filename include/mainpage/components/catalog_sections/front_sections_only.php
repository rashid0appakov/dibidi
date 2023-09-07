<?if(!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true)
{
	define("STATISTIC_SKIP_ACTIVITY_CHECK", "true");
	require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");
}?>
<?$APPLICATION->IncludeComponent(
	"aspro:wrapper.block.max", 
	"front_sections_only", 
	array(
		"IBLOCK_TYPE" => "1c_catalog",
		"IBLOCK_ID" => "53",
		"FILTER_NAME" => "arrPopularSections",
		"COMPONENT_TEMPLATE" => "front_sections_only",
		"SECTION_ID" => "",
		"SECTION_CODE" => "",
		"CACHE_TYPE" => "A",
		"CACHE_TIME" => "36000000",
		"CACHE_FILTER" => "Y",
		"CACHE_GROUPS" => "Y",
		"TITLE_BLOCK" => "Каталог товаров",
		"TITLE_BLOCK_ALL" => "Весь каталог",
		"ALL_URL" => "catalog/",
		"VIEW_MODE" => "",
		"VIEW_TYPE" => "type1",
		"NO_MARGIN" => "Y",
		"FILLED" => "N",
		"COMPOSITE_FRAME_MODE" => "A",
		"COMPOSITE_FRAME_TYPE" => "AUTO",
		"TOP_DEPTH" => "1",
		"SHOW_ICONS" => "N",
		"SHOW_SUBSECTIONS" => "N",
		"SCROLL_SUBSECTIONS" => "N",
		"INCLUDE_FILE" => ""
	),
	false
);?>