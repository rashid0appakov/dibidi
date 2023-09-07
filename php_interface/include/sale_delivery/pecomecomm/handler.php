<?php
namespace Sale\Handlers\Delivery;

use \Bitrix\Sale\Delivery\CalculationResult;
use \Bitrix\Sale\Delivery\Services\Base;
use \Bitrix\Main\Config\Option;
use \Bitrix\Main\ArgumentNullException;
use \Bitrix\Main\Localization\Loc;
use \Bitrix\Currency\CurrencyManager;
use \Bitrix\Main\Text\Encoding;
use \Bitrix\Main\Loader;
use Pec\Delivery\Tools;
use CUser;

Loc::loadMessages(__FILE__);

Loader::includeModule('pecom.ecomm');

class PecomEcommHandler extends Base
{
	protected $module_id = 'pecom.ecomm';

	public function __construct(array $initParams){
		if (!Option::get($this->module_id, "PEC_API_KEY", '') || !Option::get($this->module_id, "PEC_API_LOGIN", '')) {
			throw new ArgumentNullException('noSetOptionsModule');
		}

		$cost_not_in_order = Option::get($this->module_id, "PEC_COST_OUT", '1');
		if ($cost_not_in_order) {
			$initParams['NAME'] .= mb_convert_encoding(' (оплата доставки при получении)', SITE_CHARSET, 'utf-8');
		}

		parent::__construct($initParams);
	}

	public static function getClassTitle()
	{
		$title = mb_convert_encoding('Доставка ПЭК', SITE_CHARSET, 'utf-8');

		$cost_not_in_order = Option::get('pecom.ecomm', "PEC_COST_OUT", '1');
		if ($cost_not_in_order) {
			$title .= mb_convert_encoding(' (оплата доставки при получении)', SITE_CHARSET, 'utf-8');

		}

		return $title;
	}

	public static function getClassDescription()
	{
		return mb_convert_encoding('Доставка ПЭК (курьером и самовывоз)', SITE_CHARSET, 'utf-8');
	}

