<?php

namespace Sale\Handlers\PaySystem;

use Bitrix\Main;
use Bitrix\Main\Error;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Request;
use Bitrix\Main\Text\Encoding;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Web;
use Bitrix\Sale\BusinessValue;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\Payment;
use Bitrix\Sale\Order;
use Bitrix\Main\Loader;
use Bitrix\Main\Config\Option;

IncludeModuleLangFile(__FILE__);
require dirname(dirname(__FILE__)) . '/config.php';
Loader::includeModule( 'sberbank.ecom2' );

/**
 * Class SberbankEcomHandler
 * @package Sale\Handlers\PaySystem
 */
class sberbank_ecom2Handler extends PaySystem\ServiceHandler implements PaySystem\IPrePayable
{
	/**
	 * @param Payment $payment
	 * @param Request|null $request
	 * @return PaySystem\ServiceResult
	 */
	public function initiatePay(Payment $payment, Request $request = null)
	{
		$moduleId = 'sberbank.ecom2';
		
		$RBS_Gateway = new \Sberbank\Payments\Gateway;

		
		// module settings
		$RBS_Gateway->setOptions(array(
			'module_id' => Option::get($moduleId, 'MODULE_ID'),
			'gate_url_prod' => Option::get($moduleId, 'SBERBANK_PROD_URL'),
			'gate_url_test' => Option::get($moduleId, 'SBERBANK_TEST_URL'),
			'module_version' => Option::get($moduleId, 'MODULE_VERSION'),
			'iso' => unserialize(Option::get($moduleId, 'ISO')),
			'cms_version' => 'Bitrix ' . SM_VERSION,
			'language' => 'ru',
			'default_cartItem_tax' => Option::get($moduleId, 'TAX_DEFAULT'),
			// 'additionalOfdParams' => array(
				// 'agent_info.type' => 6,
				// 'agent_info.paying.operation' => '',
				// 'agent_info.paying.phones' => '',
				// 'agent_info.paymentsOperator.phones' => '',
				// 'agent_info.MTOperator.address' => '',
				// 'agent_info.MTOperator.inn' => '',
				// 'agent_info.MTOperator.name' => '',
				// 'agent_info.MTOperator.phones' => '',
				// 'supplier_info.inn' => '',
				// 'supplier_info.name' => '',
				// 'supplier_info.phones' => '',
				// 'cashier' => '',
				// 'additional_check_props' => '',
				// 'additional_user_props.name' => '',
				// 'additional_user_props.value' => '',
			// ),
		));

		// handler settings
		$RBS_Gateway->setOptions(array(
			'ofd_tax' => $this->getBusinessValue($payment, 'SBERBANK_OFD_TAX_SYSTEM') == 0 ? 0 : $this->getBusinessValue($payment, 'SBERBANK_OFD_TAX_SYSTEM'),
			'ofd_enabled' => $this->getBusinessValue($payment, 'SBERBANK_OFD_RECIEPT')  == 'Y' ? 1 : 0,
			//	'ffd_version' => $this->getBusinessValue($payment, 'SBERBANK_FFD_VERSION'),
			'ffd_payment_object' => $this->getBusinessValue($payment, 'SBERBANK_FFD_PAYMENT_OBJECT'),
			'ffd_payment_object_delivery' => $this->getBusinessValue($payment, 'SBERBANK_FFD_PAYMENT_OBJECT_DELIVERY'),
			'ffd_payment_method' => $this->getBusinessValue($payment, 'SBERBANK_FFD_PAYMENT_METHOD'),
			'ffd_payment_method_delivery' => $this->getBusinessValue($payment, 'SBERBANK_FFD_PAYMENT_METHOD_DELIVERY'),
			'test_mode' => $this->getBusinessValue($payment, 'SBERBANK_GATE_TEST_MODE') == 'Y' ? 1 : 0,
			'handler_two_stage' => $this->getBusinessValue($payment, 'SBERBANK_HANDLER_TWO_STAGE') == 'Y' ? 1 : 0,
            'handler_logging' => $this->getBusinessValue($payment, 'SBERBANK_HANDLER_LOGGING') == 'Y' ? 1 : 0,
		));

		$RBS_Gateway->buildData(array(
			'orderNumber' => $this->getBusinessValue($payment, 'SBERBANK_ORDER_NUMBER') . '_' . $payment->getField('ID'),
		    'amount' => $this->getBusinessValue($payment, 'SBERBANK_ORDER_AMOUNT'),
		    'userName' => $this->getBusinessValue($payment, 'SBERBANK_GATE_LOGIN'),
		    'password' => $this->getBusinessValue($payment, 'SBERBANK_GATE_PASSWORD'),
		    'description' => $this->getOrderDescription($payment,'SBERBANK_ORDER_DESCRIPTION'),
		));

		$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== "off" ? 'https://' : 'http://';
		$domain_name = strtok($_SERVER['HTTP_HOST'], ":");
		
		if(strlen($domain_name) < 3) {
			$domain_name = $_SERVER['SERVER_NAME'];
		}
		if(strlen($domain_name) < 3) {
			$domain_name = Option::get($moduleId, 'NOTIFY_URL', '');
		}

		if(strlen($domain_name) > 3) {
			$RBS_Gateway->setOptions(
				array(
					'domain_finded' => true,
					'callback_url' => html_entity_decode($protocol . $domain_name . '/bitrix/tools/sale_ps_result.php?PAYMENT=SBERBANK&OPERATION_CALLBACK=SBER')
				)
			);
		}

		$RBS_Gateway->buildData(array(
		    'returnUrl' => $protocol . $domain_name . '/bitrix/tools/sale_ps_result.php' . '?PAYMENT=SBERBANK&ORDER_ID=' . $payment->getField('ORDER_ID') . '&PAYMENT_ID=' . $payment->getField('ID')
		));

		$Order = Order::load($payment->getOrderId());
		$propertyCollection = $Order->getPropertyCollection();
		
		$phone_key = strlen(Option::get($moduleId, 'OPTION_PHONE')) > 0 ? Option::get($moduleId, 'OPTION_PHONE') : 'PHONE';
		$email_key = strlen(Option::get($moduleId, 'OPTION_EMAIL')) > 0 ? Option::get($moduleId, 'OPTION_EMAIL') : 'EMAIL';
		$fio_key = strlen(Option::get($moduleId, 'OPTION_FIO')) > 0 ? Option::get($moduleId, 'OPTION_FIO') : 'FIO';
		
		$phone = preg_replace('/\D+/', '', $this->getPropertyValueByCode($propertyCollection, $phone_key));

		if(substr($phone, 0, 1) == '7') {
			$phone = '+'.$phone;
		}
		if(substr($phone, 0, 1) == '8') {
		    $phone[0] = '7';
			$phone = '+' . $phone;
		}

		$RBS_Gateway->setOptions(array(
			'customer_name' => $this->getPropertyValueByCode($propertyCollection, $fio_key),
			'customer_email' => $this->getPropertyValueByCode($propertyCollection, $email_key),
			'customer_phone' => $phone,
		));

		if ($RBS_Gateway->ofdEnable()) {

			
			$Basket = $Order->getBasket();
			$basketItems = $Basket->getBasketItems();


			$lastIndex = 0;
			foreach ($basketItems as $key => $BasketItem) {
				$lastIndex = $key + 1;
				$position = array(
		            'positionId' => $key,
		            'itemCode' => $BasketItem->getProductId(),
		            'name' => str_replace("\n", "", mb_substr($BasketItem->getField('NAME'), 0, 120)),
		            'itemAmount' => $BasketItem->getFinalPrice(),
		            'itemPrice' => $BasketItem->getPrice(),
		            'quantity' => array(
		                'value' => $BasketItem->getQuantity(),
		                'measure' => $BasketItem->getField('MEASURE_NAME') ? $BasketItem->getField('MEASURE_NAME') : Loc::getMessage('SBERBANK_PAYMENT_FIELD_MEASURE'),
		            ),
		            'tax' => array(
		                'taxType' =>  $RBS_Gateway->getTaxCode( $BasketItem->getField('VAT_RATE') * 100 ),
		            ),
		        );

				// If need support suplier_info
				// $position['supplier_info'] = array(
				// 	'name' => 'value_sup_name',
				// 	'inn' => '9998887771',
				// );


				$RBS_Gateway->setPosition($position);
			}

			if($Order->getField('PRICE_DELIVERY') > 0) {
				
				Loader::includeModule('catalog');
				$deliveryInfo = \Bitrix\Sale\Delivery\Services\Manager::getById($Order->getField('DELIVERY_ID'));

				$deliveryVatItem = \CCatalogVat::GetByID($deliveryInfo['VAT_ID'])->Fetch();
				$RBS_Gateway->setOptions(array(
				    'delivery' => true,
				));
				$RBS_Gateway->setPosition(array(
		            'positionId' => $lastIndex + 1,
		            'itemCode' => 'DELIVERY_' . $Order->getField('DELIVERY_ID'),
		            'name' => Loc::getMessage('SBERBANK_PAYMENT_FIRLD_DELIVERY'),
		            'itemAmount' => $Order->getField('PRICE_DELIVERY'),
		            'itemPrice' => $Order->getField('PRICE_DELIVERY'),
		            'quantity' => array(
		                'value' => 1,
		                'measure' => Loc::getMessage('SBERBANK_PAYMENT_FIELD_MEASURE'),
		            ),
		            'tax' => array(
		                'taxType' => $RBS_Gateway->getTaxCodeDelivery($deliveryVatItem['RATE']),
		            ),
		        ));	
			}
		}

		$gateResponse = $RBS_Gateway->registerOrder();
		$params = array(
	        'sberbank_result' => $gateResponse,
	        'payment_link' => $RBS_Gateway->getPaymentLink(),
	        'currency' => $payment->getField('CURRENCY'),
	        'auto_redirect_exceptions' => unserialize(Option::get($moduleId, 'AUTO_REDIRECT_EXCEPTIONS')),
	    );
	    $this->setExtraParams($params);

	    $result = $this->showTemplate($payment, "payment");
        if(method_exists($result,'setPaymentUrl')) {
        	$result->setPaymentUrl($RBS_Gateway->getPaymentLink());
		}

        return $result;
	}

