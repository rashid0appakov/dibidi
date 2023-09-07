<?php
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Config\Option;
IncludeModuleLangFile(__FILE__);

Class sberbank_ecom2 extends CModule {

    var $MODULE_ID = 'sberbank.ecom2';
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_PATH;

    var $PAYMENT_HANDLER_PATH;

    function __construct() {
        $path = str_replace("\\", "/", __FILE__);
        $path = substr($path, 0, strlen($path) - strlen("/install/index.php"));

        include($path."/install/version.php");
        include($path."/config.php");
        
        $this->MODULE_PATH = $path;
        $this->MODULE_NAME =  Loc::getMessage('SBERBANK_PAYMENT_MODULE_NAME');
        $this->MODULE_DESCRIPTION = Loc::getMessage('SBERBANK_PAYMENT_MODULE_DESCRIPTION');
        $this->PARTNER_NAME = Loc::getMessage('SBERBANK_PAYMENT_PARTNER_NAME');
        $this->PARTNER_URI = Loc::getMessage('SBERBANK_PAYMENT_PARTNER_URI');
        
        $this->MODULE_VERSION = $arModuleVersion["VERSION"];
        $this->MODULE_VERSION_DATE = $arModuleVersion["VERSION_DATE"];
        
        $ps_dir_path = strlen(COption::GetOptionString('sale', 'path2user_ps_files')) > 3 ? COption::GetOptionString('sale', 'path2user_ps_files') : '/bitrix/php_interface/include/sale_payment/';
        $this->PAYMENT_HANDLER_PATH = $_SERVER["DOCUMENT_ROOT"] . $ps_dir_path . str_replace(".", "_", $this->MODULE_ID) . "/";
    }

    function changeFiles($files) {

        foreach ($files as $file) {
            if ($file->isDot() === false) {
                $path_to_file = $file->getPathname();
                $file_contents = file_get_contents($path_to_file);
                $file_contents = str_replace("{module_path}", $this->MODULE_ID, $file_contents);
                file_put_contents($path_to_file, $file_contents);
            }
        }
    }
    function InstallFiles($arParams = array()) {

        CopyDirFiles($this->MODULE_PATH . "/install/setup/handler_include", $this->PAYMENT_HANDLER_PATH, true, true);
        // CopyDirFiles($this->MODULE_PATH . "/install/setup/sberbank", $_SERVER['DOCUMENT_ROOT'] . '/sberbank/');
        CopyDirFiles($this->MODULE_PATH . "/install/setup/images/logo", $_SERVER['DOCUMENT_ROOT'] . '/bitrix/images/sale/sale_payments/');
        $this->changeFiles(new DirectoryIterator($this->PAYMENT_HANDLER_PATH));
        $this->changeFiles(new DirectoryIterator($this->PAYMENT_HANDLER_PATH . 'template/'));
    }

    function UnInstallFiles() {
        $ps_dir_path = strlen(COption::GetOptionString('sale', 'path2user_ps_files')) > 3 ? COption::GetOptionString('sale', 'path2user_ps_files') : '/bitrix/php_interface/include/sale_payment/';
        DeleteDirFilesEx($ps_dir_path . str_replace(".", "_", $this->MODULE_ID));
    }

    function DoInstall() {
        $this->InstallFiles();
        RegisterModule($this->MODULE_ID);
        COption::SetOptionInt($this->MODULE_ID, "delete", false);

    }

    function DoUninstall() {
        $ps_dir_path = strlen(COption::GetOptionString('sale', 'path2user_ps_files')) > 3 ? COption::GetOptionString('sale', 'path2user_ps_files') : '/bitrix/php_interface/include/sale_payment/';
        COption::SetOptionInt($this->MODULE_ID, "delete", true);
        DeleteDirFilesEx($ps_dir_path . str_replace(".", "_", $this->MODULE_ID));
        DeleteDirFilesEx($this->MODULE_ID);
        UnRegisterModule($this->MODULE_ID);
        return true;        
    }
}

?>