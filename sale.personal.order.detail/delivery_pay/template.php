<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

use Bitrix\Main\Localization\Loc,
    Bitrix\Main\Page\Asset;

if ($arParams['GUEST_MODE'] !== 'Y')
{
    Asset::getInstance()->addJs("/bitrix/components/bitrix/sale.order.payment.change/templates/.default/script.js");
    Asset::getInstance()->addCss("/bitrix/components/bitrix/sale.order.payment.change/templates/.default/style.css");
}
$this->addExternalCss("/bitrix/css/main/bootstrap.css");

CJSCore::Init(array('clipboard', 'fx'));

if (!empty($arResult['ERRORS']['FATAL']))
{
    foreach ($arResult['ERRORS']['FATAL'] as $error)
    {
        ShowError($error);
    }

    $component = $this->__component;

    if ($arParams['AUTH_FORM_IN_TEMPLATE'] && isset($arResult['ERRORS']['FATAL'][$component::E_NOT_AUTHORIZED]))
    {
        $APPLICATION->AuthForm('', false, false, 'N', false);
    }
}
else
{
    if (!empty($arResult['ERRORS']['NONFATAL']))
    {
        foreach ($arResult['ERRORS']['NONFATAL'] as $error)
        {
            ShowError($error);
        }
    }
    ?>

                        <div class="sale-order-detail-payment-options-inner-container">


                                <div class="col-md-12 col-sm-12 col-xs-12 sale-order-detail-payment-options-methods-container">
                                    <?
                                    foreach ($arResult['PAYMENT'] as $payment)
                                    {
                                        if ($payment["PAY_SYSTEM_ID"] != 15){
                                        ?>
                                        <div class="row payment-options-methods">
                                            <div class="col-md-12 col-sm-12 col-xs-12 sale-order-detail-payment-options-methods">
                                                <div class="row sale-order-detail-payment-options-methods-information-block">
                                                    <div class="col-md-2 col-sm-5 col-xs-12 sale-order-detail-payment-options-methods-image-container">
													<span class="sale-order-detail-payment-options-methods-image-element"
                                                          style="background-image: url('<?= $payment['PAY_SYSTEM']["SRC_LOGOTIP"] <> ''? htmlspecialcharsbx($payment['PAY_SYSTEM']["SRC_LOGOTIP"]) : '/bitrix/images/sale/nopaysystem.gif'?>');"></span>
                                                    </div>
                                                    <div class="col-md-8 col-sm-7 col-xs-10 sale-order-detail-payment-options-methods-info">
                                                        <div class="sale-order-detail-payment-options-methods-info-title">
                                                            <div class="sale-order-detail-methods-title">
                                                                <?
                                                                $paymentData[$payment['ACCOUNT_NUMBER']] = array(
                                                                    "payment" => $payment['ACCOUNT_NUMBER'],
                                                                    "order" => $arResult['ACCOUNT_NUMBER'],
                                                                    "allow_inner" => $arParams['ALLOW_INNER'],
                                                                    "only_inner_full" => $arParams['ONLY_INNER_FULL'],
                                                                    "refresh_prices" => $arParams['REFRESH_PRICES'],
                                                                    "path_to_payment" => $arParams['PATH_TO_PAYMENT']
                                                                );
                                                                $paymentSubTitle = Loc::getMessage('SPOD_TPL_BILL')." ".Loc::getMessage('SPOD_NUM_SIGN').$payment['ACCOUNT_NUMBER'];
                                                                if(isset($payment['DATE_BILL']))
                                                                {
                                                                    $paymentSubTitle .= " ".Loc::getMessage('SPOD_FROM')." ".$payment['DATE_BILL_FORMATED'];
                                                                }
                                                                $paymentSubTitle .=",";
                                                                echo htmlspecialcharsbx($paymentSubTitle);
                                                                ?>
                                                                <span class="sale-order-list-payment-title-element"><?=$payment['PAY_SYSTEM_NAME']?></span>
                                                                <?
                                                                if ($payment['PAID'] === 'Y')
                                                                {
                                                                    ?>
                                                                    <span class="sale-order-detail-payment-options-methods-info-title-status-success">
																	<?=Loc::getMessage('SPOD_PAYMENT_PAID')?></span>
                                                                    <?
                                                                }
                                                                elseif ($arResult['IS_ALLOW_PAY'] == 'N')
                                                                {
                                                                    ?>
                                                                    <span class="sale-order-detail-payment-options-methods-info-title-status-restricted">
																	<?=Loc::getMessage('SPOD_TPL_RESTRICTED_PAID')?></span>
                                                                    <?
                                                                }
                                                                else
                                                                {
                                                                    ?>
                                                                    <span class="sale-order-detail-payment-options-methods-info-title-status-alert">
																	<?=Loc::getMessage('SPOD_PAYMENT_UNPAID')?></span>
                                                                    <?
                                                                }
                                                                ?>
                                                            </div>
                                                        </div>
                                                        <div class="sale-order-detail-payment-options-methods-info-total-price">
                                                            <span class="sale-order-detail-sum-name"><?= Loc::getMessage('SPOD_ORDER_PRICE_BILL')?>:</span>
                                                            <span class="sale-order-detail-sum-number"><?=$payment['PRICE_FORMATED']?></span>
                                                        </div>
                                                        <?
                                                        if (!empty($payment['CHECK_DATA']))
                                                        {
                                                            $listCheckLinks = "";
                                                            foreach ($payment['CHECK_DATA'] as $checkInfo)
                                                            {
                                                                $title = Loc::getMessage('SPOD_CHECK_NUM', array('#CHECK_NUMBER#' => $checkInfo['ID']))." - ". htmlspecialcharsbx($checkInfo['TYPE_NAME']);
                                                                if ($checkInfo['LINK'] <> '')
                                                                {
                                                                    $link = $checkInfo['LINK'];
                                                                    $listCheckLinks .= "<div><a href='$link' target='_blank'>$title</a></div>";
                                                                }
                                                            }
                                                            if ($listCheckLinks <> '')
                                                            {
                                                                ?>
                                                                <div class="sale-order-detail-payment-options-methods-info-total-check">
                                                                    <div class="sale-order-detail-sum-check-left"><?= Loc::getMessage('SPOD_CHECK_TITLE')?>:</div>
                                                                    <div class="sale-order-detail-sum-check-left">
                                                                        <?=$listCheckLinks?>
                                                                    </div>
                                                                </div>
                                                                <?
                                                            }
                                                        }
                                                        if (
                                                            $payment['PAID'] !== 'Y'
                                                            && $arResult['CANCELED'] !== 'Y'
                                                            && $arParams['GUEST_MODE'] !== 'Y'
                                                            && $arResult['LOCK_CHANGE_PAYSYSTEM'] !== 'Y'
                                                        )
                                                        {
                                                            ?>
                                                            <a href="#" id="<?=$payment['ACCOUNT_NUMBER']?>" class="sale-order-detail-payment-options-methods-info-change-link"><?=Loc::getMessage('SPOD_CHANGE_PAYMENT_TYPE')?></a>
                                                            <?
                                                        }
                                                        ?>
                                                        <?
                                                        if ($arResult['IS_ALLOW_PAY'] === 'N' && $payment['PAID'] !== 'Y')
                                                        {
                                                            ?>
                                                            <div class="sale-order-detail-status-restricted-message-block">
                                                                <span class="sale-order-detail-status-restricted-message"><?=Loc::getMessage('SOPD_TPL_RESTRICTED_PAID_MESSAGE')?></span>
                                                            </div>
                                                            <?
                                                        }
                                                        ?>
                                                    </div>
                                                    <?
                                                    if ($payment['PAY_SYSTEM']['IS_CASH'] !== 'Y' && $payment['PAY_SYSTEM']['ACTION_FILE'] !== 'cash')
                                                    {
                                                        ?>
                                                        <div class="col-md-2 col-sm-12 col-xs-12 sale-order-detail-payment-options-methods-button-container">
                                                            <?
                                                            if ($payment['PAY_SYSTEM']['PSA_NEW_WINDOW'] === 'Y' && $arResult["IS_ALLOW_PAY"] !== "N")
                                                            {
                                                                ?>
                                                                <a class="btn-theme sale-order-detail-payment-options-methods-button-element-new-window"
                                                                   target="_blank"
                                                                   href="<?=htmlspecialcharsbx($payment['PAY_SYSTEM']['PSA_ACTION_FILE'])?>">
                                                                    <?= Loc::getMessage('SPOD_ORDER_PAY') ?>
                                                                </a>
                                                                <?
                                                            }
                                                            else
                                                            {
                                                                if ($payment["PAID"] === "Y" || $arResult["CANCELED"] === "Y" || $arResult["IS_ALLOW_PAY"] === "N")
                                                                {
                                                                    ?>
                                                                    <a class="btn-theme sale-order-detail-payment-options-methods-button-element inactive-button"><?= Loc::getMessage('SPOD_ORDER_PAY') ?></a>
                                                                    <?
                                                                }
                                                                else
                                                                {
                                                                    ?>
                                                                    <a class="btn-theme sale-order-detail-payment-options-methods-button-element active-button"><?= Loc::getMessage('SPOD_ORDER_PAY') ?></a>
                                                                    <?
                                                                }
                                                            }
                                                            ?>
                                                        </div>
                                                        <?
                                                    }
                                                    ?>
                                                    <div class="sale-order-detail-payment-inner-row-template col-md-offset-3 col-sm-offset-5 col-md-5 col-sm-10 col-xs-12"></div>
                                                </div>
                                                <?
                                                if ($payment["PAID"] !== "Y"
                                                    && $payment['PAY_SYSTEM']["IS_CASH"] !== "Y"
                                                    && $payment['PAY_SYSTEM']['ACTION_FILE'] !== 'cash'
                                                    && $payment['PAY_SYSTEM']['PSA_NEW_WINDOW'] !== 'Y'
                                                    && $arResult['CANCELED'] !== 'Y'
                                                    && $arResult["IS_ALLOW_PAY"] !== "N")
                                                {
                                                    ?>
                                                    <div class="row sale-order-detail-payment-options-methods-template col-md-12 col-sm-12 col-xs-12">
														<span class="sale-paysystem-close active-button">
															<span class="sale-paysystem-close-item sale-order-payment-cancel">
                                                                <svg xmlns="http://www.w3.org/2000/svg" width="15" height="45" viewBox="0 0 15 45">
                                                                  <g fill="none" fill-rule="evenodd">
                                                                    <polygon fill="#D0011B" points="8.878 6.678 8.878 2.278 6.678 2.278 6.678 6.678 2.278 6.678 2.278 8.878 6.678 8.878 6.678 13.278 8.878 13.278 8.878 8.878 13.278 8.878 13.278 6.678" transform="rotate(45 7.778 7.778)"/>
                                                                  </g>
                                                                </svg>

                                                            </span>
														</span>
                                                        <?=$payment['BUFFERED_OUTPUT']?>
                                                    </div>
                                                    <?
                                                }
                                                ?>
                                            </div>
                                        </div>
                                        <?
                                    }
                                    }
                                    ?>
                                </div>

                        </div>



    <?
    $javascriptParams = array(
        "url" => CUtil::JSEscape($this->__component->GetPath().'/ajax.php'),
        "templateFolder" => CUtil::JSEscape($templateFolder),
        "templateName" => $this->__component->GetTemplateName(),
        "paymentList" => $paymentData,
        "returnUrl" => $arResult['RETURN_URL'],
    );
    $javascriptParams = CUtil::PhpToJSObject($javascriptParams);
    ?>
    <script>
        BX.Sale.PersonalOrderComponent.PersonalOrderDetail.init(<?=$javascriptParams?>);
    </script>
    <?
}
?>