	public function processRequest(Payment $payment, Request $request)
	{
		global $APPLICATION;
		$moduleId = 'sberbank.ecom2';
		$result = new PaySystem\ServiceResult();

		$RBS_Gateway = new \Sberbank\Payments\Gateway;
		$RBS_Gateway->setOptions(array(
			// module settings
			'gate_url_prod' => Option::get($moduleId, 'SBERBANK_PROD_URL'),
			'gate_url_test' => Option::get($moduleId, 'SBERBANK_TEST_URL'),
			'test_mode' => $this->getBusinessValue($payment, 'SBERBANK_GATE_TEST_MODE') == 'Y' ? 1 : 0,
			'callback_redirect' => $request->get('CALLBACK_REDIRECT') == 1 ? 1 : 0
		));

		$RBS_Gateway->buildData(array(
		    'userName' => $this->getBusinessValue($payment, 'SBERBANK_GATE_LOGIN'),
		    'password' => $this->getBusinessValue($payment, 'SBERBANK_GATE_PASSWORD'),
		    'orderId' => $request->get('CALLBACK_REDIRECT') == 1 ? $request->get('mdOrder') : $request->get('orderId'),
		));
		
		$gateResponse = $RBS_Gateway->checkOrder();

		$resultId = explode("_", $gateResponse['orderNumber'] );
        array_pop($resultId);
        $res_payment_id = array_pop($resultId);
        $resultId = implode('_', $resultId);

        $successPayment = true;
        $change_order_status_logic = true;

        if($resultId != $this->getBusinessValue($payment, 'SBERBANK_ORDER_NUMBER')) {
			$successPayment = false;
		}

        if($gateResponse['errorCode'] != 0 || ($gateResponse['orderStatus'] != 1 && $gateResponse['orderStatus'] != 2)) {
        	$successPayment = false;
        }


        if($successPayment && !$payment->isPaid() && $request->get('CALLBACK_REDIRECT') == 1) {
			$inputJson = self::encode($request->toArray());
			
			$fields = array(
				'PS_INVOICE_ID' => $request->get('CALLBACK_REDIRECT') == 1 ? $request->get('mdOrder') : $request->get('orderId'),
				"PS_STATUS_CODE" => $gateResponse['orderStatus'],
				"PS_STATUS_DESCRIPTION" => $gateResponse["cardAuthInfo"]["pan"] . ";" . $gateResponse['cardAuthInfo']["cardholderName"],
				"PS_SUM" => $gateResponse["amount"] / 100,
				"PS_STATUS" => 'Y',
				"PS_CURRENCY" => $gateResponse["currency"],
				"PS_RESPONSE_DATE" => new DateTime()
			);

			$result->setOperationType(PaySystem\ServiceResult::MONEY_COMING);
			$result->setPsData($fields);

        	$order = Order::load($payment->getOrderId());

        	// set order status
        	$option_order_status = Option::get($moduleId, 'RESULT_ORDER_STATUS');

        	if($option_order_status != 'FALSE') {
	        	$statuses = array();
				$dbStatus = \CSaleStatus::GetList(Array("SORT" => "ASC"), Array("LID" => LANGUAGE_ID), false, false, Array("ID", "NAME", "SORT"));
				while ($arStatus = $dbStatus->GetNext()) {
				    $statuses[$arStatus["ID"]] = "[" . $arStatus["ID"] . "] " . $arStatus["NAME"];
				}

				if(array_key_exists($option_order_status, $statuses)) {
					$order->setField('STATUS_ID', $option_order_status);
				} else {
					echo '<span style="display:block; font-size:16px; display:block; color:red;padding:20px 0;">ERROR! CANT CHANGE ORDER STATUS</span>';
				}
			}

			// set delivery status
			if($this->getBusinessValue($payment, 'SBERBANK_HANDLER_SHIPMENT') == 'Y') {
				$shipmentCollection = $order->getShipmentCollection();
				foreach ($shipmentCollection as $shipment){
				    if (!$shipment->isSystem()) {
		        		$shipment->allowDelivery();
				    }
		    	}
	    	}

		    $order->save();
        } 


        if($request->get('CALLBACK_REDIRECT') == 1) {
        	require dirname(dirname(__FILE__)) . '/config.php';
        	if($SBERBANK_CONFIG['CALLBACK_BROADCAST']) {
	        	$broadcast_url = Option::get($moduleId, 'CALLBACK_REDIRECT_BROADCAST', '');
				$queryParams = $request->getRequestMethod() == 'GET' ? $request->getQueryList()->toArray() : $request->getPostList()->toArray();
				if(strlen($broadcast_url) > 5) {
					$RBS_Gateway->broadcast_callback($broadcast_url, $queryParams);
				}
			}

        	return $result;
        }

        $returnPage = $this->getBusinessValue($payment, 'SBERBANK_RETURN_URL');
        $failPage = $this->getBusinessValue($payment, 'SBERBANK_FAIL_URL');

        if(strlen($returnPage) > 4 && $successPayment) {
        	echo "<script>window.location='" .$this->getOrderDescription($payment,'SBERBANK_RETURN_URL')."'</script>";
        } else if(strlen($failPage) > 4 && !$successPayment) {
        	echo "<script>window.location='" .$this->getOrderDescription($payment,'SBERBANK_FAIL_URL')."'</script>";
        } else {
			self::printResultText($payment,$successPayment);
        } 

       
        return $result;
	}

