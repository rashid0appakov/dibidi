<?

use \Bitrix\Main,
    \Bitrix\Main\Localization\Loc as Loc,
    Bitrix\Main\Loader,
    Bitrix\Main\Application,
    Bitrix\Currency,
    Bitrix\Sale\Delivery,
    Bitrix\Sale\PaySystem,
    Bitrix\Sale,
    Bitrix\Sale\Order,
    Bitrix\Sale\Affiliate,
    Bitrix\Sale\DiscountCouponsManager,
    Bitrix\Main\Context,
    Bitrix\Main\Page\Asset,
    \Bitrix\Main\EventManager;

function pr($o, $show = false, $die = false, $fullBackTrace = false)
{
    global $USER;
    if ($USER->IsAdmin() || $show) {
        $bt = debug_backtrace();

        $firstBt = $bt[0];
        $dRoot = $_SERVER["DOCUMENT_ROOT"];
        $dRoot = str_replace("/", "\\", $dRoot);
        $firstBt["file"] = str_replace($dRoot, "", $firstBt["file"]);
        $dRoot = str_replace("\\", "/", $dRoot);
        $firstBt["file"] = str_replace($dRoot, "", $firstBt["file"]);
        ?>
        <div style='font-size:9pt; color:#000; background:#fff; border:1px dashed #000;'>
            <div style='padding:3px 5px; background:#99CCFF;'>

                <? if ($fullBackTrace == false): ?>
                    File: <b><?= $firstBt["file"] ?></b> [line: <?= $firstBt["line"] ?>]
                <? else: ?>
                    <? foreach ($bt as $value): ?>
                        <?
                        $dRoot = str_replace("/", "\\", $dRoot);
                        $value["file"] = str_replace($dRoot, "", $value["file"]);
                        $dRoot = str_replace("\\", "/", $dRoot);
                        $value["file"] = str_replace($dRoot, "", $value["file"]);
                        ?>

                        File: <b><?= $value["file"] ?></b> [line: <?= $value["line"] ?>]<br>
                    <? endforeach ?>
                <? endif; ?>
            </div>
            <pre style='padding:10px;'><? is_array($o) ? print_r($o) : print_r(htmlspecialcharsbx($o)) ?></pre>
        </div>
        <? if ($die == true) {
            die();
        } ?>
        <?
    } else {
        return false;
    }
}

\Bitrix\Main\EventManager::getInstance()->addEventHandler(
    'sale',
    'OnSaleOrderBeforeSaved',
    'OnSaleOrderBeforeSavedFunction'
);

//в обработчике получаем сумму, с которой планируются некоторые действия в дальнейшем:

function OnSaleOrderBeforeSavedFunction(\Bitrix\Main\Event $event)
{
    /** @var Order $order */
    $order = $event->getParameter("ENTITY");
    $oldValues = $event->getParameter("VALUES");
    $paymentCollection = $order->getPaymentCollection();
    $shipmentCollection = $order->getShipmentCollection();
    $sucs = false;
    $split_force = false;

    $Price = $order->getPrice();
    $PriceDelivery = $order->getDeliveryPrice();
    $PriceDiscont = $order->getDiscountPrice();
    $PriceWithoutDel = ($Price - $PriceDelivery);
    $propertyCollection = $order->getPropertyCollection();
    $arPropertyCollection = $propertyCollection->getArray();
    foreach ($arPropertyCollection['properties'] as $props) {
        if ($props['CODE'] == 'SPLIT_PAY') {
            $SPLIT_PAY_property = $propertyCollection->getItemByOrderPropertyId($props['ID']);
            $SPLIT_PAY = $SPLIT_PAY_property->getValue();
        }
        if ($props['CODE'] == 'WANT_SPEND') {
            $want_send_property = $propertyCollection->getItemByOrderPropertyId($props['ID']);
            $want_spend = $want_send_property->getValue();
        }
        if ($props['CODE'] == 'PHONE') {
            $PHONE_property = $propertyCollection->getItemByOrderPropertyId($props['ID']);
            $PHONE = $PHONE_property->getValue();
        }
    }

    if ($PriceDelivery > 0) {
        foreach ($paymentCollection as $payment) {
            if ($payment->getPaymentSystemId() == 7 || $payment->getPaymentSystemId() == 15) {
                $split_force = true;
                $PriceDelivery = round($PriceDelivery + $PriceWithoutDel * 0.019, 2);
                break;
            }
        }
    }
    if ($split_force) {
        $colPay = 0;
        foreach ($paymentCollection as $key => $payment) {
            $colPay++;
        }
        if ($SPLIT_PAY != "Y" && $colPay == 1) {
            foreach ($paymentCollection as $key => $payment) {

                $service = \Bitrix\Sale\PaySystem\Manager::getObjectById(8);
                $serviceOld = \Bitrix\Sale\PaySystem\Manager::getObjectById($payment->getPaymentSystemId());
                $r = $payment->delete();
                if (!$r->isSuccess()) {
                    var_dump($r->getErrorMessages());
                }
                $newMainPayment = $paymentCollection->createItem($serviceOld);
                $newMainPayment->setField('SUM', $PriceWithoutDel);
                $newPayment = $paymentCollection->createItem($service);
                $newPayment->setField('SUM', $PriceDelivery);

            }
            foreach ($shipmentCollection as $shipment) {
                if (!$shipment->isSystem()) {
                    $shipment->setBasePriceDelivery($PriceDelivery + $PriceWithoutDel, false);
                }
            }
            if ($SPLIT_PAY_property) {
                $SPLIT_PAY_property->setValue("Y");
            }
            $sucs = true;


            foreach ($shipmentCollection as $shipment) {
                if (!$shipment->isSystem())
                    $shipment->setBasePriceDelivery($PriceDelivery, false);
            }
        }
    }
    if (!empty($PHONE)) {
        $PHONE = str_replace([' ', '(', ')', '-'], '', $PHONE);
        $PHONE_property->setValue($PHONE);
    }
    $budget = intval($_SESSION['USER_ACCOUNT']['CURRENT_BUDGET']);
    if ($budget) {
        if (!$paymentCollection->getInnerPayment()) {
            if ($PriceDiscont == 0) {
                $proc_commision = 0.30;
            } else {
                $proc_commision = $PriceDiscont / $PriceWithoutDel;//Возможно плохо будет считаеть
            }
            $comision = intval($PriceWithoutDel * $proc_commision);
            if ($comision > $budget) {
                $comision = $budget;
            }
            if ($want_spend > $comision) {
                $want_spend = $comision;
                $want_send_property->setValue($comision);
            }
            foreach ($paymentCollection as $payment) {
                $service = \Bitrix\Sale\PaySystem\Manager::getObjectById($payment->getPaymentSystemId());
                $r = $payment->delete();
                if (!$r->isSuccess()) {
                    var_dump($r->getErrorMessages());
                }
                $newMainPayment = $paymentCollection->createItem($service);
                $newMainPayment->setField('SUM', ($PriceWithoutDel - $want_spend));
                break;
            }
            $newPayment = $paymentCollection->createInnerPayment();
            $newPayment->setField('SUM', $want_spend);
            $newPayment->setField('PAID', 'Y');
            $sucs = true;
        }
    }
    if ($sucs) {
        $event->addResult(
            new \Bitrix\Main\EventResult(
                \Bitrix\Main\EventResult::SUCCESS, $order
            )
        );
    }
}

\Bitrix\Main\EventManager::getInstance()->addEventHandler('sale', 'OnSaleComponentOrderJsData', 'OnSaleComponentOrderJsDataHandler');

