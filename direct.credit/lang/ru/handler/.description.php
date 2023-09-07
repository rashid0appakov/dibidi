<?php
$MESS["SBERBANK_PAYMENT_MODULE_TITLE"] = 'Интернет-эквайринг Сбербанк';
$MESS["SBERBANK_PAYMENT_GROUP_GATE"] = 'Параметры подключения платежного шлюза';
$MESS["SBERBANK_PAYMENT_GROUP_HANDLER"] = 'Параметры платежного обработчика';
$MESS["SBERBANK_PAYMENT_GROUP_ORDER"] = 'Параметры заказа';
//$MESS["SBERBANK_PAYMENT_GROUP_FFD"] = 'Настройки ФФД';
$MESS["SBERBANK_PAYMENT_GROUP_OFD"] = "Отправка чеков";

$MESS["SBERBANK_PAYMENT_API_LOGIN_NAME"] = 'Логин';
$MESS["SBERBANK_PAYMENT_API_LOGIN_DESCR"] = '';
$MESS["SBERBANK_PAYMENT_API_PASSWORD_NAME"] = 'Пароль';
$MESS["SBERBANK_PAYMENT_API_PASSWORD_DESCR"] = '';
$MESS["SBERBANK_PAYMENT_API_TEST_MODE_NAME"] = 'Тестовый режим';
$MESS["SBERBANK_PAYMENT_API_TEST_MODE_DESCR"] = 'Если отмечено, плагин будет работать в тестовом режиме. При пустом значении будет стандартный режим работы.';

$MESS["SBERBANK_PAYMENT_HANDLER_AUTO_REDIRECT_NAME"] = 'Автоматический редирект на форму оплаты';
$MESS["SBERBANK_PAYMENT_HANDLER_AUTO_REDIRECT_DESCR"] = 'Если отмечено, после оформления заказа, покупатель будет автоматически перенаправлен на страницу платежной формы.';
$MESS["SBERBANK_PAYMENT_HANDLER_LOGGING_NAME"] = 'Логирование';
$MESS["SBERBANK_PAYMENT_HANDLER_LOGGING_DESCR"] = 'Если отмечено, плагин будет логировать запросы в файл.';
$MESS["SBERBANK_PAYMENT_HANDLER_TWO_STAGE_NAME"] = 'Двухстадийные платежи';
$MESS["SBERBANK_PAYMENT_HANDLER_TWO_STAGE_DESCR"] = 'Если отмечено, будет производиться двухстадийный платеж. При пустом значении будет производиться одностадийный платеж.';
$MESS["SBERBANK_PAYMENT_HANDLER_SHIPMENT_NAME"] = 'Разрешить отгрузку';
$MESS["SBERBANK_PAYMENT_HANDLER_SHIPMENT_DESCR"] = 'Если отмечено, то после успешной оплаты будет автоматически разрешена отгрузка заказа.';

$MESS["SBERBANK_PAYMENT_ORDER_NUMBER_NAME"] = 'Уникальный идентификатор заказа в магазине';
$MESS["SBERBANK_PAYMENT_ORDER_NUMBER_DESCR"] = '';
$MESS["SBERBANK_PAYMENT_ORDER_AMOUNT_NAME"] = 'Сумма заказа';
$MESS["SBERBANK_PAYMENT_ORDER_AMOUNT_DESCR"] = '';
$MESS["SBERBANK_PAYMENT_ORDER_DESCRIPTION_NAME"] = 'Описание заказа';
$MESS["SBERBANK_PAYMENT_ORDER_DESCRIPTION_DESCR"] = "Поле может содержать метки: #PAYMENT_ID# - ID оплаты, #ORDER_ID# - ID заказа, #PAYMENT_NUMBER# - номер оплаты, \n#ORDER_NUMBER# - номер заказа, #USER_EMAIL# - Email покупателя. (не более 24 символов, запрещены к использованию %, +, конец строки '\\r' и перенос строки '\\n').";


