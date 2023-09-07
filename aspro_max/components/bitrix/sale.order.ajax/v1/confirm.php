<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Localization\Loc;

/**
 * @var array $arParams
 * @var array $arResult
 * @var $APPLICATION CMain
 */

if ($arParams["SET_TITLE"] == "Y") {
    $APPLICATION->SetTitle(Loc::getMessage("SOA_ORDER_COMPLETE"));
}
?>

<? if (!empty($arResult["ORDER"])):
    if ($orderId = $arResult['ORDER_ID']) {
        $userEmail = "";
        if ($order = \Bitrix\Sale\Order::load($orderId)) {
            /** @var \Bitrix\Sale\PropertyValueCollection $propertyCollection */
            if ($propertyCollection = $order->getPropertyCollection()) {
                //Ищет свойство заказа, у которого есть флажок IS_EMAIL
                if ($propUserEmail = $propertyCollection->getUserEmail()) {
                    $userEmail = $propUserEmail->getValue();
                } else {
                    /** @var \Bitrix\Sale\PropertyValue $orderProperty */
                    foreach ($propertyCollection as $orderProperty) {
                        //Ищет свойство заказа, у которого символьный код например EMAIL
                        if ($orderProperty->getField('CODE') == 'EMAIL') {
                            $userEmail = $orderProperty->getValue();
                            break;
                        }
                    }
                }
            }
        }
        //Если мыло не нашли, но юзер авторизован
        if (!$userEmail && $USER->IsAuthorized()) {
            $userEmail = $USER->GetEmail();
            $userEmail = trim($userEmail);
        }
    }
    ?>

    <table class="sale_order_full_table">
        <tr>
            <td>
                <?= Loc::getMessage("SOA_ORDER_SUC", array(
                    "#ORDER_DATE#" => $arResult["ORDER"]["DATE_INSERT"]->toUserTime()->format('d.m.Y H:i'),
                    "#ORDER_ID#" => $arResult["ORDER"]["ACCOUNT_NUMBER"]
                )) ?>
                <? if (!empty($arResult['ORDER']["PAYMENT_ID"])): ?>
                    <?= Loc::getMessage("SOA_PAYMENT_SUC", array(
                        "#PAYMENT_ID#" => $arResult['PAYMENT'][$arResult['ORDER']["PAYMENT_ID"]]['ACCOUNT_NUMBER']
                    )) ?>
                <? endif ?>
                <? if ($arParams['NO_PERSONAL'] !== 'Y'): ?>
                    <br/><br/>
                    <?= Loc::getMessage('SOA_ORDER_SUC1', ['#LINK#' => $arParams['PATH_TO_PERSONAL']]) ?>
                <? endif; ?>
                <hr>
                <? /* <div class="">Пройдите <a
                            href="#">опрос</a>
</div>*/
                ?>
            </td>
        </tr>
    </table>

    <script src="https://apis.google.com/js/platform.js?onload=renderOptIn" async defer>
    </script>

    <script>

        window.renderOptIn = function () {

            window.gapi.load('surveyoptin', function () {

                window.gapi.surveyoptin.render(
                    {
                        "merchant_id": "146202869",

                        "order_id": "<?=$arResult["ORDER"]["ACCOUNT_NUMBER"]?>",

                        "email": "<?=$userEmail?>",

                        "delivery_country": "ru",

                        "estimated_delivery_date": "<?php echo date('Y-m-d'); ?>",

                    });

            });

        }

        window.___gcfg = {

            lang: 'ru'

        };

    </script>
    <script src="https://apis.google.com/js/platform.js?onload=renderBadge" async defer></script>

    <script>
        window.renderBadge = function () {
            var ratingBadgeContainer = document.createElement("div");
            document.body.appendChild(ratingBadgeContainer);
            window.gapi.load('ratingbadge', function () {
                window.gapi.ratingbadge.render(ratingBadgeContainer, {"merchant_id": 146202869});
            });
        }
    </script>

    <?
    if ($arResult["ORDER"]["IS_ALLOW_PAY"] === 'Y') {
        if (!empty($arResult["PAYMENT"])) {
            foreach ($arResult["PAYMENT"] as $payment) {
                if ($payment["PAID"] != 'Y') {
                    if (!empty($arResult['PAY_SYSTEM_LIST'])
                        && array_key_exists($payment["PAY_SYSTEM_ID"], $arResult['PAY_SYSTEM_LIST'])
                    ) {
                        $arPaySystem = $arResult['PAY_SYSTEM_LIST_BY_PAYMENT_ID'][$payment["ID"]];

                        if (empty($arPaySystem["ERROR"])) {
                            ?>
                            <br/><br/>

                            <table class="sale_order_full_table">
                                <tr>
                                    <td class="ps_logo">
                                        <div class="pay_name"><?= Loc::getMessage("SOA_PAY") ?></div>
                                        <?= CFile::ShowImage($arPaySystem["LOGOTIP"], 100, 100, "border=0\" style=\"width:100px\"", "", false) ?>
                                        <div class="paysystem_name"><?= $arPaySystem["NAME"] ?></div>
                                        <br/>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <? if (strlen($arPaySystem["ACTION_FILE"]) > 0 && $arPaySystem["NEW_WINDOW"] == "Y" && $arPaySystem["IS_CASH"] != "Y"): ?>
                                            <?
                                            $orderAccountNumber = urlencode(urlencode($arResult["ORDER"]["ACCOUNT_NUMBER"]));
                                            $paymentAccountNumber = $payment["ACCOUNT_NUMBER"];
                                            ?>
                                            <script>
                                                window.open('<?=$arParams["PATH_TO_PAYMENT"]?>?ORDER_ID=<?=$orderAccountNumber?>&PAYMENT_ID=<?=$paymentAccountNumber?>');
                                            </script>
                                        <?= Loc::getMessage("SOA_PAY_LINK", array("#LINK#" => $arParams["PATH_TO_PAYMENT"] . "?ORDER_ID=" . $orderAccountNumber . "&PAYMENT_ID=" . $paymentAccountNumber)) ?>
                                        <? if (CSalePdf::isPdfAvailable() && $arPaySystem['IS_AFFORD_PDF']): ?>
                                        <br/>
                                        <?= Loc::getMessage("SOA_PAY_PDF", array("#LINK#" => $arParams["PATH_TO_PAYMENT"] . "?ORDER_ID=" . $orderAccountNumber . "&pdf=1&DOWNLOAD=Y")) ?>
                                        <? endif ?>
                                        <? else: ?>
										<? if ($arResult['ORDER']['PAY_SYSTEM_ID'] == 6||$arResult['ORDER']['PAY_SYSTEM_ID']==13): ?>
                                            <a class="btn btn-primary"
                                               href="/reservation/payment.php?pdf=Y&ORDER_ID=<?= $arResult['ORDER_ID'] ?>">Оплатить</a>
                                        <? else: ?>
                                            <?= $arPaySystem["BUFFERED_OUTPUT"] ?>
                                        <? endif ?>
                                        <? endif ?>
                                    </td>
                                </tr>
                            </table>

                            <?
                        } else {
                            ?>
                            <span style="color:red;"><?= Loc::getMessage("SOA_ORDER_PS_ERROR") ?></span>
                            <?
                        }
                    } else {
                        ?>
                        <span style="color:red;"><?= Loc::getMessage("SOA_ORDER_PS_ERROR") ?></span>
                        <?
                    }
                }
            }
        }
    } else {
        ?>
        <br/><strong><?= $arParams['MESS_PAY_SYSTEM_PAYABLE_ERROR'] ?></strong>
        <?
    }
    ?>

<? else: ?>

    <b><?= Loc::getMessage("SOA_ERROR_ORDER") ?></b>
    <br/><br/>

    <table class="sale_order_full_table">
        <tr>
            <td>
                <?= Loc::getMessage("SOA_ERROR_ORDER_LOST", ["#ORDER_ID#" => htmlspecialcharsbx($arResult["ACCOUNT_NUMBER"])]) ?>
                <?= Loc::getMessage("SOA_ERROR_ORDER_LOST1") ?>
            </td>
        </tr>
    </table>

<? endif ?>