	protected function calculateConcrete(\Bitrix\Sale\Shipment $shipment)
	{
		$result = new CalculationResult();

		$cost_not_in_order = Option::get($this->module_id, "PEC_COST_OUT", 1);
		$arParams['SELF_PACK'] = Option::get($this->module_id, "PEC_SELF_PACK", 0);

		if ($_REQUEST['order']['pec_cost_delivery_error'] == 'Y' || $_REQUEST['pec_cost_delivery_error'] == 'Y') {
			$result->addError(new \Bitrix\Main\Error(Encoding::convertEncoding('Не удалось рассчитать стоимость', SITE_CHARSET, 'UTF-8')));
			$_SESSION['pec_post'] = ['cost_error' => true];
			return $result;
		} else {
			$_SESSION['pec_post']['cost_error'] = false;
		}

		$order = $shipment->getCollection()->getOrder();

		$arParams['VOLUME'] = 0;
		$basketProductCount = 0;
		$self_pack_prop = Option::get($this->module_id, "PEC_SELF_PACK_INPUT");

		foreach ($order->getBasket() as $item) {
			$itemQuantity = $item->getQuantity();
			$basketProductCount += $itemQuantity;
			if (!$arParams['SELF_PACK'] && $self_pack_prop) {
				$product_id = $item->getField('PRODUCT_ID');
				$fragile = \CIBlockElement::GetByID($product_id)->GetNextElement()->GetProperties()[$self_pack_prop]['VALUE'];
				if ($fragile == 'Y') {
					$arParams['SELF_PACK'] = 1;
				}
			}

			$dimensions = unserialize($item->getField('DIMENSIONS'));
			$itemVolume = 0;
			if ($dimensions['WIDTH'] != 0 && $dimensions['HEIGHT'] != 0 && $dimensions['LENGTH'] != 0)
				$itemVolume = Tools::calculateVolumeM3($dimensions);
			if (!$itemVolume) {
				$itemVolume = floatval(Option::get($this->module_id, "PEC_VOLUME", 0.001));
			}
			$arParams['VOLUME'] += $itemVolume * $itemQuantity;

			// Получаем габариты
			if (!$dimensions['WIDTH']) $dimensions['WIDTH'] = Option::get($this->module_id, "PEC_MAX_SIZE", 0.2)*1000;
			if (!$dimensions['HEIGHT']) $dimensions['HEIGHT'] = Option::get($this->module_id, "PEC_MAX_SIZE", 0.2)*1000;
			if (!$dimensions['LENGTH']) $dimensions['LENGTH'] = Option::get($this->module_id, "PEC_MAX_SIZE", 0.2)*1000;
			sort($dimensions);
//			$dimensions[0] = $dimensions[0]*$itemQuantity;
//			rsort($dimensions);
			if ($arParams['DIMENSION']['WIDTH'] < $dimensions[2] / 1000) $arParams['DIMENSION']['WIDTH'] = $dimensions[2] / 1000;
			if ($arParams['DIMENSION']['HEIGHT'] < $dimensions[1] / 1000) $arParams['DIMENSION']['HEIGHT'] = $dimensions[1] / 1000;
			if ($arParams['DIMENSION']['LENGTH'] < $dimensions[0] / 1000) $arParams['DIMENSION']['LENGTH'] = $dimensions[0] / 1000;
			// Получаем габариты
		}

//		if($arParams['VOLUME'] - $arParams['DIMENSION']['WIDTH']*$arParams['DIMENSION']['HEIGHT']*$arParams['DIMENSION']['LENGTH']) {
//			$arParams['DIMENSION']['WIDTH'] = round($arParams['VOLUME'] / ($arParams['DIMENSION']['HEIGHT']*$arParams['DIMENSION']['LENGTH']),2);
//			if($arParams['DIMENSION']['WIDTH'] > $arParams['DIMENSION']['HEIGHT']) {
//				$arParams['DIMENSION']['WIDTH'] += $arParams['DIMENSION']['HEIGHT'];
//				$arParams['DIMENSION']['HEIGHT'] = $arParams['DIMENSION']['WIDTH'] - $arParams['DIMENSION']['HEIGHT'];
//				$arParams['DIMENSION']['WIDTH'] -= $arParams['DIMENSION']['HEIGHT'];
//			}
//		}

		$arParams['VOLUME'] = $arParams['VOLUME'] ? round($arParams['VOLUME'], 2, PHP_ROUND_HALF_UP) : Option::get($this->module_id, "PEC_VOLUME", 0.001);

		$weight = (float)$shipment->getWeight() / 1000;
		$arParams['WEIGHT'] = $weight ? (float)$weight : Option::get($this->module_id, "PEC_WEIGHT", 0.05) * $basketProductCount;
		$arParams['PEC_SHOW_WIDGET'] = (int)Option::get($this->module_id, "PEC_SHOW_WIDGET");

		$props = $order->getPropertyCollection();

		// Если дополнительные склады
		$locProp = $props->getDeliveryLocation();
		if($locProp) {
			$locationCode = $locProp->getValue();
			if ($locationCode != '') {
				$pec_locations = \CSaleLocation::GetList([], ["CODE" => $locationCode, "LID" => LANGUAGE_ID]);
				if ($pec_loc = $pec_locations->Fetch()) {
					$option_id = "PEC_STORE_DOP";
					$sklads = is_array(unserialize(Option::get($this->module_id, $option_id))) ?
						unserialize(Option::get($this->module_id, $option_id)) : [];
					if(!empty($sklads)) {
						foreach($sklads as $sklad) {
							$locs [$sklad['parent_id']]= [
								'address' => $sklad['address'],
								'intake' => $sklad['intake'],
							];
						}
					}
					if (in_array($pec_loc['CITY_ID'], array_keys($locs))) {
						$dop_address = $locs[$pec_loc['CITY_ID']]['address'];
						$dop_intake = $locs[$pec_loc['CITY_ID']]['intake'];
					} elseif (in_array($pec_loc['REGION_ID'], array_keys($locs))) {
						$dop_address = $locs[$pec_loc['REGION_ID']]['address'];
						$dop_intake = $locs[$pec_loc['REGION_ID']]['intake'];
					}
				}
			}
		}
		if($dop_address) {
			$arParams['FROM_ADDRESS'] = $dop_address;
			$dop_intake ? $arParams['FROM_TYPE'] = 'store' : $arParams['FROM_TYPE'] = 'pzz';
		} else {
			$arParams['FROM_ADDRESS'] = Option::get($this->module_id, "PEC_STORE_ADDRESS", '');
			$arParams['FROM_TYPE'] = Option::get($this->module_id, "PEC_STORE_PZZ", '');
		}
		$arParams['transportationType'] = Tools::getTransportTypeWidget();

		if (Option::get($this->module_id, "PEC_SAFE_PRICE", '0')) {
			$arParams['PRICE'] = $order->getPrice();
		} else {
			$arParams['PRICE'] = $arParams['WEIGHT'] * 100;
		}
		$arParams['PRICE'] = round($arParams['PRICE'], 0);

		$locations = self::getAddressByCode($props);

		$zipProp = $props->getDeliveryLocationZip();

		global $USER;
		$arParams['ADDRESS'] = '';
		$deliveryPrice = $deliveryDays = 0;
		$rsUser = CUser::GetByID($USER->GetID());
		$arUser = $rsUser->Fetch();
		if (Option::get($this->module_id, "PEC_GET_USER_ADDRESS", '') == 'personal') {
			$arParams['ADDRESS'] = $arUser['PERSONAL_CITY'];
		}
		else if (Option::get($this->module_id, "PEC_GET_USER_ADDRESS", '') == 'work') {
			$arParams['ADDRESS'] = $arUser['WORK_CITY'];
		}
		if (empty($arParams['ADDRESS'])) {
			if ($_REQUEST['order']['pec_address_selected']) {
				$_SESSION['pec_post']['address_selected'] = $_REQUEST['order']['pec_address_selected'];
				$arParams['ADDRESS'] = $_REQUEST['order']['pec_address'];
				$fullAddress = json_decode($_REQUEST['order']['pec_widget_data'], true);
				if (isset($fullAddress['toDepartmentData']['Addresses'][0]['address']['RawAddress']))
					$_SESSION['pec_post']['full_address'] = $fullAddress['toDepartmentData']['Addresses'][0]['address']['RawAddress'];
			} else {
				if($zipProp) {
					$arParams['ZIP'] = $zipProp->getValue();
				}
				$addr = isset($locations) ? $locations['NAME_RU'] : '';

				$arParams['ADDRESS'] =  $addr ? : $arParams['FROM_ADDRESS'];
			}
		}

		// API PEC
		if (!isset($_REQUEST['order']['pec_price']) && !empty($_REQUEST)) {
			if (!isset($_REQUEST['pec_price'])) {
				$data = [];
				switch ($arParams['transportationType']) {
					case 'auto':
					case 'avia':
						$data['transport'] = $arParams['transportationType'];
						$data['transportationType'] = 'regular';
						break;
					case 'easyway':
						$data['transport'] = 'auto';
						$data['transportationType'] = $arParams['transportationType'];
						break;
				}

				$deliveryType = $arParams['FROM_TYPE'] == 'store' ? 'address' : 'department';
				$data['needPackingRigid'] = $arParams['SELF_PACK'];
				$data['cargo']['volume'] = $arParams['VOLUME'];
				$data['cargo']['weight'] = $arParams['WEIGHT'];
				$data['cargo']['declaredAmount'] = $arParams['PRICE'];

				$data['direction']['from']['address'] = $arParams['FROM_ADDRESS'];
				$data['direction']['from']['coords'] = null;
				$data['direction']['from']['type'] = $deliveryType;
				$data['direction']['from']['department'] = null;

				$data['direction']['to']['address'] = $arParams['ADDRESS'];
				$data['direction']['to']['coords'] = null;
				$data['direction']['to']['type'] = 'department';
				$data['direction']['to']['department'] = null;

				$body = json_encode($data);
				$key = md5($body);
				$resultPost = $_SESSION['pecom_address'.$key];
				if (!$resultPost) {
					$resultPost = Tools::getPecPrice($data);
					$_SESSION['pecom_address'.$key] = $resultPost;
				}

				$deliveryPrice = round($resultPost->result->price, 0);
				$deliveryDays = mb_convert_encoding($resultPost->result->term->days, SITE_CHARSET, 'utf-8');
			} else {
				$deliveryPrice = round($_REQUEST['pec_price'], 0);
				$deliveryDays = mb_convert_encoding($_REQUEST['pec_days'], SITE_CHARSET, 'utf-8');
			}
		} else {
			$deliveryPrice = round($_REQUEST['order']['pec_price'], 0);
			$deliveryDays = mb_convert_encoding($_REQUEST['order']['pec_days'], SITE_CHARSET, 'utf-8');
		}

		$arParams['MAIN'] = $_SESSION['MAIN'] = self::getMarginPrice();

		if (!$cost_not_in_order) {
			$arParams['PEC_COST_OUT'] = 0;
			if ($deliveryPrice >= 0) {
				$arParams['PEC_PRICE'] = $deliveryPrice;
				$_SESSION['pec_post']['price'] = $deliveryPrice;
			} elseif ($_SESSION['pec_post']['price']) {
				$arParams['PEC_PRICE'] = $_SESSION['pec_post']['price'];
				$deliveryPrice = $_SESSION['pec_post']['price'];
			}
			if ($deliveryDays) {
				$arParams['PEC_DAYS'] = $deliveryDays;
				$_SESSION['pec_post']['days'] = $deliveryDays;
			} elseif ($_SESSION['pec_post']['days']) {
				$arParams['PEC_DAYS'] = $_SESSION['pec_post']['days'];
			}
		} else {
			if ($deliveryPrice) {
				$_SESSION['pec_post']['price'] = $deliveryPrice;
			}
			if ($deliveryDays) {
				$_SESSION['pec_post']['days'] = $deliveryDays;
			}
			$arParams['PEC_COST_OUT'] = 1;
			$deliveryPrice = 0;
			$arParams['cost_title'] = mb_convert_encoding('(без учета доставки)', SITE_CHARSET, 'utf-8');
			$arParams['cost_text'] = mb_convert_encoding('(оплата в ТК ПЭК)', SITE_CHARSET, 'utf-8');
		}

		if ($_REQUEST['order']['pec_to_address']) {
			$_SESSION['pec_post']['to_address'] = Encoding::convertEncoding($_REQUEST['order']['pec_to_address'], SITE_CHARSET, 'UTF-8');
		}
		if ($_REQUEST['order']['pec_to_uid']) {
			$_SESSION['pec_post']['to_uid'] = $_REQUEST['order']['pec_to_uid'];
		}
		if ($_REQUEST['order']['pec_to_type']) {
			$_SESSION['pec_post']['to_type'] = $_REQUEST['order']['pec_to_type'];
		}
		$arParams['deliveryId'] = $shipment->getDeliveryId();

		$jsContent['address'] = mb_convert_encoding('Адрес доставки', SITE_CHARSET, 'utf-8');
		$jsContent['change'] = mb_convert_encoding('изменить', SITE_CHARSET, 'utf-8');
		$jsContent['address_to'] = mb_convert_encoding('Адрес: ', SITE_CHARSET, 'utf-8');
		$jsContent['term'] = mb_convert_encoding('Срок: от ', SITE_CHARSET, 'utf-8');
		$jsContent['btn'] = mb_convert_encoding('Выбрать адрес доставки', SITE_CHARSET, 'utf-8');
		$jsContent['error'] = mb_convert_encoding('срок загрузки виджета вышел', SITE_CHARSET, 'utf-8');


		$main = $arParams['MAIN'];
		$marginPrice = ($main['marginType'] === '%') ? $main['marginValue']/100 * $deliveryPrice : $main['marginValue'];
		$arParams['PEC_PRICE'] += $marginPrice;

		$arParams['text'] = $jsContent;

		$_SESSION['pec_post']['arParams'] = $arParams;

		if (isset($_REQUEST['action']) && $_REQUEST['action'] == 'changeDeliveryService') {

			$data = [];
			switch ($arParams['transportationType']) {
				case 'auto':
				case 'avia':
					$data['transport'] = $arParams['transportationType'];
					$data['transportationType'] = 'regular';
					break;
				case 'easyway':
					$data['transport'] = 'auto';
					$data['transportationType'] = $arParams['transportationType'];
					break;
			}

			$collection = $order->getPropertyCollection();
			$addressProp = self::getAddressByCode($collection);

			$deliveryType = $arParams['FROM_TYPE'] == 'store' ? 'address' : 'department';
			$data['needPackingRigid'] = $arParams['SELF_PACK'];
			$data['cargo']['volume'] = $arParams['VOLUME'];
			$data['cargo']['weight'] = $arParams['WEIGHT'];
			$data['cargo']['declaredAmount'] = $arParams['PRICE'];

			$data['direction']['from']['address'] = $arParams['FROM_ADDRESS'];
			$data['direction']['from']['coords'] = null;
			$data['direction']['from']['type'] = $deliveryType;
			$data['direction']['from']['department'] = null;

			$data['direction']['to']['address'] = $addressProp['NAME_RU'];
			$data['direction']['to']['coords'] = null;
			$data['direction']['to']['type'] = $_REQUEST['order']['pec_to_type'];
			$data['direction']['to']['department'] = null;

			$resultPost = Tools::getPecPrice($data);

			$price = $resultPost->result->price;
			$price += ($arParams['MAIN']['marginType'] == '%') ? $price * $arParams['MAIN']['marginValue']/100 : $arParams['MAIN']['marginValue'];
			$deliveryPrice = round($price, 0);
			$deliveryDays = mb_convert_encoding($resultPost->result->term->days, SITE_CHARSET, 'utf-8');
		}
		if ($deliveryPrice > 0)
			$deliveryPrice += $marginPrice;
		$result->setDeliveryPrice(roundEx($deliveryPrice));

		if ($deliveryDays)
			$result->setPeriodDescription($deliveryDays);

		return $result;
	}