function OnSaleComponentOrderJsDataHandler(&$arResult, &$arParams)
{
    global $USER;
    $changeDelivery = false;
    foreach ($arResult['JS_DATA']['PAY_SYSTEM'] as $keyDOST => $JS_DATUM) {
        if ($JS_DATUM['CHECKED'] == 'Y' && ($JS_DATUM['ID'] == "7" || $JS_DATUM['ID'] == "15")) {
            $changeDelivery = true;
            $keyDOSTEx = $keyDOST;
        }
    }

    if ($changeDelivery) {
        $delPrice = $arResult['JS_DATA']['TOTAL']['DELIVERY_PRICE'];
        $orderPerc = round($arResult['JS_DATA']['TOTAL']['ORDER_PRICE'] * 0.019, 2);
        if ($delPrice > 0) {
            $arResult['JS_DATA']['TOTAL']['DELIVERY_PRICE'] = $orderPerc + $delPrice;
            $arResult['JS_DATA']['TOTAL']['DELIVERY_PRICE_FORMATED'] = $arResult['JS_DATA']['TOTAL']['DELIVERY_PRICE'] . " &#8381;";
        }
        $arResult['JS_DATA']['TOTAL']['ORDER_TOTAL_PRICE'] = $arResult['JS_DATA']['TOTAL']['DELIVERY_PRICE'] + $arResult['JS_DATA']['TOTAL']['ORDER_PRICE'];
        $arResult['JS_DATA']['TOTAL']['ORDER_TOTAL_PRICE_FORMATED'] = $arResult['JS_DATA']['TOTAL']['ORDER_TOTAL_PRICE'] . " &#8381;";
        foreach ($arResult['JS_DATA']['DELIVERY'] as $keyDEL => $JS_DEL) {
            if ($JS_DEL['PRICE'] > 0) {
                $arResult['JS_DATA']['DELIVERY'][$keyDEL]['PRICE'] = $JS_DEL['PRICE'] + $orderPerc;
                $arResult['JS_DATA']['DELIVERY'][$keyDEL]['PRICE_FORMATED'] = $arResult['JS_DATA']['DELIVERY'][$keyDEL]['PRICE'] . " &#8381;";
            }
        }
    }
    echo '<pre>';
    echo print_r($arResult['JS_DATA']['TOTAL']['ORDER_TOTAL_PRICE']);
    echo '</pre>';
    if ($USER->IsAuthorized()) {
        $USER_ID = $USER->GetID();

        $dbUserAccount = CSaleUserAccount::GetList([], ["USER_ID" => $USER_ID, "CURRENCY" => "RUB",]);
        $_SESSION['USER_ACCOUNT'] = $dbUserAccount->Fetch();
        $budget = floatval($_SESSION['USER_ACCOUNT']['CURRENT_BUDGET']);
        if ($budget > 0) {
            $arResult['JS_DATA']['TOTAL']["WANT_SPEND"] = 0;
            foreach ($arResult['JS_DATA']['ORDER_PROP']['properties'] as $props) {
                if ($props['CODE'] == 'WANT_SPEND') {
                    $arResult["WANT_SPEND_ID"] = $props['ID'];
                    $arResult['JS_DATA']['TOTAL']["WANT_SPEND"] = intval($props['VALUE'][0]);
                }
                if ($props['CODE'] == 'UTM_SOURCE') {
                    $arResult["UTM_SOURCE_ID"] = $props['ID'];
                }
                if ($props['CODE'] == 'utm_medium') {
                    $arResult["UTM_MEDIUM_ID"] = $props['ID'];
                }
                if ($props['CODE'] == 'utm_campaign') {
                    $arResult["UTM_CAMPAIGN_ID"] = $props['ID'];
                }
                if ($props['CODE'] == 'utm_content') {
                    $arResult["UTM_CONTENT_ID"] = $props['ID'];
                }
                if ($props['CODE'] == 'utm_term') {
                    $arResult["UTM_TERM_ID"] = $props['ID'];
                }
            }
            $orderPrice = $arResult['JS_DATA']['TOTAL']['PRICE_WITHOUT_DISCOUNT_VALUE'];
            if ($arResult['JS_DATA']['TOTAL']['BASKET_PRICE_DISCOUNT_DIFF_VALUE'] == 0) {
                $proc_commision = 0.30;
            } else {
                $proc_commision = $arResult['JS_DATA']['TOTAL']['BASKET_PRICE_DISCOUNT_DIFF_VALUE'] / $arResult['JS_DATA']['TOTAL']['PRICE_WITHOUT_DISCOUNT_VALUE'];
            }
            $comision = intval($orderPrice * $proc_commision);
            if ($comision > $budget) {
                $comision = $budget;
            }
            $want_spend = $arResult['JS_DATA']['TOTAL']["WANT_SPEND"];
            $arResult['JS_DATA']['TOTAL']['COMISION'] = $comision;


            if ($want_spend > $comision) {
                $want_spend = $comision;
                $arResult['JS_DATA']['TOTAL']["WANT_SPEND"] = $comision;
            }
            $arResult['JS_DATA']['CURRENT_BUDGET'] = intval($budget);
            $arResult['JS_DATA']['CURRENT_BUDGET_FORMATED'] = $arResult['JS_DATA']['CURRENT_BUDGET'] . " &#8381;";
            $arResult['JS_DATA']['TOTAL']['ORDER_TOTAL_LEFT_TO_PAY'] = $orderPrice - $want_spend;
            $arResult['JS_DATA']['TOTAL']['ORDER_TOTAL_LEFT_TO_PAY_FORMATED'] = $arResult['JS_DATA']['TOTAL']['ORDER_TOTAL_LEFT_TO_PAY'] . " &#8381;";
            $arResult['JS_DATA']['TOTAL']['PAYED_FROM_ACCOUNT_FORMATED'] = $want_spend . "&#8381;";

        }

    }

}

