<?
ini_set("display_errors","Off");
global $USER, $APPLICATION;
use Bitrix\Main\Page\Asset,
    Bitrix\Main\Page\AssetLocation,
    Bitrix\Main\Web\Json,
    Bitrix\Main\Localization\Loc;

if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
    die();


$this->setFrameMode(true);

$this->addExternalCss('/bitrix/components/eshoplogistic/button/css/styles.css');
$this->addExternalJs('/bitrix/components/eshoplogistic/button/js/script'.(mb_strtolower(LANG_CHARSET)!='utf-8'?'-1251':'').'.js');

$rsUser = CUser::GetByID($USER->GetID());
$arUser = $rsUser->Fetch();

$component = new EslButtonComponent();
$element = $component->getElementById($arParams['ELEMENT_ID']);
$ar_res = CCatalogProduct::GetByID($arParams['ELEMENT_ID']);
$curSKU = $arParams['ELEMENT_ID'];
if($element['offers_exists']){
    $curSKU = array_keys($element['offers']['offers'])[0];
}
?>

<button type="button"
        class="<?=$arParams['BUTTON_ONE_CLICK_CLASS']?> esl-button_modal esl-button_data"
        data-widget-button=""
        data-article="<?=$curSKU?>"
        data-id="<?=$arParams['ELEMENT_ID']?>"
        data-name="<?=$element['name']?>"
        data-price="<?=$element['price']?>"
        data-unit=""
        data-weight="1">
    <?=$arParams['BUTTON_ONE_CLICK']?>
</button>
<div id="eShopLogisticApp" data-key="<?=$arParams['ESL_WIDGET_KEY']?>"></div>

