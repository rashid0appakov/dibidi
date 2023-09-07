<?php

namespace Sale\Handlers\PaySystem;
use Bitrix\Sale;
use Bitrix\Catalog;
use Bitrix\Catalog\Mysql\CCatalogVat;
use Bitrix\Main\Application;
use Bitrix\Main\Web\Uri;
use Bitrix\Main\Error;
use Bitrix\Main\Request;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;
use Bitrix\Sale\PaySystem;
use Bitrix\Sale\Payment;
use Bitrix\Sale\PriceMaths;
use Bitrix\Sale\Shipment; 
use Bitrix\Main\Web\HttpClient;

class PayKeeperHandler extends PaySystem\ServiceHandler implements PaySystem\IRefund
{
    private $payment_form_url = "";
    private $fiscal_cart = array(); //fz54 cart
    private $order_total = 0; //order total sum
    private $shipping_price = 0; //shipping price
    private $use_taxes = false;
    private $use_delivery = false;
    private $delivery_index = -1;
    private $single_item_index = -1;
    private $more_then_one_item_index = -1;
    private $order_params = NULL;
    private $paykeeper_pstype = 0;
    private $discounts = array();
   	/**
	 * @param Payment $payment
	 * @param Request|null $request
	 * @return PaySystem\ServiceResult
	 */
	public function initiatePay(Payment $payment, Request $request = null)
	{
      	$busValues = $this->getParamsBusValue($payment);
        $form_url = $busValues["PAYKEEPER_FORM_URL"];
        $no_vat = $busValues["PAYKEEPER_NO_VAT"];
        $this->setPaymentFormUrl($form_url);
        $secret = $busValues["PAYKEEPER_SECRET"];
        $orderid = ($this->getPaymentFormType() == "create") ? $payment->getOrderId() : $payment->getOrderId() . "|paykeeper_payment_system";
        $order = Sale\Order::load($orderid);
        $propertyCollection = $order->getPropertyCollection();
        $this->setOrderTotal(floatval($order->getPrice())-floatval($order->getSumPaid())); 
        $clientid = is_null($propertyCollection->getPayerName()) ? "" : $propertyCollection->getPayerName()->getValue();
        $client_email = is_null($propertyCollection->getUserEmail()) ? "" : $propertyCollection->getUserEmail()->getValue();
        $client_phone = is_null($propertyCollection->getPhone()) ? "" : $propertyCollection->getPhone()->getValue();
        $service_name = "paykeeper_payment_system";
        //set order parameters
        $this->setOrderParams($this->getOrderTotal(),     //sum
                              $clientid,                    //clientid
                              $orderid,                     //orderid
                              $client_email,                //client_email
                              $client_phone,                //client_phone
                              $service_name,                //service_name
                              $form_url,                    //payment form url
                              $secret                       //secret key
        );
        $this->fiscal_cart = $this->cart($order);
        echo '<pre>';
        echo var_dump($this->getOrderTotal());
        echo '</pre>';
		$params = array(
            "form_url" => $this->getOrderParams("form_url"),
            "sum" => 500,
            "orderid" => $this->getOrderParams("orderid"),
            "clientid" => $this->getOrderParams("clientid"),
            "client_email" => $this->getOrderParams("client_email"),
            "client_phone" => $this->getOrderParams("client_phone"),
            "service_name" => $this->getOrderParams("service_name"),
            "phone" => $this->getOrderParams("client_phone"),
            "cart" => $this->fiscal_cart
        );
        if($busValues["PAYKEEPER_PSTYPE"] != ""){
            $params["pstype"] = $busValues["PAYKEEPER_PSTYPE"];
            }
        $request = Application::getInstance()->getContext()->getRequest();
        $uriString = $request->getRequestUri();
        $uri = new Uri($uriString);
        $params["noauth_payment_active"] = strpos($uri->GetLocator(), "access=");
        $params["detail_page_active"] = strpos($uri->GetLocator(), "/personal/orders/$orderid");
        //payment form language
        switch (LANGUAGE_ID) {
            case "en":
                $pay_button = "Pay online";
                if ($params["detail_page_active"] !== false) {
                    $message = "Press pay button for redirect to bank payment gateway now.";
                    $params["detail_page_message"] = ($params["noauth_payment_active"] === false) ?
                        "Press pay button for redirect to bank payment gateway now."
                        : "Press pay button on personal account page for redirect to bank payment gateway now.";
                }
                else
                    $message = "You will be redirected to bank payment gateway now.";
                $pf_lang = "en";
                break;
            default:
                $pay_button = "Оплатить";
                if ($params["detail_page_active"] !== false) {
                    $message = "Нажмите на кнопку Оплатить для перенаправления на страницу банка.";
                    $params["detail_page_message"] = ($params["noauth_payment_active"] === false) ?
                        "Нажмите на кнопку Оплатить для перенаправления на страницу банка."
                        : "Нажмите на кнопку Оплатить в персональном разделе сайта для перенаправления на страницу банка.";
                }
                else
                    $message = "Сейчас Вы будете перенаправлены на страницу банка.";
                $pf_lang = "ru";
                break;
        }
        if (LANG_CHARSET != "utf-8" and $pf_lang == "ru")
            $message = iconv("UTF-8", LANG_CHARSET, $message);
        $params["message"] = $message;
        if ($this->getPaymentFormType() == "create") { //create form
            $params["form_type"] = "create";
            $to_hash = number_format($this->getOrderTotal(), 2, ".", "").
                       $this->getOrderParams("clientid")     .
                       $this->getOrderParams("orderid")      .
                       $this->getOrderParams("service_name") .
                       $this->getOrderParams("client_email") .
                       $this->getOrderParams("client_phone") .
                       $this->getOrderParams("secret_key");
            $sign = hash ('sha256' , $to_hash);
            $params["sign"] = $sign;
            $params["lang"] = $pf_lang;
            $pay_button = (LANG_CHARSET == "UTF-8") ? $pay_button :
                      iconv("UTF-8", LANG_CHARSET, $pay_button);
            $params["pay_button"] = $pay_button;
            $js_submit_string = 'document.forms["pay_form"].submit();';
            $params["js_submit_string"] = $js_submit_string;
        }
        else { //order form
            $payment_parameters = array
            (
                "clientid"=>$this->getOrderParams("clientid"), 
                "orderid"=>$this->getOrderParams('orderid'), 
                "sum"=>$this->getOrderTotal(), 
                "client_phone"=>$this->getOrderParams("phone"), 
                "phone"=>$this->getOrderParams("phone"), 
                "client_email"=>$this->getOrderParams("client_email"), 
                "cart"=>$this->fiscal_cart
            );
            $query = http_build_query($payment_parameters);
            $err_num = $err_text = NULL;
            if( function_exists( "curl_init" )) 
            { //using curl
                $CR = curl_init();
                curl_setopt($CR, CURLOPT_URL, $this->getOrderParams("form_url"));
                curl_setopt($CR, CURLOPT_POST, 1);
                curl_setopt($CR, CURLOPT_FAILONERROR, true); 
                curl_setopt($CR, CURLOPT_POSTFIELDS, $query);
                curl_setopt($CR, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($CR, CURLOPT_SSL_VERIFYPEER, 0);
                $result = curl_exec( $CR );
                $error = curl_error( $CR );
                if( !empty( $error )) {
                    $form = "<br/><span class=message>"."INTERNAL ERROR:".$error."</span>";
                    return false;
                }
                else {
                    $form = $result;
                    echo $form;  
                }
                curl_close($CR);
                exit();
            }
            else { //using file_get_contents
                if (!ini_get('allow_url_fopen')) {
                    $form = "<br/><span class=message>"."INTERNAL ERROR: Option allow_url_fopen is not set in php.ini"."</span>";
                }
                else {
                    $query_options = array("https"=>array(
                    "method"=>"POST",
                    "header"=>
                    "Content-type: application/x-www-form-urlencoded",
                    "content"=>$query));
                    $context = stream_context_create($query);
                    $form = file_get_contents($this->getOrderParams("form_url"), false, $context);
                }
            }
            if ($form  == "") {
                $form = '<h3>Произошла ошибка при инциализации платежа</h3>';
            }
        }
		$this->setExtraParams($params);
        return $this->showTemplate($payment, "template");
    }
    /**
	 * @param Payment $payment
	 * @param $refundableSum
	 * @return PaySystem\ServiceResult
	 * @throws Main\ArgumentException
	 * @throws Main\ArgumentNullException
	 * @throws Main\ArgumentOutOfRangeException
	 * @throws Main\ArgumentTypeException
	 * @throws Main\ObjectException
	 */
	public function refund(Payment $payment, $refundableSum): PaySystem\ServiceResult
	{
		$result = new PaySystem\ServiceResult();
        $busValues = $this->getParamsBusValue($payment);
        $paykeeper_url = $busValues["PAYKEEPER_FORM_URL"];
        $paykeeper_login = $busValues["PAYKEEPER_LK_LOGIN"];
        $paykeeper_password = $busValues["PAYKEEPER_LK_PASSWORD"];
        if($pos=strrpos($paykeeper_url, "/create"))
        {
            $paykeeper_url = substr($paykeeper_url, 0, $pos);
        }
        else if($pos=strrpos($paykeeper_url, "/order"))
        {
            $paykeeper_url = substr($paykeeper_url, 0, $pos);
        }
        $url = $this->getUrl($payment, 'test');
        $paykeeper_id = substr($payment->getField('PS_STATUS_MESSAGE'), 12); 
        $orderid = $payment->getField('ORDER_ID'); 
        $totalsum = $payment->getField('SUM');
        $order = Sale\Order::load($orderid);
        $this->setOrderTotal($refundableSum);
        $this->fiscal_cart = $this->cart($order);
        $params = [
			'id' => $paykeeper_id,
			'amount' => $totalsum,
			'refund_cart' => $this->fiscal_cart,
            ];
        $refund=$this->refundPaykeeper($paykeeper_login, $paykeeper_password, $paykeeper_url, $params);
        $message = "Paykeeper refund - order_id ".$orderid.", sum ".$totalsum.", status ".$refund->result." cart ".$this->fiscal_cart;
        PaySystem\Logger::addError($message);
        $file = "refund.txt";
        file_put_contents($file, print_r($message, true).PHP_EOL, FILE_APPEND);
        if($refund->result == "success")
        {
            $result->setOperationType(PaySystem\ServiceResult::MONEY_LEAVING);
        }
            return $result;
    }
    public function cart($order)
    {
        $basket = $order->getBasket();
        $item_index = 0;
        foreach ($basket as $item) {
            $taxes = array("tax" => "none", "tax_sum" => 0);
            $name = $item->getField("NAME");
            $quantity = $item->getField("QUANTITY");
            //for correctPrecision()
            if ($quantity == 1 && $this->single_item_index < 0)
                $this->single_item_index = $item_index;
            if ($quantity > 1 && $this->more_then_one_item_index < 0)
                $this->more_then_one_item_index = $item_index;
            $price = floatval($item->getField("PRICE"));
            $sum = $price*$quantity;

            $vat_rate = (int) ($item->getField("VAT_RATE")*100);
            if($no_vat == "Y"){
                $taxes = $this->setTaxes(0);
            }
            else {
                $taxes = $this->setTaxes((float) $vat_rate);
            }
            //$taxes = $this->setTaxes($sum, (float) $vat_rate);
            $this->updateFiscalCart($this->getPaymentFormType(), $name, $price, $quantity, $sum, $taxes["tax"]);
            $item_index++;
        }
        $shipping_tax_rate = 0;
        $shipping_taxes = array("tax" => "none", "tax_sum" => 0);
        $dbSaleDelivery = \Bitrix\Sale\Delivery\Services\Manager::getById($order->getField("DELIVERY_ID"));
        $delivery_name = $dbSaleDelivery["NAME"];
        $delivery_price = floatval($order->getDeliveryPrice());
        $this->setShippingPrice($delivery_price);
        if (!$this->checkDeliveryIncluded($this->getShippingPrice(), $delivery_name)
            && $this->getShippingPrice() > 0) {
            $shipmentCollection = $order->getShipmentCollection();
            foreach($shipmentCollection as $shipment)
                 {
                $shipment_id = $shipment->getId();
                if ($shipment->isSystem())
                continue;
                 }
            $delivery_tax_rate = $shipment->getVatRate()*100;
            $delivery_taxes = ((int) $dbSaleDelivery["VAT_ID"] == 0) ?
                            array("tax" => "none", "tax_sum" => 0) :
                            $this->setTaxes($delivery_tax_rate);
            $this->setUseDelivery();
            $this->updateFiscalCart($this->getPaymentFormType(), $delivery_name, $delivery_price, 1, $delivery_price, $delivery_taxes["tax"]);
            $this->delivery_index = count($this->getFiscalCart())-1;
        }
        //set discounts
        $this->setDiscounts((floatval($order->getSumPaid()) > 0));
        $this->correctPrecision(); //correct possible difference between order sum and fiscal cart sum
        //Encode fiscal cart to utf-8 for json_encode
        $cart_encoded = array();
        foreach ($this->getFiscalCart() as $product) {
            $product_ar = array();
            foreach ($product as $key => $value) {
                $enc = mb_detect_encoding($value, 'ASCII, UTF-8, windows-1251', false);
                $product_ar[$key] = ($enc == "UTF-8") ? $value : iconv($enc, "UTF-8", $value);
            }
            $cart_encoded[] = $product_ar;
        }
       return $this->fiscal_cart = json_encode($cart_encoded); 
    }
    private function refundPaykeeper($user, $password, $server_paykeeper, array $params) 
    {
    $base64=base64_encode("$user:$password");         // Формируем base64 хэш
    $headers=Array();
    array_push($headers,'Content-Type: application/x-www-form-urlencoded');
    array_push($headers,'Authorization: Basic '.$base64);
    // Готовим первый запрос на получение токена
    $uri="/info/settings/token/";                     // Запрос на получение токена
    $curl=curl_init();                                // curl должен быть установлен
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl,CURLOPT_URL,$server_paykeeper.$uri);
    curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'GET');
    curl_setopt($curl,CURLOPT_HTTPHEADER,$headers);
    curl_setopt($curl,CURLOPT_HEADER,false);
    $out=curl_exec($curl);                            // Инициируем запрос к API
    $php_array=json_decode($out,true);                // Сохраняем результат в массив
    if (isset($php_array['token'])) $token=$php_array['token']; else die('Error getting token');
    $uri="/change/payment/reverse/";
    $curl=curl_init();                                # curl должен быть установлен
    curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
    curl_setopt($curl,CURLOPT_URL,$server_paykeeper.$uri);
    curl_setopt($curl,CURLOPT_CUSTOMREQUEST,'POST');
    curl_setopt($curl,CURLOPT_HTTPHEADER,$headers);
    curl_setopt($curl,CURLOPT_HEADER,false);
    curl_setopt($curl,CURLOPT_POSTFIELDS,"id=".$params["id"]."&amount=".$params["amount"]."&partial=false"."&token=".$token."&refund_cart=".$params["refund_cart"]);
    $out=curl_exec($curl);                            # Инициируем запрос к API
    return json_decode($out);                       # Выводим результат запроса
    } 
	/**
	 * @return array
	 */
	public static function getIndicativeFields()
	{
		return array('id', 'sum', 'clientid', 'orderid', 'key');
	}
    /** 
     * @param Request $request
     * @param $paySystemId
     * @return bool
     */
    static protected function isMyResponseExtended(Request $request, $paySystemId)
    {   
        if($request->get('action') == "paykeeper_cancel"){
            $orderid = $request->get('orderid');
            $order = \Bitrix\Sale\Order::load($orderid);
            $r = $order->setField('COMMENTS', 'CANCELED');
            if (!$r->isSuccess())
                {
                     var_dump($r->getErrorMessages());
                }
                $r = $order->save();
            return FALSE;
        }
        $orderid = $request->get('orderid');
        $service_name = $request->get('service_name');
        if ($service_name == 'paykeeper_payment_system')
            return TRUE;
        $orderid_split = explode('|', $orderid);
        if (is_array($orderid_split))
            if (count($orderid_split) > 1)
                if ($orderid_split[1] == 'paykeeper_payment_system')
                    return TRUE;
        return FALSE;
    }
	/**
	 * @param Request $request
	 * @return mixed
	 */
	public function getPaymentIdFromRequest(Request $request)
	{
        return $this->getOrderIdFromRequest($request);
	}
	/**
	 * @param Payment $payment
	 * @param Request $request
	 * @return PaySystem\ServiceResult
	 */
	public function processRequest(Payment $payment, Request $request)
	{
        $payment_id = (int) $request->get('id');
        $clientid = $request->get('clientid');
        $sum = (float) $request->get('sum');
        $orderid = $request->get('orderid');
        $sign = $request->get('key');
        if($payment_id == 0)
        {
            echo "No payment specified!";
            die();
        }
        $totalsum = $payment->getField('SUM');
        if($sum != $totalsum)
        {
            echo "The sums are not equal!";
            die();
        }
		$busValues = $this->getParamsBusValue($payment);
        $secret = $busValues["PAYKEEPER_SECRET"];

        $hash = md5($payment_id.number_format($sum, 2, '.', '').$clientid.$orderid.$secret);
        if ($hash != $sign)
        {
            echo "Hash mismatch";
            die();
        }
        else
        {
            $result = new PaySystem\ServiceResult();
            $fields = array(
                "PS_STATUS" => "Y",
                "PS_STATUS_CODE" => "Success",
                "PS_STATUS_DESCRIPTION" => "Payment accepted",
                "PS_STATUS_MESSAGE" => "Payment id: $payment_id",
                "PS_SUM" => $sum,
                "PS_CURRENCY" => "",
            );
            $result->setOperationType(PaySystem\ServiceResult::MONEY_COMING);
            $result->setPsData($fields);
			//change order status according setting
            $status_id_after_payment = $busValues["PAYKEEPER_STATUS_AFTER_PAYMENT"];
            if ($status_id_after_payment != NULL) {
                if ((int) $orderid > 0) {
                    $order = \Bitrix\Sale\Order::load($orderid);
                    $r = $order->setField('STATUS_ID', $status_id_after_payment);
                    if (!$r->isSuccess())
                    {   
                        var_dump($r->getErrorMessages());
                    }   
                    else
                    {   
                        $r = $order->save();
                        if (!$r->isSuccess())
                        {   
                            var_dump($r->getErrorMessages());
                        }   
                        else
                            echo "OK ".md5($payment_id.$secret);
                    }   
                }   
            }   
            else
                echo "OK ".md5($payment_id.$secret);
        }
        return $result;
	}
	/**
	 * @return array
	 */
	public function getCurrencyList()
	{
		return array('RUB');
	}
    private function getPaymentFormType()
    {
        if (strpos($this->getPaymentFormUrl(), "/order/inline") == True)
            return "order";
        else
            return "create";
    }
    private function setPaymentFormUrl($payment_form_url)
    {
        $this->payment_form_url = $payment_form_url;
    }
    private function getPaymentFormUrl()
    {
        return $this->payment_form_url;
    }
    private function getOrderIdFromRequest(Request $request)
    {
        if (strpos($request->get('orderid'), 'paykeeper_payment_system') !== false) {
            $order_data = explode("|", $request->get('orderid'));
            $orderid = $order_data[0];
        }
        else
            $orderid = $request->get('orderid');
        $order = Sale\Order::load($orderid);
        if (!is_null($order)) {
            $bitrix_order_id = $order->getPaymentCollection()->current()->getField("ID");
            return $bitrix_order_id;
        }
        else
            echo "Could not load order object by given orderid: $orderid";
    }
    public function setOrderParams($order_total = 0, $clientid="", $orderid="", $client_email="", 
                                    $client_phone="", $service_name="", $form_url="", $secret_key="")
    {
       $this->setOrderTotal($order_total);
       $this->order_params = array(
           "sum" => $order_total,
           "clientid" => $clientid,
           "orderid" => $orderid,
           "client_email" => $client_email,
           "client_phone" => $client_phone,
           "phone" => $client_phone,
           "service_name" => $service_name,
           "form_url" => $form_url,
           "secret_key" => $secret_key,
       );
    }
    public function getOrderParams($value)
    {
        return array_key_exists($value, $this->order_params) ? $this->order_params["$value"] : False;
    }
    public function getAllOrderParams()
    {
        return $this->order_params;
    }
    public function updateFiscalCart($ftype, $name="", $price=0, $quantity=0, $sum=0, $tax="none")
    {
        //update fz54 cart
        if ($ftype === "create") {
            $name = str_replace("\n ", "", $name);
            $name = str_replace("\r ", "", $name);
        }
        $price_to_add = number_format($price, 2, ".", "");
        $sum_to_add = number_format($price_to_add*$quantity, 2, ".", "");
        $this->fiscal_cart[] = array(
            "name" => $name,
            "price" => $price_to_add,
            "quantity" => $quantity,
            "sum" => $sum_to_add,
            "tax" => $tax
        );
    }
    public function getFiscalCart()
    {
        return $this->fiscal_cart;
    }
    public function setDiscounts($discount_enabled_flag)
    {
        $discount_modifier_value = 1;
        $shipping_included = false;
        //set discounts
        if ($discount_enabled_flag) {
            if ($this->getFiscalCartSum(false) > 0) {
                if ($this->getOrderTotal() >= $this->getShippingPrice()) {
                    if ($this->getFiscalCartSum(false) > 0) { //divide by zero error
                        $discount_modifier_value = ($this->getOrderTotal() - $this->getShippingPrice())/$this->getFiscalCartSum(false);
                    }
                }
                else {
                    if ($this->getFiscalCartSum(true) > 0) { //divide by zero error
                        $discount_modifier_value = $this->getOrderTotal()/$this->getFiscalCartSum(true);
                        $shipping_included = true;
                    }
                }
                if ($discount_modifier_value < 1) {
                    for ($pos=0; $pos<count($this->getFiscalCart()); $pos++) {//iterate fiscal cart with or without shipping
                        if (!$shipping_included && $pos == $this->delivery_index) {
                            continue;
                        }
                        if ($this->fiscal_cart[$pos]["quantity"] > 0) { //divide by zero error
                            $price = $this->fiscal_cart[$pos]["price"]*$discount_modifier_value;
                            $this->fiscal_cart[$pos]["price"] = number_format($price, 2, ".", "");
                            $sum = $this->fiscal_cart[$pos]["price"]*$this->fiscal_cart[$pos]["quantity"];
                            $this->fiscal_cart[$pos]["sum"] = number_format($sum, 2, ".", "");
                        }
                    }
                }
            }
        }
    }
    public function correctPrecision()
    {
        //handle possible precision problem
        $fiscal_cart_sum = $this->getFiscalCartSum(true);
        $total_sum = $this->getOrderTotal();
        $diff_value = $total_sum - $fiscal_cart_sum;
        //debug_info
        //echo "\ntotal: $total_sum - cart: $fiscal_cart_sum - diff: $diff_sum";
        if (abs($diff_value) >= 0.005) {
            $diff_sum = number_format($diff_value, 2, ".", "");
            if ($this->getUseDelivery()) { //delivery is used
                $this->correctPriceOfCartItem($diff_sum, count($this->fiscal_cart)-1);
            }
            else {
                if ($this->single_item_index >= 0) { //we got single cart element
                    $this->correctPriceOfCartItem($diff_sum, $this->single_item_index);
                }
                else if ($this->more_then_one_item_index >= 0) { //we got cart element with more then one quantity
                    $this->splitCartItem($this->more_then_one_item_index);
                    //add diff_sum to the last element (just separated) of fiscal cart
                    $this->correctPriceOfCartItem($diff_sum, count($this->fiscal_cart)-1);
                }
                else { //we only got cart elements with less than one quantity
                    $modify_value = ($diff_sum > 0) ? $total_sum/$fiscal_cart_sum : $fiscal_cart_sum/$total_sum;
                    if ($diff_sum > 0) {
                        if ($fiscal_cart_sum > 0) { //divide by zero error
                            $modify_value = $total_sum/$fiscal_cart_sum;
                        }
                    }
                    else {
                        if ($total_sum > 0) { //divide by zero error
                            $modify_value = $fiscal_cart_sum/$total_sum;
                        }
                    }
                    for ($pos=0; $pos<count($this->getFiscalCart()); $pos++) {
                        if ($this->fiscal_cart[$pos]["quantity"] > 0) { //divide by zero error
                            $sum = $this->fiscal_cart[$pos]["sum"]*$modify_value;
                            $this->fiscal_cart[$pos]["sum"] *= number_format($sum, 2, ".", "");
                            $price = $this->fiscal_cart[$pos]["sum"]/$this->fiscal_cart[$pos]["quantity"];
                            $this->fiscal_cart[$pos]["price"] = number_format($price, 2, ".", "");
                        }
                    }
                }
            }
        }
    }
    public function setOrderTotal($value)
    {
        $this->order_total = $value;
    }
    public function getOrderTotal()
    {
        return $this->order_total;
    }
    public function setShippingPrice($value)
    {
        $this->shipping_price = $value;
    }
    public function getShippingPrice()
    {
        return $this->shipping_price;
    }
    public function setUseTaxes()
    {
        $this->use_taxes = True;
    }
    public function getUseTaxes()
    {
        return $this->use_taxes;
    }
    public function setUseDelivery()
    {
        $this->use_delivery = True;
    }
    public function getUseDelivery()
    {
        return $this->use_delivery;
    }
    //$zero_value_as_none: if variable is set, then when tax_rate is zero, tax is equal to none
    public function setTaxes($tax_rate, $zero_value_as_none = true)
    {
        $taxes = array("tax" => "none", "tax_sum" => 0);
        switch(number_format(floatval($tax_rate), 0, ".", "")) {
            case 0:
                if (!$zero_value_as_none) {
                    $taxes["tax"] = "vat0";
                }
                break;
            case 10:
                $taxes["tax"] = "vat10";
                break;
            case 18:
                $taxes["tax"] = "vat18";
                break;
            case 20:
                $taxes["tax"] = "vat20";
                break;
        }
        return $taxes;
    }
    public function checkDeliveryIncluded($delivery_price, $delivery_name) {
        $index = 0;
        foreach ($this->getFiscalCart() as $item) {
            if ($item["name"] == $delivery_name
                && $item["price"] == $delivery_price
                && $item["quantity"] == 1) {
                $this->delivery_index = $index;
                return true;
            }
            $index++;
        }
        return false;
    }
    public function getFiscalCartSum($delivery_included) {
        $fiscal_cart_sum = 0;
        $index = 0;
        foreach ($this->getFiscalCart() as $item) {
            if (!$delivery_included && $index == $this->delivery_index)
                continue;
                $fiscal_cart_sum += $item["price"]*$item["quantity"];
            $index++;
        }
        return number_format($fiscal_cart_sum, 2, ".", "");
    }
    public function showDebugInfo($obj_to_debug)
    {
        echo "<pre>";
        var_dump($obj_to_debug);
        echo "</pre>";
    }
    public function correctPriceOfCartItem($corr_price_to_add, $item_position)
    { //$corr_price_to_add is always with 2 gigits after dot
        $item_sum = 0;
        $this->fiscal_cart[$item_position]["price"] += $corr_price_to_add;
        $item_sum = $this->fiscal_cart[$item_position]["price"]*$this->fiscal_cart[$item_position]["quantity"];
        $this->fiscal_cart[$item_position]["sum"] = number_format($item_sum, 2, ".", "");
    }
    public function splitCartItem($cart_item_position)
    {
        $item_sum = 0;
        $item_price = 0;
        $item_quantity = 0;
        $item_price = $this->fiscal_cart[$cart_item_position]["price"];
        $item_quantity = $this->fiscal_cart[$cart_item_position]["quantity"]-1;
        $this->fiscal_cart[$cart_item_position]["quantity"] = $item_quantity; //decreese quantity by one
        $this->fiscal_cart[$cart_item_position]["sum"] = $item_price*$item_quantity; //new sum
        //add one cart item to the end of cart
        $this->updateFiscalCart(
            $this->getPaymentFormType(),
            $this->fiscal_cart[$cart_item_position]["name"],
            $item_price, 1, $item_price,
            $this->fiscal_cart[$cart_item_position]["tax"]);
    }
}