AddEventHandler("main", "OnAfterUserRegister", array("UserAuthRegister", "OnAfterUserRegisterHandler"));
AddEventHandler("main", "OnAfterUserLogin", array("UserAuthRegister", "OnAfterUserLoginHandler"));
AddEventHandler("main", "OnBeforeUserLogin", array("UserAuthRegister", "OnBeforeUserLoginHandler"));
AddEventHandler("main", "OnBeforeUserSendPassword", array("UserAuthRegister", "OnBeforeUserSendPasswordHandler"));
AddEventHandler("main", "OnBeforeUserChangePassword", array("UserAuthRegister", "OnBeforeUserChangePasswordHandler"));
AddEventHandler("main", "OnAfterUserUpdate", array("UserAuthRegister", "OnAfterUserUpdateHandler"));

class UserAuthRegister
{
    function OnAfterUserRegisterHandler(&$arFields)
    {

        //obStartFields($arFields, 'OnAfterUserRegisterHandler');
    }

    function OnBeforeUserChangePasswordHandler(&$arFields)
    {
        //obStartFields($arFields, 'OnBeforeUserChangePasswordHandler');
    }

    function OnAfterUserLoginHandler(&$arFields)
    {
        getPersonalInfo($arFields['USER_ID']);
        //obStartFields($arFields, 'OnAfterUserLoginHandler');
    }

    function OnBeforeUserLoginHandler(&$arFields)
    {
        //obStartFields($arFields, 'OnBeforeUserLoginHandler');
    }

    function OnBeforeUserSendPasswordHandler(&$arFields)
    {

        //obStartFields($arFields, 'OnBeforeUserSendPasswordHandler');

    }

    function OnAfterUserUpdateHandler(&$arFields)
    {

        //obStartFields($arFields, 'OnAfterUserUpdateHandler');
    }
}

function getPersonalInfo($ID)
{
    global $DB;
    CModule::IncludeModule('sale');
    $arFilter = Array(
        "USER_ID" => $ID,
        "<=DATE_INSERT" => date($DB->DateFormatToPHP(CSite::GetDateFormat("SHORT")), strtotime('-2 minutes')),
        "PAYED" => "Y"
    );
    $db_sales = CSaleOrder::GetList(array("DATE_INSERT" => "ASC"), $arFilter);
    while ($ar_sales = $db_sales->Fetch()) {

        $order = \Bitrix\Sale\Order::load($ar_sales['ID']);
        if ($order->isPaid()) {

            $propertyCollection = $order->getPropertyCollection();
            $arPropertyCollection = $propertyCollection->getArray();
            foreach ($arPropertyCollection['properties'] as $props) {

                if ($props['CODE'] == 'ADD_BONUS') {
                    $ADD_BONUS_property = $propertyCollection->getItemByOrderPropertyId($props['ID']);
                    $ADD_BONUS = $ADD_BONUS_property->getValue();

                }

            }
            if ($ADD_BONUS == 'N') {
                $Price = $order->getPrice();
                $PriceDelivery = $order->getDeliveryPrice();
                $PriceWithoutDel = ($Price - $PriceDelivery);
                CSaleUserAccount::UpdateAccount(
                    $ID,
                    $PriceWithoutDel / 100,
                    "RUB",
                    "AUTO_ADD",
                    $ar_sales['ID']
                );

                $ADD_BONUS_property->setValue("Y");
                $order->save();
            }
        }
    }

}

function obStartFields($arFields, $name)
{
    ob_start();
    print_r($arFields);
    $dump = ob_get_clean();

    $filename = $_SERVER['DOCUMENT_ROOT'] . '/upload/dump/' . $name . '.html';
    if (!file_exists($filename)) {
        $f = fopen($filename, 'w+');
        fclose($f);
    }
    file_put_contents($filename, $dump);
}

AddEventHandler("iblock", "OnBeforeIBlockSectionUpdate", Array("SalesHandlerClass", "DoNotUpdateSect"));
AddEventHandler("iblock", "OnBeforeIBlockElementUpdate", Array("SalesHandlerClass", "OnBeforeIBlockElementUpdate"));
AddEventHandler("sale", "OnOrderUpdate", Array("SalesHandlerClass", "OnOrderUpdateFunc"));

class SalesHandlerClass
{
    function OnBeforeIBlockElementUpdate(&$arFields)
    {
        if (isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'import') {
            unset($arFields['ACTIVE']);
        }
    }

