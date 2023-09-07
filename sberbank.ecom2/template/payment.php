<?php
	IncludeModuleLangFile(__FILE__);
?>
<div class="sberbank__wrapper">
	<div class="sberbank__content">
		<?php if (in_array($params['sberbank_result']['errorCode'], array(999, 1, 2, 3, 4, 5, 7, 8))) { ?>
			<span class="sberbank__description"><?=getMessage("SBERBANK_PAYMENT_DESCRIPTION");?>:</span>
			<span class="sberbank__error-code"><?=getMessage("SBERBANK_PAYMENT_ERROR_TITLE");?><?=$params['sberbank_result']['errorCode']?></span>
			<span class="sberbank__error-message"><?=$params['sberbank_result']['errorMessage']?></span>

		<?php } else if ($params['sberbank_result']['payment'] == 1) { ?>

			<span class="sberbank__error-message"><?=getMessage("SBERBANK_PAYMENT_MESSAGE_PAYMENT_ALLREADY");?></span>

		<?php } else if ($params['sberbank_result']['errorCode'] == 0) { ?>

			<? if($params['SBERBANK_HANDLER_AUTO_REDIRECT'] == 'Y') {?>
				<script>
					var needRedirect = true;
					var currentPage = window.location.pathname;
					var auto_redirect_exceptions = JSON.parse('<?=json_encode($params['auto_redirect_exceptions']);?>');
					auto_redirect_exceptions.forEach((element) => {
					  if(currentPage.match(element)) {
					  	needRedirect = false;
					  }
					})
					if(needRedirect) {
						window.location = '<?=$params['payment_link']?>';
					}
				</script>
			<?php } ?>

			<span class="sberbank__price-string"><?=getMessage("SBERBANK_PAYMENT_PAYMENT_TITLE");?>: <b><?=CurrencyFormat($params['SBERBANK_ORDER_AMOUNT'], $params['currency'])?></b></span>
			<a href="<?=$params['payment_link']?>" class="sberbank__payment-link"><?=getMessage("SBERBANK_PAYMENT_PAYMENT_BUTTON_NAME");?></a>
			<span class="sberbank__payment-description"><?=getMessage("SBERBANK_PAYMENT_PAYMENT_DESCRIPTION");?></span>

		<?php } else { ?>
			<span class="sberbank__error-message"><?=getMessage("SBERBANK_PAYMENT_ERROR_MESSAGE_UNDEFIND");?></span>

		<?php } ?>
	</div>
	<div class="sberbank__footer">
		<span class="sberbank__description"><?=getMessage("SBERBANK_PAYMENT_FOOTER_DESCRIPTION");?></span>
	</div>
</div>

<style>
	body .sberbank__wrapper {
		font-family: arial;
		text-align: left;
		margin-bottom: 20px;
		margin-top: 20px;
	}
	body .sberbank__price-block {
		font-family: arial;
		display: block;
		margin: 20px 0px;
	}
	body .sberbank__price-string {
		font-family: arial;
		font-weight: bold;
		font-size: 14px;
	}
	body .sberbank__price-string b {
		font-family: arial;
		font-size: 20px;
	}
	body .sberbank__content {
		font-family: arial;
	    width: 400px;
	    max-width: 100%;
	    padding: 10px 10px 13px;
	    border: 1px solid #e5e5e5;
	    text-align: center;
	    margin-bottom: 12px;
	}
	body .sberbank__payment-link {
		font-family: arial;
		display: inline-block;
		width: 320px;
		max-width: 100%;
		margin: 8px 0 5px;
		background-color: #1eb42f !important;
		color: #fff !important;
		border:none;
		box-shadow: none;
    	outline: none;
    	font-size: 14px;
	    font-weight: normal;
	    line-height: 1.42857143;
	    text-align: center;
    	white-space: nowrap;
    	vertical-align: middle;
    	padding: 6px 12px;
    	text-decoration: none !important;
	}
	body .sberbank__payment-link:hover,body .sberbank__payment-link:active,body .sberbank__payment-link:focus {
		font-family: arial;
		background: #189d27;
		color: #fff;
	}
	body .sberbank__payment-description {
		font-family: arial;
		display: block;
		font-size: 12px;
		color: #939393;
	}
	body .sberbank__description {
		font-family: arial;
		font-size: 12px;
		max-width: 400px;
		display: block;
	}
	body .sberbank__error-code {
		font-family: arial;
		color: red;
		font-size: 20px;
		display: block;
		margin-top:5px;
		margin-bottom: 7px;
	}
	body .sberbank__error-message {
		font-family: arial;
		color:#000;
		font-size: 14px;
		display: block;
	}
</style>