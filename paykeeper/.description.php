<?
use \Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);
$status_list = array();
$dbStatuses = CSaleStatus::GetList();
while ($status_item = $dbStatuses->Fetch()) {
    $status_list[$status_item["ID"]] = $status_item["NAME"];
}
$data = array(
	'NAME' => 'PayKeeper',
	'SORT' => 100,
	'CODES' => array(
		"PAYKEEPER_FORM_URL" => array(
			"NAME" => Loc::getMessage("SALE_HPS_PAYKEEPER_FORM_URL"),
            "DESCRIPTION" => Loc::getMessage("SALE_HPS_PAYKEEPER_FORM_URL_DESCR"),
			'GROUP' => 'PAYKEEPER_SETTINGS',
			'SORT' => 100,
		),
		"PAYKEEPER_LK_LOGIN" => array(
			"NAME" => Loc::getMessage("SALE_HPS_PAYKEEPER_LK_LOGIN"),
            "DESCRIPTION" => Loc::getMessage("SALE_HPS_PAYKEEPER_LK_LOGIN_DESCR"),
			'GROUP' => 'PAYKEEPER_SETTINGS',
			'SORT' => 200,
		),
		"PAYKEEPER_LK_PASSWORD" => array(
			"NAME" => Loc::getMessage("SALE_HPS_PAYKEEPER_LK_PASSWORD"),
            "DESCRIPTION" => Loc::getMessage("SALE_HPS_PAYKEEPER_LK_PASSWORD_DESCR"),
			'GROUP' => 'PAYKEEPER_SETTINGS',
			'SORT' => 300,
		),
		"PAYKEEPER_SECRET" => array(
			"NAME" => Loc::getMessage("SALE_HPS_PAYKEEPER_SECRET"),
			'GROUP' => 'PAYKEEPER_SETTINGS',
			'SORT' => 400,
		),
		"PAYKEEPER_PSTYPE" => array(
			"NAME" => Loc::getMessage("SALE_HPS_PAYKEEPER_PSTYPE"),
			'GROUP' => 'PAYKEEPER_SETTINGS',
			'SORT' => 500,
		),
        'PAYKEEPER_STATUS_AFTER_PAYMENT' => array(
            'NAME' => Loc::getMessage('SALE_HPS_PAYKEEPER_STATUS_AFTER_PAYMENT'),
            'SORT' => 600,
            'GROUP' => 'PAYKEEPER_SETTINGS',
            'INPUT' => array(
                'TYPE' => 'ENUM',
                'OPTIONS' => $status_list
            )
        )
	)
);