    function DoNotUpdateSect(&$arFields)
    {
        if ($_REQUEST['mode'] == 'import') {

            unset($arFields['ACTIVE']);

        }
    }

    function OnOrderUpdateFunc($ID, &$arFields)
    {
        getPersonalInfo($arFields['USER_ID']);
        if ($arFields['ORDER_PROP'][52]) {
            $arEventFields['CDEK_FOLLOW'] = $arFields['ORDER_PROP'][52];
        }
        if ($arFields['ORDER_PROP'][65]) {
            $arEventFields['LINK_FOLLOW'] = $arFields['ORDER_PROP'][65];
        }
        if ($arFields['ORDER_PROP'][21]) {
            $arEventFields['EMAIL'] = $arFields['ORDER_PROP'][21];
        }
        if ($arFields['ORDER_PROP'][52] || $arFields['ORDER_PROP'][65]) {
            $arEventFields['SALE_EMAIL'] = 'order@dibidishop.ru';
            CEvent::SendImmediate("FOLLOW_ORDER", s2, $arEventFields, "N", 129);
        }
        //obStartFields($arFields, 'OnOrderUpdateFunc');
    }
}

AddEventHandler("sale", "OnOrderNewSendEmail", "ModifyOrderSaleMails");
function ModifyOrderSaleMails($orderID, &$eventName, &$arFields)
{
    if (CModule::IncludeModule("sale") && CModule::IncludeModule("iblock")) {
        //СОСТАВ ЗАКАЗА РАЗБИРАЕМ SALE_ORDER НА ЗАПЧАСТИ

        $strOrderList = "";
        $dbBasketItems = CSaleBasket::GetList(
            array("NAME" => "ASC"),
            array("ORDER_ID" => $orderID),
            false,
            false,
            array("PRODUCT_ID", "ID", "NAME", "QUANTITY", "PRICE", "CURRENCY")
        );
        /* $order = Sale\Order::load($orderID);
         $propertyCollection = $order->getPropertyCollection();
         $arPropertyCollection = $propertyCollection->getArray();
         foreach ($arPropertyCollection['properties'] as $props) {
             if ($props['CODE'] == 'CDEK_FOLLOW') {
                 $CDEK_FOLLOW_property = $propertyCollection->getItemByOrderPropertyId($props['ID']);
                 $CDEK_FOLLOW = $CDEK_FOLLOW_property->getValue();
                 $arFields['CDEK_FOLLOW'] = $CDEK_FOLLOW;
             }
             if ($props['CODE'] == 'LINK_FOLLOW') {
                 $LINK_FOLLOW_property = $propertyCollection->getItemByOrderPropertyId($props['ID']);
                 $LINK_FOLLOW = $LINK_FOLLOW_property->getValue();
                 $arFields['LINK_FOLLOW'] = $LINK_FOLLOW;
             }
         }*/
        while ($arProps = $dbBasketItems->Fetch()) {
            //ПЕРЕМНОЖАЕМ КОЛИЧЕСТВО НА ЦЕНУ
            $summ = $arProps['QUANTITY'] * $arProps['PRICE'];
            //СОБИРАЕМ В СТРОКУ ТАБЛИЦЫ
            $strCustomOrderList .= $arProps['NAME'] . " количество: " . $arProps['QUANTITY'] . "<br> стоимость: " . round($arProps['PRICE'], 2) . " " . $arProps['CURRENCY'] . "<br> сумма: " . round($summ, 2) . " " . $arProps['CURRENCY'] . "<br>";
        }
        //ОБЪЯВЛЯЕМ ПЕРЕМЕННУЮ ДЛЯ ПИСЬМА
        $arFields["ORDER_TABLE_ITEMS"] = $strCustomOrderList;
        obStartFields($arFields, 'ModifyOrderSaleMails');
    }
}

CModule::AddAutoloadClasses(
    '',
    array(
        'ChigovMeta' => '/local/php_interface/include/meta_template.php'
    )
);

AddEventHandler("main", "OnEpilog", array("ChigovMeta", "MetaTemplateOnEpilog"));

function RegisterCheck(&$arFields)
{
    if ($arFields["USER_ID"] > 0) {
        session_start();
        $_SESSION['register_ok'] = 1;
    }
}

AddEventHandler("main", "OnAfterUserRegister", "RegisterCheck");

Loader::registerAutoLoadClasses(null, [
    '\Bitrix\IGWD\Image' => '/local/php_interface/include/IGWD/Image.php',
]);