	public function getPaymentIdFromRequest(Request $request)
	{
	    $paymentId = $request->get('PAYMENT_ID');
	    return intval($paymentId);
	}

	public function getCurrencyList()
	{
		return array('RUB','EUR','USD','UAH','BYN');
	}

	public static function getIndicativeFields()
	{
		return array('PAYMENT' => 'SBERBANK');
	}

	static protected function isMyResponseExtended(Request $request, $paySystemId)
	{
		global $APPLICATION;

		$RBS_Gateway = new \Sberbank\Payments\Gateway;

		if($request->get('OPERATION_CALLBACK') == 'SBER' && $request->get('CALLBACK_REDIRECT') != 1) {
			
			if(!$request->get('orderNumber')) {
				$RBS_Gateway->baseLogger('CALLBACK_RETURN', 'CALLBACK', $request->getQueryList(), [],'ERROR PROCESSING CALLBACK');
				return false;
			}

			$arrOrderIds = explode("_", $request->get('orderNumber') );
			array_pop($arrOrderIds);
			$R_PAYMENT_ID = array_pop($arrOrderIds);
			$R_ORDER_ID = implode('_', $arrOrderIds);

			if(!$R_ORDER_ID) {
				return false;
			}

			$order = is_numeric($R_ORDER_ID) ? Order::load($R_ORDER_ID) : false;

			if($order) {
				$paymentCollection = $order->getPaymentCollection();
				foreach ($paymentCollection as $payment) {
					if($R_PAYMENT_ID == $payment->getId()) {
						LocalRedirect($APPLICATION->GetCurUri("ORDER_ID=" . $payment->getOrderId() . "&PAYMENT_ID=" . $R_PAYMENT_ID . "&CALLBACK_REDIRECT=1"));
					}
				}
			}

			$order = Order::loadByAccountNumber($R_ORDER_ID);
			if($order) {
				$paymentCollection = $order->getPaymentCollection();
				foreach ($paymentCollection as $payment) {
					if($R_PAYMENT_ID == $payment->getId()) {
						LocalRedirect($APPLICATION->GetCurUri("ORDER_ID=" . $payment->getOrderId() . "&PAYMENT_ID=" . $R_PAYMENT_ID . "&CALLBACK_REDIRECT=1"));
					}
				}
			} 
			if(!$order) {
				$RBS_Gateway->baseLogger('CALLBACK_RETURN', 'CALLBACK', $request->getQueryList(), [],'ERROR PROCESSING CALLBACK');
				return false;
			}
	    	return false;
		}


		if(!$request->get('ORDER_ID')) {
			return false;
		}

		$order = is_numeric($request->get('ORDER_ID')) ? Order::load($request->get('ORDER_ID')) : false;

		if(!$order) {
			$order = Order::loadByAccountNumber($request->get('ORDER_ID'));
		} 
		if(!$order) {
			echo Loc::getMessage('RBS_MESSAGE_ERROR_BAD_ORDER');
			return false;
		}

		$paymentIds = $order->getPaymentSystemId();
		return in_array($paySystemId, $paymentIds);
	}