//$MESS["SBERBANK_PAYMENT_FFD_VERSION_NAME"] = 'Формат фискальных документов';
//$MESS["SBERBANK_PAYMENT_FFD_VERSION_DESCR"] = 'Формат версии требуется указать в личном кабинете банка и в кабинете сервиса фискализации';
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_DELIVERY_METHOD_NAME"] = 'Тип оплаты для доставки';
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_METHOD_NAME"] = 'Тип оплаты';
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_METHOD_DESCR"] = '';
$MESS['SBERBANK_PAYMENT_FFD_PAYMENT_METHOD_VALUE_1'] = "Полная предварительная оплата до момента передачи предмета расчёта";
$MESS['SBERBANK_PAYMENT_FFD_PAYMENT_METHOD_VALUE_2'] = "Частичная предварительная оплата до момента передачи предмета расчёта";
$MESS['SBERBANK_PAYMENT_FFD_PAYMENT_METHOD_VALUE_3'] = "Аванс";
$MESS['SBERBANK_PAYMENT_FFD_PAYMENT_METHOD_VALUE_4'] = "Полная оплата в момент передачи предмета расчёта";
$MESS['SBERBANK_PAYMENT_FFD_PAYMENT_METHOD_VALUE_5'] = "Частичная оплата предмета расчёта в момент его передачи с последующей оплатой в кредит";
$MESS['SBERBANK_PAYMENT_FFD_PAYMENT_METHOD_VALUE_6'] = "Передача предмета расчёта без его оплаты в момент его передачи с последующей оплатой в кредит";
$MESS['SBERBANK_PAYMENT_FFD_PAYMENT_METHOD_VALUE_7'] = "Оплата предмета расчёта после его передачи с оплатой в кредит";

$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_OBJECT_NAME"] = 'Тип оплачиваемой позиции';
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_OBJECT_DELIVERY_NAME"] = 'Тип оплачиваемой позиции для доставки';
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_OBJECT_DESCR"] = '';
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_OBJECT_VALUE_1"]  = "Товар";
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_OBJECT_VALUE_2"]  = "Подакцизный товар";
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_OBJECT_VALUE_3"]  = "Работа";
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_OBJECT_VALUE_4"]  = "Услуга";
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_OBJECT_VALUE_5"]  = "Ставка азартной игры";
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_OBJECT_VALUE_6"]  = "Выигрыш азартной игры";
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_OBJECT_VALUE_7"]  = "Лотерейный билет";
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_OBJECT_VALUE_8"]  = "Выигрыш лотереи";
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_OBJECT_VALUE_9"]  = "Предоставление РИД";
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_OBJECT_VALUE_10"] = "Платёж";
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_OBJECT_VALUE_11"] = "Агентское вознаграждение";
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_OBJECT_VALUE_12"] = "Составной предмет расчёта";
$MESS["SBERBANK_PAYMENT_FFD_PAYMENT_OBJECT_VALUE_13"] = "Иной предмет расчёта";


$MESS["SBERBANK_PAYMENT_OFD_RECIEPT_NAME"] = "Чек выпускает банк";
$MESS["SBERBANK_PAYMENT_OFD_RECIEPT_DESCR"] = "Если отмечено, то сформирует и отправит клиенту чек. Опция платная, за подключением обратитесь в сервисную службу банка. При использовании необходимо настроить НДС продаваемых товаров";

$MESS["SBERBANK_PAYMENT_OFD_RECIEPT_VALUE_0"] = "Общая";
$MESS["SBERBANK_PAYMENT_OFD_RECIEPT_VALUE_1"] = "Упрощённая, доход";
$MESS["SBERBANK_PAYMENT_OFD_RECIEPT_VALUE_2"] = "Упрощённая, доход минус расход";
$MESS["SBERBANK_PAYMENT_OFD_RECIEPT_VALUE_3"] = "Единый налог на вменённый доход";
$MESS["SBERBANK_PAYMENT_OFD_RECIEPT_VALUE_4"] = "Единый сельскохозяйственный налог";
$MESS["SBERBANK_PAYMENT_OFD_RECIEPT_VALUE_5"] = "Патентная система налогообложения";


$MESS["SBERBANK_PAYMENT_OFD_TAX_SYSTEM_NAME"] = "Система налогообложения";
$MESS["SBERBANK_PAYMENT_OFD_TAX_SYSTEM_DESCR"] = "";

$MESS["SBERBANK_PAYMENT_RETURN_URL_NAME"] = "Страница, на которую необходимо перенаправить пользователя в случае успешной оплаты";
$MESS["SBERBANK_PAYMENT_RETURN_URL_DESCR"] = "Оставьте это поле пустым, если хотите использовать настройки по умолчанию.";
$MESS["SBERBANK_PAYMENT_FAIL_URL_NAME"] = "Страница, на которую требуется перенаправить пользователя в случае неуспешной оплатыa";
$MESS["SBERBANK_PAYMENT_FAIL_URL_DESCR"] = "Оставьте это поле пустым, если хотите использовать настройки по умолчанию.";