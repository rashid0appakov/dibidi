<?php namespace Sberbank\Payments;

// version: 1.1
// date: 2021-04-30

use Bitrix\Main\Web;
use DateTime;

define('LOG_FILE', realpath(dirname(dirname(dirname(__FILE__)))) . "/logs/sberbank.log");

Class Gateway
{

    const log_file = LOG_FILE;
    /**
     * Массив с НДС
     *
     * @var integer
     * 0 = Без НДС
     * 1 = НДС по ставке 0%
     * 2 = НДС чека по ставке 10%
     * 3 = НДС чека по ставке 18%
     * 4 = НДС чека по ставке 10/110
     * 6 = НДС чека по ставке 20%
	 * 7 = НДС чека по ставке 20/120
     */

    private static $arr_tax = array(
        0 => 0,
        1 => 1,
        2 => 10, 
        4 => 4, // 10/110
        3 => 18,
        6 => 20,
        7 => 7 // 20/120
    );
    private $gate_url;

	private $basket = array();

	private $data = array();

	private $options = array(
		'gate_url_prod' => '',
		'gate_url_test' => '',
		'payment_link' => '',
		'ofd_enabled' => false,
		'module_version' => 'def',
		'language' => 'ru',
		'ofd_tax' => 0,
		'handler_two_stage' => 0,
		'default_cartItem_tax' => 0,
		'delivery' => false,
		'handler_logging' => true,
		'customer_phone' => '',
		'customer_email' => '',
		'customer_name' => '',
		'callback_redirect' => 0,
		'domain_finded' => false,
		'callback_url' => '',
		'additionalOfdParams' => array(),
	);


	public function buildData($data) {
		foreach ($data as $key => $value) {
			$this->data[$key] = $value;
		}
	}

	public function setOptions($data) {
		foreach ($data as $key => $value) {
			$this->options[$key] = $value;
		}
	}

	public function registerOrder() {
		$this->transformPrices();
		$json_params = array(
			'CMS' => $this->options['cms_version'],
			'Module-Version' => $this->options['module_version'],
			'USER_FIO' => $this->options['customer_name'],
		);

		if(strlen($this->options['customer_email']) > 3) {
			$json_params['email'] = $this->options['customer_email'];
		}
		if(strlen($this->options['customer_phone']) > 3) {
			$json_params['phone'] = $this->options['customer_phone'];
		}
		$this->buildData(array(
		    'CMS' => $this->options['cms_version'],
			'language' => $this->options['language'],
		    'jsonParams' => Web\Json::encode($json_params)
		));
		$gateData = $this->data;
		$orderId = $this->data['orderNumber'];
		
		for ($i=0; $i < 30; $i++) {

		 	$gateData['orderNumber'] = $orderId . "_" . $i;
			$method = 'getOrderStatusExtended.do';
		 	$gateResponse = $this->setRequest($method, $gateData);

		 	if($gateResponse['amount'] != $gateData['amount'] && $gateResponse['errorCode'] != 6) {
			 	continue;
			}
		 	if($gateResponse['errorCode'] == 6) {

		 		// register order from gate
		 		if($this->ofdEnable()) {
		 			$this->addFFDParams();
					$gateData = $this->addOrderBundle($gateData);
					if(isset($this->options['additionalOfdParams']) && count($this->options['additionalOfdParams']) > 0) {
						$gateData['additionalOfdParams'] = json_encode($this->options['additionalOfdParams']);
					}
				}
				$method = $this->options['handler_two_stage'] ? 'registerPreAuth.do' : 'register.do';
		 		$gateResponse = $this->setRequest($method, $gateData);

				if($this->options['domain_finded']) {
					$this->updateCallback([
						'login' => $this->data['userName'],
						'password' => $this->data['password'],
						'test_mode' => $this->options['test_mode'],
	
						'callbacks_enabled' => true,
						'callback_addresses' => $this->options['callback_url'],
						'callback_operations' => 'approved,deposited'
					]);
				}
				if($gateResponse['errorCode'] == 0 ) {		
			 		$this->setRequest('addParams.do', array(
			 			'userName' => $this->data['userName'],
			 			'password' => $this->data['password'],
			 			'orderId' => $gateResponse['orderId'],
			 			'language' => $this->options['language'],
			 			'params' => Web\Json::encode(array('formUrl' => $gateResponse['formUrl'])),
			 		));

			 		$this->createPaymentLink($gateResponse['formUrl'],'register.do');
		 		}
		 		break;

		 	} else if($gateResponse['errorCode'] == 0 && $gateResponse['orderStatus'] == 0) {
		 		// return and build payment link already registered order from gate
		 		foreach ($gateResponse['merchantOrderParams'] as $key => $item) {
		 			if($item['name'] == 'formUrl') {
		 				$this->createPaymentLink($item['value'],'getOrderStatusExtended.do');
		 				break;
		 			}
		 		}
		 		
		 		break;
		 	} else if($gateResponse['errorCode'] == 0 && $gateResponse['orderStatus'] == 2 && $gateResponse['amount'] == $gateData['amount']) {
		 		// order allready payed
				$gateResponse = array('payment' => 1);
				break;
		 	} else if($gateResponse['errorCode'] != 0) {
				break;
		 	}

		}

		if($gateResponse['errorCode'] != 0) {
			$this->baseLogger($this->gate_url, $method, $gateData, $gateResponse,'REGISTER_ERROR');
		} else if(($method == 'registerPreAuth.do' || $method == 'register.do') && $this->options['handler_logging']) {
			$this->baseLogger($this->gate_url, $method, $gateData, $gateResponse,'REGISTER_NEW_ORDER');
		}

		return $gateResponse;
	}

	public function checkOrder() {
		$gateData = $this->data;
		$gateResponse = $this->setRequest('getOrderStatusExtended.do', $gateData);

		if($this->options['handler_logging']) {
			$title = $this->options['callback_redirect'] ? 'CALLBACK_RETURN' : 'USER_RETURN';
			$this->baseLogger($this->gate_url, 'getOrderStatusExtended.do', $gateData, Web\Json::encode($title == 'USER_RETURN' ? array( 'orderNumber' => $gateResponse['orderNumber']) : $gateResponse, JSON_UNESCAPED_UNICODE),$title);
		}
		return $gateResponse;
	}

	public function refund() {
		$gateData = $this->data;

		$gateResponse = $this->setRequest('refund.do', $gateData);

		if($this->options['handler_logging']) {
			$this->baseLogger($this->gate_url, 'refund.do', $gateData, Web\Json::encode($gateResponse),'REFUND');
		}
		return $gateResponse;
	}

	public function deposit() {
		$gateData = $this->data;
		$gateResponse = $this->setRequest('deposit.do', $gateData);

		if($this->options['handler_logging']) {
			$this->baseLogger($this->gate_url, 'deposit.do', $gateData, Web\Json::encode($gateResponse),'DEPOSIT');
		}
		return $gateResponse;
	}

	public function ofdEnable() {
		if($this->options['ofd_enabled'] == true) {
			return true;
		}
		return false;
	}

	public function setPosition($position) {
		array_push($this->basket, $position);
	}

	public function getBasket() {        
		return $this->basket;
	}

	public function getTaxCode($tax_rate) {
		$result = $this->options['default_cartItem_tax'];
		
		if($tax_rate != 0) {
			foreach (self::$arr_tax as $key => $value) {
				if($value == $tax_rate) {
					$result = $key;
				}
			}
		}
		           
		return $result;
	}

	public function getTaxCodeDelivery($tax_rate) {
		$result = 0;
		
		foreach (self::$arr_tax as $key => $value) {
			if($value == $tax_rate) {
				$result = $key;
			}
		}
		           
		return $result;
	}

	public function getCurrencyCode($currency) {
		$result = 0;
		foreach ($this->options['iso'] as $key => $value) {

			if($key == $currency) {
				$result = $value;
			}
		}
		return $result;
	}

	private function addFFDParams() {
		//	if($this->options['ffd_version'] == '1.05') {

			foreach ($this->basket as $key => $item) {

				if($this->options['delivery'] && count($this->basket) == $key+1) {
					$paymentMethod = $this->options['ffd_payment_method_delivery'] ? $this->options['ffd_payment_method_delivery'] : 1;
					$paymentObject = $this->options['ffd_payment_object_delivery'] ? $this->options['ffd_payment_object_delivery'] : 4;
				} else {
					$paymentMethod = $this->options['ffd_payment_method'];
					$paymentObject = $this->options['ffd_payment_object'];
				}
				$this->basket[$key]['itemAttributes'] = array(
	                'attributes' => array(
	                    array(
	                        'name' => 'paymentMethod',
	                        'value' => $paymentMethod,
	                    ),
	                    array(
	                        'name' => 'paymentObject',
	                        'value' => $paymentObject,
	                    ),
	                )
	            );
				if(isset($this->basket[$key]['supplier_info'])) {
					 $this->basket[$key]['itemAttributes']['attributes'][] = array(
					 	'name' => 'supplier_info.name',
	                    'value' => $this->basket[$key]['supplier_info']['name'],
					 );
					 $this->basket[$key]['itemAttributes']['attributes'][] = array(
					 	'name' => 'supplier_info.inn',
						'value' => $this->basket[$key]['supplier_info']['inn'],
					 );
					unset($this->basket[$key]['supplier_info']);
				}
			}

		// }
	}

	private function setRequest($method,$data) {

		global $APPLICATION;


		$this->gate_url = $this->options['test_mode'] ?  $this->options['gate_url_test'] : $this->options['gate_url_prod'];

		if (mb_strtoupper(SITE_CHARSET) != 'UTF-8') { $data = $APPLICATION->ConvertCharsetArray($data, 'windows-1251', 'UTF-8'); }
		$http = new Web\HttpClient();
	    $http->setCharset("utf-8");
	 	$http->setHeader('CMS: ', $this->options['cms_version']);
	 	$http->setHeader('Module-Version:: ', $this->options['module_version']);
	 	$http->disableSslVerification();
	 	$http->post($this->gate_url . $method, $data);

	 	$response =  $http->getResult();

	 	if ($this->is_json($response)) {
	    	$response =  Web\Json::decode($response);
	    } else {
	        $response = array(
	            'errorCode' => 999,
	            'errorMessage' => 'Server not available',
	        );
	        //var_dump( $http->getError() );
			//var_dump( $http->getStatus() );
			//var_dump( $http->getHeaders() );
	    }

	 	if (mb_strtoupper(SITE_CHARSET) != 'UTF-8') { $APPLICATION->ConvertCharsetArray($response, 'UTF-8', 'windows-1251'); }
	 	
	 	return $response;
	}

	private function is_json($string,$return_data = false) {
	      $data = json_decode($string);
	     return (json_last_error() == JSON_ERROR_NONE) ? ($return_data ? $data : TRUE) : FALSE;
	}

	private function addOrderBundle($data) {
		$data['orderBundle']['customerDetails'] = array(
			'email' => $this->options['customer_email'],
		);
		$data['orderBundle']['cartItems']['items'] = $this->basket;
		$data['taxSystem'] = $this->options['ofd_tax'];

		$data['orderBundle'] = Web\Json::encode($data['orderBundle']);
		return $data;
	}

	private function transformPrices() {
		$this->data['amount'] = $this->data['amount'] * 100;
		if (is_float($this->data['amount'])) {
		    $this->data['amount'] = round($this->data['amount']);
		}
		if($this->ofdEnable()) {
			foreach ($this->basket as $key => $item) {
				$this->basket[$key]['itemPrice'] = round($item['itemPrice'] * 100);
				$this->basket[$key]['itemAmount'] = round($item['itemAmount'] * 100);
				
			}
		}
	}
    
	private function createPaymentLink($linkPart,$method) {

		if($method == 'register.do' || $method == 'registerPreAuth.do') {
			$this->options['payment_link'] = $linkPart;
		} else if ($method == 'getOrderStatusExtended.do') {
			$this->options['payment_link'] = $linkPart;	
		}
	}

	public function getPaymentLink() {
		return $this->options['payment_link'];
	}

	public function debug($data) {
		echo "<pre>";
		print_r($data);
		echo "</pre>";
	}
	
	public function baseLogger($url, $method, $data, $response, $title) {
        $objDateTime = new DateTime();
        $file = self::log_file;
        $logContent = '';

        if(file_exists($file)) {
            $logSize = filesize($file) / 1000;
            if($logSize < 10000) {
                $logContent = file_get_contents($file);
            }
        }
        $logContent .= $title . "\n";
        $logContent .= '----------------------------' . "\n";
        $logContent .= "DATE: " . $objDateTime->format("Y-m-d H:i:s") . "\n";
        $logContent .= 'URL ' . $url . "\n";
        // $logContent .= 'METHOD ' . $method . "\n";
        
        
        if($title != 'USER_RETURN') {
        	$logContent .= "DATA: \n" . print_r($data,true) . "\n";
        }
        $logContent .= "RESPONSE: \n" . print_r($response,true) . "\n";

        $logContent .= "\n\n";
        file_put_contents($file, $logContent);

	}

	public function updateCallback($data) {
		if(!isset($data['login']) && !isset($data['password'])) {
			return false;
		}

		$data['name'] = str_replace('-api', "", $data['login']);

    	if($data['test_mode'] == 1) {
			$gate_url = "https://3dsec.sberbank.ru/mportal-uat/mvc/public/merchant/";
    	} else {
    		$gate_url = "https://securepayments.sberbank.ru/mportal/mvc/public/merchant/";
    	}    	
    	$gate_url = $gate_url . 'update/' . $data['name'];


    	$http = new Web\HttpClient();
	 	$http->setHeader('Content-Type', 'application/json');
	 	$http->setAuthorization($data['login'], $data['password']);
	 	$http->disableSslVerification();
	 	$http->post($gate_url . $method, json_encode($data));
	 	$response =  $http->getResult();


        $this->baseLogger($gate_url, 'update', $data, $response,'CALLBACK_UPDATE');
        $response = json_decode($response,true);
        if($response['status'] == 'SUCCESS') {
        	return 1;
        } else {
        	return 0;
        }
	}

	public function getCallback($data) {
		if(!isset($data['login']) && !isset($data['password'])) {
			return false;
		}

		$data['name'] = str_replace('-api', "", $data['login']);

    	if($data['test_mode'] == 1) {
			$gate_url = "https://3dsec.sberbank.ru/mportal-uat/mvc/public/merchant/";
    	} else {
    		$gate_url = "https://securepayments.sberbank.ru/mportal/mvc/public/merchant/";
    	}    	
    	$gate_url = $gate_url . 'get/' . $data['name'];


    	$http = new Web\HttpClient();
	 	$http->setHeader('Content-Type', 'application/json');
	 	$http->setAuthorization($data['login'], $data['password']);
	 	$http->disableSslVerification();
	 	$http->post($gate_url . $method, json_encode($data));
	 	$response =  $http->getResult();


        // $this->baseLogger($gate_url, 'get', $data, $response,'CALLBACK_GET');
        $response = json_decode($response,true);
        if($response && $response['status'] == 'SUCCESS') {
        	return $response['callback_addresses'];
        }
        return false;
        
	}

	public function broadcast_callback($url,$params) {
		$data = http_build_query($params);
		$result_url = strpos($url, '?') ? $url . '&' . $data : $url . '?' . $data;
		$http = new Web\HttpClient();
		$http->get($result_url);
	 	$response =  $http->getResult();
	 	$this->baseLogger($result_url, '', '', '','CALLBACK_BROADCAST');
	}
}

?>