	private function getPropertyValueByCode($propertyCollection, $code) {
		$property = '';
		foreach ($propertyCollection as $property)
	    {
	        if($property->getField('CODE') == $code)
	            return $property->getValue();
	    }
	}


	/**
	 * @return array
	 */
	protected function getUrlList()
	{
		return array(

		);
	}
	/**
	 * @return array
	 */
	public function getProps()
	{
		$data = array();

		return $data;
	}
	/**
	 * @param Payment $payment
	 * @param Request $request
	 * @return bool
	 */
	public function initPrePayment(Payment $payment = null, Request $request)
	{
		return true;
	}
	/**
	 * @param array $orderData
	 */
	public function payOrder($orderData = array())
	{

	}
	/**
	 * @param array $orderData
	 * @return bool|string
	 */
	public function BasketButtonAction($orderData = array())
	{
		return true;
	}
	/**
	 * @param array $orderData
	 */
	public function setOrderConfig($orderData = array())
	{
		if ($orderData)
			$this->prePaymentSetting = array_merge($this->prePaymentSetting, $orderData);
	}

	protected function getOrderDescription(Payment $payment, $PROP_CODE)
	{
		/** @var PaymentCollection $collection */
		$collection = $payment->getCollection();
		$order = $collection->getOrder();
		$userEmail = $order->getPropertyCollection()->getUserEmail();

		$description =  str_replace(
			array(
				'#PAYMENT_NUMBER#',
				'#ORDER_NUMBER#',
				'#PAYMENT_ID#',
				'#ORDER_ID#',
				'#USER_EMAIL#'
			),
			array(
				$payment->getField('ACCOUNT_NUMBER'),
				$order->getField('ACCOUNT_NUMBER'),
				$payment->getId(),
				$order->getId(),
				($userEmail) ? $userEmail->getValue() : ''
			),
			$this->getBusinessValue($payment, $PROP_CODE)
		);

		return $description;
	}
	private static function encode(array $data)
	{
		return Main\Web\Json::encode($data, JSON_UNESCAPED_UNICODE);
	}
	protected function printResultText($payment,$successPayment)
	{
		global $APPLICATION;
        echo '<div class="sberbank-center" style="width: 100%;display: flex;align-items: center;align-content: center;justify-content: center;height: 100%;position: fixed;"><div style="display: block;background:#fff;padding: 10px 10px; margin-left:-10px;border-radius: 6px;max-width: 400px; border: 1px solid #e7e7e7;">';
		echo '<div class="sberbank-result-message" style="margin:5px; text-align:center;padding:10px 20px; 0"><span style=" font-family: arial;font-size: 16px;">';

	        if($successPayment) {
	        	$APPLICATION->SetTitle(Loc::getMessage('SBERBANK_PAYMENT_MESSAGE_THANKS'));
	        	echo Loc::getMessage('SBERBANK_PAYMENT_MESSAGE_THANKS_DESCRIPTION') . $this->getBusinessValue($payment, 'SBERBANK_ORDER_NUMBER');
	        } else {
	        	$APPLICATION->SetTitle(Loc::getMessage('SBERBANK_PAYMENT_MESSAGE_ERROR'));
	        	echo Loc::getMessage('SBERBANK_PAYMENT_MESSAGE_ERROR') . ' #' . $this->getBusinessValue($payment, 'SBERBANK_ORDER_NUMBER');
	        }
		echo '<div style=" display: block; margin:10px 10px 0;"><a style="font-family: arial;font-size: 16px;color: #21a038;" href="/">' . Loc::getMessage('SBERBANK_RETURN_LINK') . '</a></div>';
	        echo "</span></div>";
		echo "</div></div>";
	}
	public function isRefundableExtended(){}
	public function confirm(Payment $payment){}
	public function cancel(Payment $payment){}
	public function refund(Payment $payment, $refundableSum){}
	public function sendResponse(PaySystem\ServiceResult $result, Request $request){}

}