	public function isCalculatePriceImmediately()
	{
		return true;
	}

	public static function whetherAdminExtraServicesShow()
	{
		return true;
	}

	protected function getConfigStructure(): array
	{

		$result = array(
			"MAIN" => array(
				"TITLE" => Loc::getMessage("SALE_DLVR_HANDL_AUT_HANDLER_SETTINGS"),
				"DESCRIPTION" => Loc::getMessage("SALE_DLVR_HANDL_AUT_HANDLER_SETTINGS_DSCR"),
			)
		);

		$serviceCurrency = $this->currency;

		if (Loader::includeModule('currency')) {
			$currencyList = CurrencyManager::getCurrencyList();

			if (isset($currencyList[$this->currency]))
				$serviceCurrency = $currencyList[$this->currency];

			unset($currencyList);
		}

		$marginTypes = array(
			"%" => "%",
			"CURRENCY" => $serviceCurrency
		);

		$result["MAIN"]["ITEMS"]["MARGIN_VALUE"] = array(
			"TYPE" => "STRING",
			"NAME" => Loc::getMessage("SALE_DLVR_HANDL_AUT_MARGIN_VALUE"),
			"DEFAULT" => 0
		);

		$result["MAIN"]["ITEMS"]["MARGIN_TYPE"] = array(
			"TYPE" => "ENUM",
			"NAME" => Loc::getMessage("SALE_DLVR_HANDL_AUT_MARGIN_TYPE"),
			"DEFAULT" => "%",
			"OPTIONS" => $marginTypes
		);

		$configProfileIds = array();

		if (isset($this->oldConfig["CONFIG_GROUPS"])) {
			$groupProfileIds = array_keys($this->oldConfig["CONFIG_GROUPS"]);
			$intersect = array_intersect($groupProfileIds, $configProfileIds);

			foreach ($intersect as $pid)
				unset($this->oldConfig["CONFIG_GROUPS"][$pid]);
		}

		return $result;
	}

	protected function getMarginPrice(): array
	{
		return [
			'marginType' => $this->config["MAIN"]["MARGIN_TYPE"],
			'marginValue' => intval($this->config["MAIN"]["MARGIN_VALUE"]),
		];
	}

	function getAddressByCode($props) {
		$locProp = $props->getDeliveryLocation();
		if($locProp) {
			$locationCode = $locProp->getValue();
			if($locationCode != '') {
				return $items = \Bitrix\Sale\Location\LocationTable::getByCode($locationCode, array(
					'filter' => array('=NAME.LANGUAGE_ID' => LANGUAGE_ID),
					'select' => array('NAME_RU' => 'NAME.NAME')
				))->fetch();
			}
		}
	}
}
