<?php
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();
if ($params["form_type"] == "create") {
    if ($params["detail_page_active"] === false) {
        $autoredir = ($params["noauth_payment_active"] === false) ? 'setTimeout(fSubmit, 0000);' : '';
        $message = $params["message"];
    }
    else {
        $autoredir = "";
        $message = $params["detail_page_message"];
    }
    $form = '
        <h3>'. $message.'</h3> 
        <form id="pay_form" action="' . $params["form_url"] . '" accept-charset="utf-8" method="post">
        <input type="hidden" name="sum" value = "'.$params["sum"].'"/>
        <input type="hidden" name="orderid" value = "'.$params["orderid"].'"/>
        <input type="hidden" name="clientid" value = "'.$params["clientid"].'"/>
        <input type="hidden" name="client_email" value = "'.$params["client_email"].'"/>
        <input type="hidden" name="client_phone" value = "'.$params["client_phone"].'"/>
        <input type="hidden" name="service_name" value = "'.$params["service_name"].'"/>
        <input type="hidden" name="cart" value = \''.htmlentities($params["cart"],ENT_QUOTES).'\' />';
        if (isset($params["pstype"])){
            $form = $form . '<input type="hidden" name="pstype" value = "'.$params["pstype"].'"/>';
        }
        $form = $form.'<input type="hidden" name="lang" value = '.$params["lang"].' />
        <input type="hidden" name="sign" value = "'.$params["sign"].'"/>
        <input type="submit" class="btn btn-default" value="'.$params["pay_button"].'"/>
        </form>
        <script type="text/javascript">
            window.onload=function(){
                '.$autoredir.'
            }
            function fSubmit() {
                '.$params["js_submit_string"].'
            }
        </script>';
    echo '<div id="tmg_pk_form_container"><br>'.$form.'</div>';
}
else if ($params["detail_page_active"] !== false)
    echo $params["detail_page_message"];
