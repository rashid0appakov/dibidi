<?php define("TIMELIMIT_VERSION", true);?><?php
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true)
	die();

//Disable statistics
define("STOP_STATISTICS", true);
define("NO_AGENT_STATISTIC", true);
define("NO_AGENT_CHECK", true);

@set_time_limit(3600);
@ignore_user_abort(true);

define("BX_PRODUCT_INSTALLATION", true);

if (defined("DEBUG_MODE"))
	error_reporting(E_ALL);
else
	error_reporting(E_COMPILE_ERROR|E_ERROR|E_CORE_ERROR|E_PARSE);

if (isset($_REQUEST["clear_db"]))
	setcookie("clear_db", $_REQUEST["clear_db"], time()+3600);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/bx_root.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/lib/loader.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/autoload.php");

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/wizard.php"); //Wizard API
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/version.php"); //Sitemanager version
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/install/wizard/template.php"); //Wizard template
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/install/wizard/utils.php"); //Wizard utils

@set_time_limit(3600);

//wizard customization file
$bxProductConfig = array();
if(file_exists($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/.config.php"))
	include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/.config.php");

//require to register trial and to get trial license key
if(isset($bxProductConfig["saas"]))
{
	define("TRIAL_RENT_VERSION", true);
	define("TRIAL_RENT_VERSION_MAX_USERS", $bxProductConfig["saas"]["max_users"]);
}

//Get Wizard Settings
$GLOBALS["arWizardConfig"] = BXInstallServices::GetWizardsSettings();

if($GLOBALS["arWizardConfig"]["LANGUAGE_ID"])
	define("LANGUAGE_ID", $GLOBALS["arWizardConfig"]["LANGUAGE_ID"]);
else
	define("LANGUAGE_ID", PRE_LANGUAGE_ID);

if($GLOBALS["arWizardConfig"]["INSTALL_CHARSET"])
	define("INSTALL_CHARSET", $GLOBALS["arWizardConfig"]["INSTALL_CHARSET"]);
else
	define("INSTALL_CHARSET", PRE_INSTALL_CHARSET);

require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/charset_converter.php");
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/tools.php");

//Init new kernel
$application = \Bitrix\Main\HttpApplication::getInstance();
$application->initializeBasicKernel();
$context = new \Bitrix\Main\HttpContext($application);
$context->setLanguage(LANGUAGE_ID);
$application->setContext($context);

//Lang files
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/install.php");

//Magic quotes
UnQuoteAll();

bx_accelerator_reset();

class WelcomeStep extends CWizardStep
{
	function InitStep()
	{
		$this->SetStepID("welcome");
		$this->SetNextStep("agreement");
		$this->SetNextCaption(InstallGetMessage("NEXT_BUTTON"));
		$this->SetTitle(InstallGetMessage("INS_STEP1_TITLE"));
	}

	function ShowStep()
	{
		global $arWizardConfig;

		//wizard customization file
		$bxProductConfig = array();
		if(file_exists($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/.config.php"))
			include($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/.config.php");

		if(isset($bxProductConfig["product_wizard"]["welcome_text"]))
			$this->content .= '<div class="inst-cont-text-block"><div class="inst-cont-text">'.$bxProductConfig["product_wizard"]["welcome_text"].'</div></div>';
		else
			$this->content .= '<div class="inst-cont-text-block"><div class="inst-cont-text">'.(isset($arWizardConfig["welcomeText"]) ? $arWizardConfig["welcomeText"] : InstallGetMessage("FIRST_PAGE")).'</div></div>';
	}

	public static function unformat($str)
	{
		$str = strtolower(trim($str));
		$res = intval($str);
		$suffix = substr($str, -1);
		if($suffix == "k")
			$res *= 1024;
		elseif($suffix == "m")
			$res *= 1048576;
		elseif($suffix == "g")
			$res *= 1048576*1024;
		return $res;
	}
}

class AgreementStep extends CWizardStep
{
	function InitStep()
	{
		$this->SetStepID("agreement");
		$this->SetPrevStep("welcome");
		$this->SetNextStep("select_database");
		$this->SetNextCaption(InstallGetMessage("NEXT_BUTTON"));
		$this->SetPrevCaption(InstallGetMessage("PREVIOUS_BUTTON"));
		$this->SetTitle(InstallGetMessage("INS_STEP2_TITLE"));
	}

	function OnPostForm()
	{
		$wizard = $this->GetWizard();

		if ($wizard->IsPrevButtonClick())
			return;

		$agreeLicense = $wizard->GetVar("agree_license");
		if ($agreeLicense !== "Y")
			$this->SetError(InstallGetMessage("ERR_AGREE_LICENSE"), "agree_license");
	}

	function ShowStep()
	{
		$this->content = '<br /><iframe name="license_text" src="/bitrix/legal/license.php" width="100%" height="250" border="0" frameBorder="1" scrolling="yes"></iframe><br /><br />';
		$this->content .= $this->ShowCheckboxField("agree_license", "Y", Array("id" => "agree_license_id", "tabindex" => "1"));
		$this->content .= '&nbsp;<label for="agree_license_id">'.InstallGetMessage("LICENSE_AGREE_PROMT").'</label>';

		$this->content .= '<script type="text/javascript">setTimeout(function() {document.getElementById("agree_license_id").focus();}, 500);</script>';
	}

}

class AgreementStep4VM extends CWizardStep
{
	function InitStep()
	{
		$this->SetStepID("agreement");
		if($_SERVER['BITRIX_ENV_TYPE'] <> "crm")
		{
			$this->SetNextStep("check_license_key");
		}
		else
		{
			$this->SetNextStep("create_modules");
		}
		$this->SetNextCaption(InstallGetMessage("NEXT_BUTTON"));
		$this->SetPrevCaption(InstallGetMessage("PREVIOUS_BUTTON"));
		$this->SetTitle(InstallGetMessage("INS_STEP2_TITLE"));
	}

	function OnPostForm()
	{
		$wizard = $this->GetWizard();

		if ($wizard->IsPrevButtonClick())
			return;

		$agreeLicense = $wizard->GetVar("agree_license");
		if ($agreeLicense !== "Y")
			$this->SetError(InstallGetMessage("ERR_AGREE_LICENSE"), "agree_license");

		$this->CheckShortInstall();
	}

	public function CheckShortInstall()
	{
		$DBType = "mysql";

		//PHP
		$requireStep = new RequirementStep;
		if (!$requireStep->CheckRequirements($DBType))
			$this->SetError($requireStep->GetErrors());

		//UTF-8
		if (defined("BX_UTF") && !BXInstallServices::IsUTF8Support())
			$this->SetError(InstallGetMessage("INST_UTF8_NOT_SUPPORT"));

		//Check connection
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/mysql/database.php");
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/mysql/main.php");
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/tools.php");
		IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/main.php");

		$application = \Bitrix\Main\HttpApplication::getInstance();
		$application->initializeBasicKernel();
		$conPool = $application->getConnectionPool();
		$connection = $conPool->getConnection();

		$conPool->useMasterOnly(true);

		$DB = new CDatabase;

		$DBHost = $connection->getHost();
		$DBName = $connection->getDatabase();
		$DBLogin = $connection->getLogin();
		$DBPassword = $connection->getPassword();

		if (!$DB->Connect($DBHost, $DBName, $DBLogin, $DBPassword))
			$this->SetError(InstallGetMessage("COULD_NOT_CONNECT")." ".$DB->db_Error);

		$databaseStep = new CreateDBStep;
		$databaseStep->DB =& $DB;
		$databaseStep->dbType = $DBType;
		$databaseStep->dbName = $DBName;
		$databaseStep->filePermission = (defined("BX_FILE_PERMISSIONS")? sprintf("%04o", BX_FILE_PERMISSIONS) : 0);
		$databaseStep->folderPermission = (defined("BX_DIR_PERMISSIONS")? sprintf("%04o", BX_DIR_PERMISSIONS) : 0);
		$databaseStep->createDBType = (defined("MYSQL_TABLE_TYPE") ? MYSQL_TABLE_TYPE : "");
		$databaseStep->utf8 = defined("BX_UTF");
		$databaseStep->createCharset = null;
		$databaseStep->needCodePage = false;

		if ($databaseStep->IsBitrixInstalled())
			$this->SetError($databaseStep->GetErrors());

		//Database check
		$dbResult = $DB->Query("select VERSION() as ver", true);
		if ($dbResult && ($arVersion = $dbResult->Fetch()))
		{
			$mysqlVersion = trim($arVersion["ver"]);
			if (!BXInstallServices::VersionCompare($mysqlVersion, "5.6.0"))
				$this->SetError(InstallGetMessage("SC_DB_VERS_MYSQL_ER"));

			$databaseStep->needCodePage = true;

			if (!$databaseStep->needCodePage && defined("BX_UTF"))
				$this->SetError(InstallGetMessage("INS_CREATE_DB_CHAR_NOTE"));
		}

		//Code page
		if ($databaseStep->needCodePage)
		{
			$codePage = false;
			if (LANGUAGE_ID == "ru" || LANGUAGE_ID == "ua")
				$codePage = "cp1251";
			elseif ($databaseStep->createCharset != '')
				$codePage = $databaseStep->createCharset;
			else
				$codePage = 'latin1';

			if ($databaseStep->utf8)
				$DB->Query("ALTER DATABASE `".$databaseStep->dbName."` CHARACTER SET UTF8 COLLATE utf8_unicode_ci", true);
			elseif ($codePage)
				$DB->Query("ALTER DATABASE `".$databaseStep->dbName."` CHARACTER SET ".$codePage, true);
		}

		if ($databaseStep->createDBType <> '')
		{
			$res = $DB->Query("SET storage_engine = '".$databaseStep->createDBType."'", true);
			if(!$res)
			{
				//mysql 5.7 removed storage_engine variable
				$DB->Query("SET default_storage_engine = '".$databaseStep->createDBType."'");
			}
		}

		//SQL mode
		$dbResult = $DB->Query("SELECT @@sql_mode", true);
		if ($dbResult && ($arResult = $dbResult->Fetch()))
		{
			$sqlMode = trim($arResult["@@sql_mode"]);
			if ($sqlMode <> "")
			{
				$databaseStep->sqlMode = "";
			}
		}

		//Create after_connect.php if not exists
		if (!file_exists($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/after_connect_d7.php") && $databaseStep->CreateAfterConnect() === false)
			$this->SetError($databaseStep->GetErrors());

		if (!$databaseStep->CheckDBOperation())
			$this->SetError($databaseStep->GetErrors());
	}

	function SetError($strError, $id = false)
	{
		if(is_array($strError))
		{
			$this->stepErrors = array_merge($this->stepErrors, $strError);
		}
		else
		{
			$this->stepErrors[] = Array($strError, $id);
		}
	}

	function ShowStep()
	{
		$this->content = '<iframe name="license_text" src="/bitrix/legal/license.php" width="100%" height="250" border="0" frameBorder="1" scrolling="yes"></iframe><br /><br />';
		$this->content .= $this->ShowCheckboxField("agree_license", "Y", Array("id" => "agree_license_id", "tabindex" => "1"));
		$this->content .= '&nbsp;<label for="agree_license_id">'.InstallGetMessage("LICENSE_AGREE_PROMT").'</label>';
		$this->content .= '<script type="text/javascript">setTimeout(function() {document.getElementById("agree_license_id").focus();}, 500);</script>';
	}

}

class DBTypeStep extends CWizardStep
{
	function InitStep()
	{
		$this->SetStepID("select_database");
		$this->SetPrevStep("agreement");
		$this->SetNextStep("requirements");
		$this->SetNextCaption(InstallGetMessage("NEXT_BUTTON"));
		$this->SetPrevCaption(InstallGetMessage("PREVIOUS_BUTTON"));

		$arDBTypes = BXInstallServices::GetDBTypes();
		if (count($arDBTypes) > 1)
			$this->SetTitle(InstallGetMessage("INS_DB_SELECTION"));
		else
			$this->SetTitle(InstallGetMessage("INS_LICENSE_HEAD"));

		$wizard = $this->GetWizard();

		if (defined("TRIAL_VERSION") || defined("TRIAL_RENT_VERSION"))
		{
			$wizard->SetDefaultVar("lic_key_variant", "Y");
		}

		if(file_exists($_SERVER['DOCUMENT_ROOT'].'/bitrix/license_key.php'))
		{
			$LICENSE_KEY = '';
			include($_SERVER['DOCUMENT_ROOT'].'/bitrix/license_key.php');
			$wizard->SetDefaultVar("license", $LICENSE_KEY);
		}

		$defaultDbType = "mysql";
		foreach ($arDBTypes as $dbType => $active)
		{
			$defaultDbType = $dbType;
			if ($active)
				break;
		}

		$wizard->SetDefaultVar("dbType", $defaultDbType);
		$wizard->SetDefaultVar("utf8", (BXInstallServices::IsUTF8Support()? "Y" : "N"));
	}

	function OnPostForm()
	{
		$wizard = $this->GetWizard();

		if ($wizard->IsPrevButtonClick())
			return;

		$dbType = $wizard->GetVar("dbType");
		$arDBTypes = BXInstallServices::GetDBTypes();

		if (count($arDBTypes) > 1 && (!array_key_exists($dbType, $arDBTypes) || $arDBTypes[$dbType] === false))
			$this->SetError(InstallGetMessage("ERR_NO_DATABSEL"), "dbType");

		$licenseKey = $wizard->GetVar("license");

		if (!defined("TRIAL_VERSION") && !defined("TRIAL_RENT_VERSION") && function_exists("preg_match") && !preg_match('/[A-Z0-9]{3}-[A-Z]{2}-?[A-Z0-9]{12,30}/i', $licenseKey))
			$this->SetError(InstallGetMessage("BAD_LICENSE_KEY"), "license");

		if(defined("TRIAL_VERSION") || defined("TRIAL_RENT_VERSION"))
		{
			$lic_key_variant = $wizard->GetVar("lic_key_variant");

			if((defined("TRIAL_RENT_VERSION") || (defined("TRIAL_VERSION") && $lic_key_variant == "Y")) && $licenseKey == '')
			{
				$lic_key_user_surname = $wizard->GetVar("user_surname");
				$lic_key_user_name = $wizard->GetVar("user_name");
				$lic_key_email = $wizard->GetVar("email");

				$bError = false;
				if(trim($lic_key_user_name) == '')
				{
					$this->SetError(InstallGetMessage("ACT_KEY_BAD_NAME"), "user_name");
					$bError = true;
				}
				if(trim($lic_key_user_surname) == '')
				{
					$this->SetError(InstallGetMessage("ACT_KEY_BAD_LAST_NAME"), "user_surname");
					$bError = true;
				}
				if(trim($lic_key_email) == '' || !check_email($lic_key_email))
				{
					$this->SetError(InstallGetMessage("ACT_KEY_BAD_EMAIL"), "email");
					$bError = true;
				}

				if(!$bError)
				{
					$key = BXInstallServices::GetRegistrationKey($lic_key_user_name, $lic_key_user_surname, $lic_key_email, $dbType);

					if($key !== false)
					{
						$wizard->SetVar("license", $key);
					}
					elseif(defined("TRIAL_RENT_VERSION"))
					{
						$this->SetError(InstallGetMessage("ACT_KEY_REQUEST_ERROR"), "email");
					}
				}
			}
		}
	}

	function ShowStep()
	{
		$wizard = $this->GetWizard();

		BXInstallServices::SetSession();

		$this->content .= '
		<table border="0" class="data-table">
			<tr>
				<td colspan="2" class="header">'.InstallGetMessage("INS_LICENSE_HEAD").'</td>
			</tr>';

		if(!defined("TRIAL_VERSION") && !defined("TRIAL_RENT_VERSION"))
		{
			$this->content .= '<tr>
				<td nowrap align="right" width="40%" valign="top">
					<span style="color:red">*</span>&nbsp;'.InstallGetMessage("INS_LICENSE").'
				</td>
				<td width="60%" valign="top">'.$this->ShowInputField("text", "license", Array("size" => "30", "tabindex" => "1", "id" =>"license_id")).'
					<br>
					<small>'.InstallGetMessage("INS_LICENSE_NOTE_SOURCE").'<br></small>
				</td>
				</tr>
				<tr>
				<td nowrap align="right" width="40%" valign="top">
					'.InstallGetMessage("INSTALL_DEVSRV").'
				</td>
				<td width="60%" valign="top">'.$this->ShowCheckboxField("devsrv", "Y", Array("id" => "devsrv_inst")).'
					<br>
					<small>'.InstallGetMessage("INSTALL_DEVSRV_NOTE").'<br></small>
				</td>
				</tr>';
		}
		else
		{
			$this->content .= '
			<script>
				function changeLicKey(val)
				{
					if(val)
					{
						document.getElementById("lic_key_activation").style.display = "block";
					}
					else
					{
						document.getElementById("lic_key_activation").style.display = "none";
					}
				}
			</script>

					';
			if(!defined("TRIAL_RENT_VERSION"))
				$this->content .= '<tr><td colspan="2"><label for="lic_key_variant">'.$this->ShowCheckboxField("lic_key_variant", "Y", Array("id" => "lic_key_variant", "onclick" => "javascript:changeLicKey(this.checked)")).''.InstallGetMessage("ACT_KEY").'</label></td></tr>';


			$lic_key_variant = $wizard->GetVar("lic_key_variant", $useDefault = true);
			$this->content .= '
			</table>
			<div id="lic_key_activation">
			<table border="0" class="data-table" style="border-top:none;">
			<tr>
				<td align="right" width="40%" style="border-top:none;"><span style="color:red">*</span>&nbsp;'.InstallGetMessage("ACT_KEY_NAME").':</td>
				<td width="60%" valign="top" style="border-top:none;">'.$this->ShowInputField("text", "user_name", Array("size" => "30", "tabindex" => "4", "id" => "user_name")).'</td>
			</tr>
			<tr>
				<td align="right" width="40%" style="border-top:none;"><span style="color:red">*</span>&nbsp;'.InstallGetMessage("ACT_KEY_LAST_NAME").':</td>
				<td width="60%" valign="top" style="border-top:none;">'.$this->ShowInputField("text", "user_surname", Array("size" => "30", "tabindex" => "5", "id" => "user_surname")).'</td>
			</tr>
			<tr>
				<td align="right" width="40%" style="border-top:none;"><span style="color:red">*</span>&nbsp;Email:</td>
				<td width="60%" valign="top" style="border-top:none;">'.$this->ShowInputField("text", "email", Array("size" => "30", "tabindex" => "6", "id" => "email")).'</td>
			</tr>
			</table>
			</div>
			<script>
			changeLicKey('.(($lic_key_variant == "Y") ? 'true' : 'false').');
			</script>
			';
		}
		$this->content .= '<br /><table border="0" class="data-table">';

		$wizard->SetVar("dbType", $wizard->GetDefaultVar("dbType"));

		$this->content .= '
			<tr id="utf-row-one">
				<td colspan="2" class="header">'.InstallGetMessage("INS_UTF_PARAMS").'</td>
			</tr>
			<tr id="utf-row-two">
				<td colspan="2">
					'.$this->ShowCheckboxField("utf8", "Y", Array("id" => "utf8_inst")).'<label for="utf8_inst">&nbsp;'.InstallGetMessage("INSTALL_IN_UTF8").'</label>
				</td>
			</tr>
			</table>
			<script type="text/javascript">
				setTimeout(function() {
					if(document.getElementById("license_id"))
					{
						document.getElementById("license_id").focus();
					}
					else
					{
						if(document.getElementById("lic_key_variant"))
							document.getElementById("lic_key_variant").focus();
					}
				}, 500);
			</script>
		';
	}

}

class RequirementStep extends CWizardStep
{
	protected $memoryMin = 64;
	protected $memoryRecommend = 256;
	protected $diskSizeMin = 500;

	protected $phpMinVersion = "7.4.0";
	protected $apacheMinVersion = "2.0";
	protected $bitrixVmMinVersion = '7.5.0';

	protected $arCheckFiles = Array();

	function InitStep()
	{
		$this->SetStepID("requirements");
		$this->SetNextStep("create_database");
		$this->SetPrevStep("select_database");
		$this->SetNextCaption(InstallGetMessage("NEXT_BUTTON"));
		$this->SetPrevCaption(InstallGetMessage("PREVIOUS_BUTTON"));
		$this->SetTitle(InstallGetMessage("INS_STEP4_TITLE"));
	}

	function OnPostForm()
	{
		$wizard = $this->GetWizard();

		if ($wizard->IsPrevButtonClick())
			return null;

		$dbType = $wizard->GetVar("dbType");
		$utf8 = $wizard->GetVar("utf8");

		if ($utf8 == "Y" && !BXInstallServices::IsUTF8Support())
		{
			$this->SetError(InstallGetMessage("INST_UTF8_RECOMENDATION2"));
			return false;
		}
		if ($utf8 != "Y" && strtoupper(ini_get("default_charset")) == "UTF-8")
		{
			$this->SetError(InstallGetMessage("ERR_MBSTRING_EXISTS1"));
			return false;
		}

		$mbEncoding = ini_get("mbstring.internal_encoding");
		if($mbEncoding <> '' && strtoupper($mbEncoding) <> strtoupper(ini_get("default_charset")))
		{
			$this->SetError(InstallGetMessage("INST_UTF8_DEFAULT_ENCODING"));
			return false;
		}

		if (!BXInstallServices::CheckSession())
		{
			$this->SetError(InstallGetMessage("INST_SESSION_NOT_SUPPORT"));
			return false;
		}

		if (!$this->CheckRequirements($dbType))
		{
			return false;
		}
		return null;
	}

	function CheckRequirements($dbType)
	{
		if ($this->CheckServerVersion($serverName, $serverVersion, $serverMinVersion) === false)
		{
			$this->SetError(InstallGetMessage("SC_WEBSERVER_VER_ER"));
			return false;
		}

		if (!$this->CheckPHPVersion())
		{
			$this->SetError(InstallGetMessage("SC_PHP_VER_ER"));
			return false;
		}

		if ($this->GetPHPSetting("safe_mode")=="ON")
		{
			$this->SetError(InstallGetMessage("SC_SAFE_MODE_ER"));
			return false;
		}

		if(ini_get("date.timezone") == '')
		{
			$this->SetError(InstallGetMessage("SC_TIME_ZONE_ER"));
			return false;
		}

		if(extension_loaded('eaccelerator'))
		{
			$this->SetError(InstallGetMessage("SC_EA_ER"));
			return false;
		}

		$arDBTypes = BXInstallServices::GetDBTypes();
		if (!array_key_exists($dbType, $arDBTypes) || $arDBTypes[$dbType] === false)
		{
			$this->SetError(InstallGetMessage("SC_NO_MYS_LIB_ER"));
			return false;
		}

		if (!function_exists("preg_match"))
		{
			$this->SetError(InstallGetMessage("SC_NO_PERE_LIB_ER"));
			return false;
		}

		if (!function_exists("hash"))
		{
			$this->SetError(InstallGetMessage("SC_NO_HASH"));
			return false;
		}

		if (!function_exists("json_encode"))
		{
			$this->SetError(InstallGetMessage("SC_NO_JSON_LIB_ER"));
			return false;
		}

		if (!function_exists("openssl_encrypt"))
		{
			$this->SetError(InstallGetMessage("SC_NO_OPENSSL_LIB_ER"));
			return false;
		}

		if (!function_exists("mb_strlen"))
		{
			$this->SetError(InstallGetMessage("SC_NO_MBSTRING_LIB_ER"));
			return false;
		}

		if(intval(ini_get("mbstring.func_overload")) > 0)
		{
			$this->SetError(InstallGetMessage("SC_FUNC_OVERLOAD_ER1"));
			return false;
		}

		if (!$this->CheckFileAccess())
		{
			$files = "";
			foreach ($this->arCheckFiles as $arFile)
				if (!$arFile["ACCESS"])
					$files .= "<br />&nbsp;".$arFile["PATH"];

			$this->SetError(InstallGetMessage("INST_ERROR_ACCESS_FILES").$files);
			return false;
		}

		return true;
	}

	function GetPHPSetting($value)
	{
		return (ini_get($value) == "1" || strtoupper(ini_get($value)) == "ON" ? "ON" : "OFF");
	}

	function ShowResult($resultText, $type = "OK")
	{
		if ($resultText == '')
			return "";

		if (strtoupper($type) == "ERROR" || $type === false)
			return "<b><span style=\"color:red\">".$resultText."</span></b>";
		elseif (strtoupper($type) == "OK" || $type === true)
			return "<b><span style=\"color:green\">".$resultText."</span></b>";
		elseif (strtoupper($type) == "NOTE" || strtoupper($type) == "N")
			return "<b><span style=\"color:black\">".$resultText."</span></b>";
		return "";
	}

	function CheckServerVersion(&$serverName, &$serverVersion, &$serverMinVersion)
	{
		$serverName = "";
		$serverVersion = "";
		$serverMinVersion = "";

		if (isset($_SERVER['BITRIX_VA_VER']))
		{
			// Bitrix VM on board
			$serverName = 'Bitrix VM';
			$serverVersion = $_SERVER['BITRIX_VA_VER'];
			$serverMinVersion = $this->bitrixVmMinVersion;

			return BXInstallServices::VersionCompare($serverVersion, $serverMinVersion);
		}
		else
		{
			$serverSoftware = $_SERVER["SERVER_SOFTWARE"];
			if ($serverSoftware == '')
				$serverSoftware = $_SERVER["SERVER_SIGNATURE"];
			$serverSoftware = trim($serverSoftware);

			if (!function_exists("preg_match") || !preg_match("#^([a-zA-Z-]+).*?([\\d]+\\.[\\d]+(\\.[\\d]+)?)#i", $serverSoftware, $arMatch))
				return null;

			$serverName = $arMatch[1];
			$serverVersion = $arMatch[2];

			if (strtoupper($serverName) == "APACHE")
			{
				$serverMinVersion = $this->apacheMinVersion;
				return BXInstallServices::VersionCompare($serverVersion, $serverMinVersion);
			}

			return null;
		}
	}

	function CheckPHPVersion()
	{
		return BXInstallServices::VersionCompare(phpversion(), $this->phpMinVersion);
	}

	function CheckFileAccess()
	{
		$this->arCheckFiles = Array(
			Array("PATH" => $_SERVER["DOCUMENT_ROOT"], "DESC" => InstallGetMessage("SC_DISK_PUBLIC"), "RESULT" => "", "ACCESS" => true),
			Array("PATH" => $_SERVER["DOCUMENT_ROOT"]."/bitrix", "DESC" => InstallGetMessage("SC_DISK_BITRIX"), "RESULT" => "", "ACCESS" => true),
			Array("PATH" => $_SERVER["DOCUMENT_ROOT"]."/index.php", "DESC" => InstallGetMessage("SC_FILE"), "RESULT" => "", "ACCESS" => true),
			Array("PATH" => $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules", "DESC" => InstallGetMessage("SC_CATALOG"), "RESULT" => "", "ACCESS" => true),
		);

		$success = true;
		foreach ($this->arCheckFiles as $index => $arFile)
		{
			$readable = is_readable($arFile["PATH"]);
			$writeable = is_writeable($arFile["PATH"]);

			if ($readable && $writeable)
			{
				$this->arCheckFiles[$index]["RESULT"] = $this->ShowResult(InstallGetMessage("SC_DISK_AVAIL_READ_WRITE1"), "OK");
				continue;
			}

			$success = false;
			$this->arCheckFiles[$index]["ACCESS"] = false;

			if (!$writeable)
				$this->arCheckFiles[$index]["RESULT"] .= $this->ShowResult(InstallGetMessage("SC_CAN_NOT_WRITE"), "ERROR");

			if (!$writeable && !$readable)
				$this->arCheckFiles[$index]["RESULT"] .= " ".InstallGetMessage("SC_AND")." ";

			if (!$readable)
				$this->arCheckFiles[$index]["RESULT"] .= $this->ShowResult(InstallGetMessage("SC_CAN_NOT_READ"), "ERROR");
		}

		if ($success === false)
			return false;

		return $this->CreateTemporaryFiles();
	}

	function CreateTemporaryFiles()
	{
		$htaccessTest = $_SERVER["DOCUMENT_ROOT"]."/bitrix/httest";
		$rootTest = $_SERVER["DOCUMENT_ROOT"]."/bxtest";

		if (!file_exists($htaccessTest) && @mkdir($htaccessTest) === false)
		{
			$this->arCheckFiles[]= Array(
				"PATH" => $htaccessTest,
				"DESC" => InstallGetMessage("SC_CATALOG"),
				"RESULT" => $this->ShowResult(InstallGetMessage("SC_CAN_NOT_WRITE"), "ERROR"),
				"ACCESS" => false
			);
			return false;
		}

		if (!file_exists($rootTest) && @mkdir($rootTest) === false)
		{
			$this->arCheckFiles[]= Array(
				"PATH" => $rootTest,
				"ACCESS" => false,
				"RESULT" => $this->ShowResult(InstallGetMessage("SC_CAN_NOT_WRITE"), "ERROR"),
				"DESC" => InstallGetMessage("SC_CATALOG")
			);
			return false;
		}

		$arFiles = Array(
			Array(
				"PATH" => $_SERVER["DOCUMENT_ROOT"]."/bitrix/httest/.htaccess",
				"CONTENT" => 'ErrorDocument 404 /bitrix/httest/404.php

<IfModule mod_rewrite.c>
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^.+\.php$ /bitrix/httest/404.php
</IfModule>',
			),
			Array(
				"PATH" => $_SERVER["DOCUMENT_ROOT"]."/bitrix/httest/404.php",
				"CONTENT" => "<"."?\n".
							"$"."cgi = (stristr(php_sapi_name(), \"cgi\") !== false);\n".
							"$"."fastCGI = ("."$"."cgi && stristr("."$"."_SERVER[\"SERVER_SOFTWARE\"], \"Microsoft-IIS\") !== false);\n".
							"if ("."$"."cgi && !"."$"."fastCGI)\n".
							"	header(\"Status: 200 OK\");\n".
							"else\n".
							"	header(\"HTTP/1.0 200 OK\");\n".
							"echo \"SUCCESS\";\n".
							"?".">",
			),
			Array(
				"PATH" => $_SERVER["DOCUMENT_ROOT"]."/bxtest/test.php",
				"CONTENT" => "test",
			),
		);

		foreach ($arFiles as $arFile)
		{
			if (!$fp = @fopen($arFile["PATH"], "wb"))
			{
				$this->arCheckFiles[]= Array("PATH" => $arFile["PATH"], "ACCESS" => false, "SKIP" => true);
				return false;
			}

			if (!fwrite($fp, $arFile["CONTENT"]))
			{
				$this->arCheckFiles[]= Array("PATH" => $arFile["PATH"], "ACCESS" => false, "SKIP" => true);
				return false;
			}
		}

		return true;
	}

	function ShowStep()
	{
		$wizard = $this->GetWizard();

		$this->content .= '<h3>'.InstallGetMessage("SC_SUBTITLE_REQUIED").'</h3>'.InstallGetMessage("SC_SUBTITLE_REQUIED_DESC").'<br><br>';

		$this->content .= '
		<table border="0" class="data-table data-table-multiple-column">
			<tr>
				<td class="header">'.InstallGetMessage("SC_PARAM").'</td>
				<td class="header">'.InstallGetMessage("SC_REQUIED").'</td>
				<td class="header">'.InstallGetMessage("SC_CURRENT").'</td>
			</tr>';

		//Web server version
		$success = $this->CheckServerVersion($serverName, $serverVersion, $serverMinVersion);
		$this->content .= '
		<tr>
			<td valign="top">
					'.str_replace("#SERVER#", (($serverName <> '') ? $serverName : InstallGetMessage("SC_UNKNOWN")), InstallGetMessage("SC_SERVER_VERS")).'
			</td>
			<td valign="top">
				'.($serverMinVersion <> '' ? str_replace("#VER#", $serverMinVersion, InstallGetMessage("SC_VER_VILKA1")) : "").'
			</td>
			<td valign="top">
				'.($success !== null ? $this->ShowResult($serverVersion, $success) : $this->ShowResult(InstallGetMessage("SC_UNKNOWN1"), "ERROR")).'
			</td>
		</tr>';

		//PHP version
		$success = $this->CheckPHPVersion();
		$this->content .= '
		<tr>
			<td valign="top">'.InstallGetMessage("SC_PHP_VERS").'</td>
			<td valign="top">
					'.($this->phpMinVersion <> '' ? str_replace("#VER#", $this->phpMinVersion, InstallGetMessage("SC_VER_VILKA1")) : "").'
			</td>
			<td valign="top">'.$this->ShowResult(phpversion(), $success).'</td>
		</tr>';

		$this->content .= '
		<tr>
			<td colspan="3"><b>'.InstallGetMessage("SC_PHP_SETTINGS").'</b></td>
		</tr>';

		//Save mode
		$this->content .= '
		<tr>
			<td valign="top">safe mode</td>
			<td valign="top">'.InstallGetMessage("SC_TURN_OFF").'</td>
			<td valign="top">
					'.($this->GetPHPSetting("safe_mode")=="ON" ?
						$this->ShowResult(InstallGetMessage("SC_TURN_ON"), "ERROR") :
						$this->ShowResult(InstallGetMessage("SC_TURN_OFF"), "OK")
					).'
			</td>
		</tr>';

		//timezone
		$this->content .= '
		<tr>
			<td valign="top">date.timezone</td>
			<td valign="top">'.InstallGetMessage("SC_SETTED").'</td>
			<td valign="top">
					'.(($zone = ini_get("date.timezone")) == '' ?
						$this->ShowResult(InstallGetMessage("SC_NOT_SETTED"), "ERROR") :
						$this->ShowResult($zone, "OK")
					).'
			</td>
		</tr>';

		if ($this->GetPHPSetting('opcache.enable') == 'ON')
		{
			$this->content .= '
			<tr>
				<td valign="top">opcache.validate_timestamps</td>
				<td valign="top">1</td>
				<td valign="top">
						'.($this->GetPHPSetting('opcache.validate_timestamps') == "ON" ?
							$this->ShowResult(1, "OK") :
							$this->ShowResult(0, "ERROR")
						).'
				</td>
			</tr>
			<tr>
				<td valign="top">opcache.revalidate_freq</td>
				<td valign="top">0</td>
				<td valign="top">
						'.(ini_get('opcache.revalidate_freq') > 0 ?
							$this->ShowResult(ini_get('opcache.revalidate_freq'), "ERROR") :
							$this->ShowResult("0", "OK")
						).'
				</td>
			</tr>';
		}

		if(extension_loaded('eaccelerator'))
		{
			$this->content .= '
			<tr>
				<td valign="top">eAccelerator</td>
				<td valign="top">'.InstallGetMessage("SC_NOT_SETTED").'</td>
				<td valign="top">'.$this->ShowResult(InstallGetMessage("SC_SETTED"), "ERROR").'</td>
			</tr>';
		}

		//UTF-8
		$utf8 = ($wizard->GetVar("utf8") == "Y");

		$encoding = ini_get("default_charset");
		if ($encoding == "")
			$encoding = $this->ShowResult(InstallGetMessage("SC_NOT_SETTED"), "ERROR");
		elseif($utf8)
			$encoding = $this->ShowResult($encoding, (strtoupper($encoding) == "UTF-8"? "OK" : "ERROR"));

		$this->content .= '
			<tr>
				<td valign="top">default_charset</td>
				<td valign="top">'.($utf8? "UTF-8" : "&nbsp;").'</td>
				<td valign="top">'.$encoding.'</td>
			</tr>';

		if (intval(ini_get("mbstring.func_overload")) > 0)
		{
			$this->content .= '
				<tr>
					<td valign="top">mbstring.func_overload</td>
					<td valign="top">-</td>
					<td valign="top">'.$this->ShowResult(ini_get("mbstring.func_overload"), "ERROR").'</td>
				</tr>';
		}

		//Database support in PHP
		$dbType = $wizard->GetVar("dbType");
		$arDBTypes = BXInstallServices::GetDBTypes();
		$success = (array_key_exists($dbType, $arDBTypes) && $arDBTypes[$dbType] === true);

		$this->content .= '
		<tr>
			<td colspan="3"><b>'.InstallGetMessage("SC_REQUIED_PHP_MODS").'</b></td>
		</tr>
		<tr>
			<td valign="top"><a href="http://www.php.net/manual/en/ref.mysql.php" target="_blank">'.InstallGetMessage("SC_MOD_MYSQL").'</a></td>
			<td valign="top">'.InstallGetMessage("SC_SETTED").'</td>
			<td valign="top">
			'.(
				$success ?
					$this->ShowResult(InstallGetMessage("SC_SETTED"), "OK") :
					$this->ShowResult(InstallGetMessage("SC_NOT_SETTED"), "ERROR")
				).'</td>
		</tr>';

		$this->content .= '
		<tr>
			<td valign="top">
				<a href="http://www.php.net/manual/en/ref.pcre.php" target="_blank">'.InstallGetMessage("SC_MOD_PERL_REG").'</a>
			</td>
			<td valign="top">'.InstallGetMessage("SC_SETTED").'</td>
			<td valign="top">
					'.(function_exists("preg_match") ? $this->ShowResult(InstallGetMessage("SC_SETTED"), "OK") : $this->ShowResult(InstallGetMessage("SC_NOT_SETTED"), "ERROR")).'
			</td>
		</tr>';

		$this->content .= '
		<tr>
			<td valign="top">
				<a href="http://php.net/manual/book.hash.php" target="_blank">'.InstallGetMessage("SC_MOD_HASH").'</a>
			</td>
			<td valign="top">'.InstallGetMessage("SC_SETTED").'</td>
			<td valign="top">
					'.(function_exists("hash") ? $this->ShowResult(InstallGetMessage("SC_SETTED"), "OK") : $this->ShowResult(InstallGetMessage("SC_NOT_SETTED"), "ERROR")).'
			</td>
		</tr>';

		$this->content .= '
		<tr>
			<td valign="top">
				<a href="http://php.net/manual/en/book.json.php" target="_blank">'.InstallGetMessage("SC_MOD_JSON").'</a>
			</td>
			<td valign="top">'.InstallGetMessage("SC_SETTED").'</td>
			<td valign="top">
					'.(function_exists("json_encode") ? $this->ShowResult(InstallGetMessage("SC_SETTED"), "OK") : $this->ShowResult(InstallGetMessage("SC_NOT_SETTED"), "ERROR")).'
			</td>
		</tr>';
		$this->content .= '
		<tr>
			<td valign="top">
				<a href="http://php.net/manual/en/book.openssl.php" target="_blank">OpenSSL</a>
			</td>
			<td valign="top">'.InstallGetMessage("SC_SETTED").'</td>
			<td valign="top">
					'.(function_exists("openssl_encrypt") ? $this->ShowResult(InstallGetMessage("SC_SETTED"), "OK") : $this->ShowResult(InstallGetMessage("SC_NOT_SETTED"), "ERROR")).'
			</td>
		</tr>';
		$this->content .= '
		<tr>
			<td valign="top">
				<a href="http://php.net/manual/en/book.mbstring.php" target="_blank">Multibyte String</a>
			</td>
			<td valign="top">'.InstallGetMessage("SC_SETTED").'</td>
			<td valign="top">
					'.(function_exists("mb_strlen") ? $this->ShowResult(InstallGetMessage("SC_SETTED"), "OK") : $this->ShowResult(InstallGetMessage("SC_NOT_SETTED"), "ERROR")).'
			</td>
		</tr>';

		if (!BXInstallServices::CheckSession())
		{
			$this->content .= '
				<tr>
					<td valign="top">
							<a href="http://www.php.net/manual/en/book.session.php" target="_blank">'.InstallGetMessage("INST_SESSION_SUPPORT").'</a>
					</td>
					<td valign="top">'.InstallGetMessage("INST_YES").'</td>
					<td valign="top">'.$this->ShowResult(InstallGetMessage("INST_NO").". ".InstallGetMessage("INST_SESSION_NOT_SUPPORT"),"ERROR").'</td>
				</tr>';
		}

		$this->content .= '</table>';

		//File and folder permissons
		$this->content .= '<h3>'.InstallGetMessage("SC_SUBTITLE_DISK").'</h3>'.InstallGetMessage("SC_SUBTITLE_DISK_DESC").'<br><br>';
		$this->content .= '
		<table border="0" class="data-table data-table-multiple-column">
		<tr>
			<td class="header">'.InstallGetMessage("SC_PARAM").'</td>
			<td class="header">'.InstallGetMessage("SC_VALUE").'</td>
		</tr>';

		$this->CheckFileAccess();
		foreach ($this->arCheckFiles as $arFile)
		{
			if (isset($arFile["SKIP"]))
				continue;

			$this->content .= '
			<tr>
				<td valign="top">'.$arFile["DESC"].' <i>'.$arFile["PATH"].'</i></td>
				<td valign="top">'.$arFile["RESULT"].'</td>
			</tr>';
		}

		$this->content .= '</table>';

		//Recommend
		$this->content .= '<h3>'.InstallGetMessage("SC_SUBTITLE_RECOMMEND").'</h3>'.InstallGetMessage("SC_SUBTITLE_RECOMMEND_DESC").'<br><br>';
		$this->content .= '
		<table border="0" class="data-table data-table-multiple-column">
			<tr>
				<td class="header">'.InstallGetMessage("SC_PARAM").'</td>
				<td class="header">'.InstallGetMessage("SC_RECOMMEND").'</td>
				<td class="header">'.InstallGetMessage("SC_CURRENT").'</td>
			</tr>';

		if(file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet"))
		{
			$min_version = '4.2';
			if (!($vm = getenv('BITRIX_VA_VER')))
				$txt = $this->ShowResult(InstallGetMessage('SC_VMBITRIX_UNKNOWN'),false);
			elseif (version_compare($vm, $min_version, '<'))
				$txt = $this->ShowResult(str_replace('#VER#', htmlspecialcharsbx($vm), InstallGetMessage('SC_VMBITRIX_OLD')), false);
			else
				$txt = $this->ShowResult(InstallGetMessage('SC_VMBITRIX_OK'), true);

			$this->content .= '
			<tr>
				<td valign="top">'.InstallGetMessage("SC_VMBITRIX").'</td>
				<td valign="top">'.str_replace('#VER#', $min_version, InstallGetMessage('SC_VMBITRIX_RECOMMENDED')).'</td>
				<td valign="top">'.$txt.'</td>
			</tr>';
		}

		if(strpos(strtolower($_SERVER['SERVER_SOFTWARE']), 'apache') !== false)
		{
			$this->content .= '
			<tr>
				<td valign="top">'.InstallGetMessage("SC_HTACCESS").'</td>
				<td valign="top">'.InstallGetMessage("SC_TURN_ON2").'</td>
				<td valign="top"><span id="httest">'.$this->ShowResult(InstallGetMessage("SC_TESTING"), "N").'</span>
				<script type="text/javascript">
					req = false;
					if(window.XMLHttpRequest)
					{
						try{req = new XMLHttpRequest();}
						catch(e) {req = false;}
					} else if(window.ActiveXObject)
					{
						try {req = new ActiveXObject("Msxml2.XMLHTTP");}
						catch(e)
						{
							try {req = new ActiveXObject("Microsoft.XMLHTTP");}
							catch(e) {req = false;}
						}
					}

					if (req)
					{
						req.onreadystatechange = processReqChange;
						req.open("GET", "/bitrix/httest/test_404.php?random=" + Math.random(), true);
						req.send("");
					}
					else
						document.getElementById("httest").innerHTML = \''.$this->ShowResult(InstallGetMessage("SC_HTERR"), "ERROR").'\';

					function processReqChange() {
						if (req.readyState == 4) {
							if (req.responseText == "SUCCESS") {
								res = \''.$this->ShowResult(InstallGetMessage("SC_TURN_ON2"), "OK").'\';
							} else {
								res = \''.$this->ShowResult(InstallGetMessage("SC_TURN_OFF2"), "ERROR").'\';
							}
							document.getElementById("httest").innerHTML = res;
						}
					}
				</script>
				</td>
			</tr>';
		}

		$freeSpace = @disk_free_space($_SERVER["DOCUMENT_ROOT"]);
		$freeSpace = $freeSpace * 1.0 / 1000000.0;
		$this->content .= '
		<tr>
			<td valign="top">'.InstallGetMessage("SC_AVAIL_DISK_SPACE").'</td>
			<td valign="top">
				'.(intval($this->diskSizeMin) > 0 ? str_replace("#SIZE#", $this->diskSizeMin, InstallGetMessage("SC_AVAIL_DISK_SPACE_SIZE")) : "").'&nbsp;
			</td>
			<td valign="top">
					'.($freeSpace > $this->diskSizeMin ? $this->ShowResult(round($freeSpace, 1)." Mb", "OK") : $this->ShowResult(round($freeSpace, 1)." Mb", "ERROR")).'
			</td>
		</tr>';

		$memoryLimit = WelcomeStep::unformat(ini_get('memory_limit'));
		if (!$memoryLimit || $memoryLimit == '')
			$memoryLimit = WelcomeStep::unformat(get_cfg_var('memory_limit'));

		if($memoryLimit > 0 && $memoryLimit < $this->memoryMin)
		{
			@ini_set("memory_limit", "64M");
			$memoryLimit = WelcomeStep::unformat(ini_get('memory_limit'));
		}

		$recommendMemory = "";
		if (intval($this->memoryMin)>0)
			$recommendMemory .= str_replace("#SIZE#", $this->memoryMin, InstallGetMessage("SC_AVAIL_MEMORY_MIN"));
		if (intval($this->memoryMin)>0 && intval($this->memoryRecommend)>0)
			$recommendMemory .= ", ";
		if (intval($this->memoryRecommend)>0)
			$recommendMemory .= str_replace("#SIZE#", $this->memoryRecommend, InstallGetMessage("SC_AVAIL_MEMORY_REC"));

		$this->content .= '
		<tr>
			<td colspan="3"><b>'.InstallGetMessage("SC_RECOM_PHP_SETTINGS").'</b></td>
		</tr>
		<tr>
			<td valign="top">'.InstallGetMessage("SC_AVAIL_MEMORY").'</td>
			<td valign="top">'.$recommendMemory.'</td>
			<td valign="top">
					'.($memoryLimit > 0 && $memoryLimit < $this->memoryMin*1048576 ? $this->ShowResult(ini_get('memory_limit'), "ERROR") : $this->ShowResult(ini_get('memory_limit'), "OK")).'
			</td>
		</tr>';

		$this->content .= '
		<tr>
			<td valign="top">'.InstallGetMessage("SC_ALLOW_UPLOAD").' (file_uploads)</td>
			<td valign="top">'.InstallGetMessage("SC_TURN_ON1").'</td>
			<td valign="top">
					'.($this->GetPHPSetting("file_uploads")=="ON" ? $this->ShowResult(InstallGetMessage("SC_TURN_ON1"), "OK") : $this->ShowResult(InstallGetMessage("SC_TURN_OFF1"), "ERROR")).'
			</td>
		</tr>
		<tr>
			<td valign="top">'.InstallGetMessage("SC_SHOW_ERRORS").' (display_errors)</td>
			<td valign="top">'.InstallGetMessage("SC_TURN_OFF1").'</td>
			<td valign="top">
					'.($this->GetPHPSetting("display_errors")=="ON" ? $this->ShowResult(InstallGetMessage("SC_TURN_ON1"), "ERROR") : $this->ShowResult(InstallGetMessage("SC_TURN_OFF1"), "OK")).'
			</td>
		</tr>
		<tr>
			<td valign="top">'.InstallGetMessage("SC_magic_quotes_sybase").' (magic_quotes_sybase)</td>
			<td valign="top">'.InstallGetMessage("SC_TURN_OFF1").'</td>
			<td valign="top">
					'.($this->GetPHPSetting("magic_quotes_sybase")=="ON" ? $this->ShowResult(InstallGetMessage("SC_TURN_ON1"), "ERROR") : $this->ShowResult(InstallGetMessage("SC_TURN_OFF1"), "OK")).'
			</td>
		</tr>';

		//Recommended extensions
		$this->content .= '
		<tr>
			<td colspan="3"><b>'.InstallGetMessage("SC_RECOM_PHP_MODULES").'</b></td>
		</tr>
		<tr>
			<td valign="top">
				<a href="http://www.php.net/manual/en/ref.zlib.php" target="_blank">Zlib Compression</a>
			</td>
			<td valign="top">'.InstallGetMessage("SC_SETTED").'</td>
			<td valign="top">
					'.(extension_loaded("zlib") && function_exists("gzcompress") ? $this->ShowResult(InstallGetMessage("SC_SETTED"), "OK") : $this->ShowResult(InstallGetMessage("SC_NOT_SETTED"), "ERROR")).'
			</td>
		</tr>
		<tr>
			<td valign="top">
				<a href="http://www.php.net/manual/en/ref.image.php" target="_blank">'.InstallGetMessage("SC_MOD_GD").'</a>
			</td>
			<td valign="top">'.InstallGetMessage("SC_SETTED").'</td>
			<td valign="top">
					'.(function_exists("imagecreate") ? $this->ShowResult(InstallGetMessage("SC_SETTED"), "OK") : $this->ShowResult(InstallGetMessage("SC_NOT_SETTED"), "ERROR")).'
			</td>
		</tr>
		<tr>
			<td valign="top"><a href="http://www.freetype.org" target="_blank">Free Type Library</a></td>
			<td valign="top">'.InstallGetMessage("SC_SETTED").'</td>
			<td valign="top">
					'.(function_exists("imagettftext") ? $this->ShowResult(InstallGetMessage("SC_SETTED"), "OK") : $this->ShowResult(InstallGetMessage("SC_NOT_SETTED"), "ERROR")).'
			</td>
		</tr>';


		$this->content .='</table>';

		$this->content .= '<br /><br /><table class="data-table"><tr><td width="0%">'.InstallGetMessage("SC_NOTES1").'</td></tr></table>';
	}
}


class CreateDBStep extends CWizardStep
{
	var $dbType;
	var $dbUser;
	var $dbPassword;
	var $dbHost;
	var $dbName;

	var $createDatabase;
	var $createUser;

	var $createCharset = false;
	var $createDBType;

	var $rootUser;
	var $rootPassword;

	var $filePermission;
	var $folderPermission;

	var $utf8;

	var $needCodePage = false;
	var $DB = null;
	var $sqlMode = false;

	function InitStep()
	{
		$this->SetStepID("create_database");
		$this->SetNextStep("create_modules");
		$this->SetPrevStep("requirements");
		$this->SetNextCaption(InstallGetMessage("NEXT_BUTTON"));
		$this->SetPrevCaption(InstallGetMessage("PREVIOUS_BUTTON"));
		$this->SetTitle(InstallGetMessage("INS_STEP5_TITLE"));

		$wizard = $this->GetWizard();

		$wizard->SetDefaultVars(Array(
			"folder_access_perms" => "0755",
			"file_access_perms" => "0644",
			"create_user" => "N",
			"create_database" => "N",
		));

		$dbType = $wizard->GetVar("dbType");

		$wizard->SetDefaultVar("database", "sitemanager");

		if ($dbType == "mysql")
			$wizard->SetDefaultVar("host", "localhost");
	}

	function OnPostForm()
	{
		$wizard = $this->GetWizard();

		if ($wizard->IsPrevButtonClick())
			return;

		$this->dbType = $wizard->GetVar("dbType");
		$this->dbUser = $wizard->GetVar("user");
		$this->dbPassword = $wizard->GetVar("password");
		$this->dbHost = $wizard->GetVar("host");
		$this->dbName = $wizard->GetVar("database");

		$this->createDatabase = $wizard->GetVar("create_database");
		$this->createDatabase = ($this->createDatabase && $this->createDatabase == "Y");

		$this->createUser = $wizard->GetVar("create_user");
		$this->createUser = ($this->createUser && $this->createUser == "Y");

		$this->createDBType = $wizard->GetVar("create_database_type");

		$this->rootUser = $wizard->GetVar("root_user");
		$this->rootPassword = $wizard->GetVar("root_password");

		if(preg_match("/(0[0-7]{3})/", $wizard->GetVar("file_access_perms"), $match))
			$this->filePermission = $match[1];
		else
			$this->filePermission = $wizard->GetDefaultVar("file_access_perms");

		if(preg_match("/(0[0-7]{3})/", $wizard->GetVar("folder_access_perms"), $match))
			$this->folderPermission = $match[1];
		else
			$this->folderPermission = $wizard->GetDefaultVar("folder_access_perms");

		//UTF-8
		$this->utf8 = $wizard->GetVar("utf8");
		$this->utf8 = ($this->utf8 && $this->utf8 == "Y" && BXInstallServices::IsUTF8Support());

		// /bitrix/admin permissions
		BXInstallServices::CheckDirPath($_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/", octdec($this->folderPermission));
		if(!is_readable($_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/") || !is_writeable($_SERVER["DOCUMENT_ROOT"]."/bitrix/admin/"))
		{
			$this->SetError("Access denied: /bitrix/admin/");
			return;
		}

		// define.php
		$definePath = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/admin/define.php";
		if(file_exists($definePath))
		{
			if(!is_readable($definePath) || !is_writeable($definePath))
			{
				$this->SetError("Access denied: /bitrix/modules/main/admin/define.php");
				return;
			}
		}
		else
		{
			$sc = false;
			if($fp = @fopen($definePath, "wb"))
			{
				if(fwrite($fp, "test"))
					$sc = true;

				@fclose($fp);
				@unlink($definePath);
			}

			if(!$sc)
			{
				$this->SetError("Access denied: /bitrix/modules/main/admin/define.php");
				return;
			}
		}

		//Bad database type
		$arDBTypes = BXInstallServices::GetDBTypes();
		if(!array_key_exists($wizard->GetVar("dbType"), $arDBTypes) || $arDBTypes[$wizard->GetVar("dbType")] === false)
		{
			$this->SetError(InstallGetMessage("ERR_INTERNAL_NODEF"));
			return;
		}

		//Empty database user
		if ($this->dbUser == '')
		{
			$this->SetError(InstallGetMessage("ERR_NO_USER"), "user");
			return;
		}

		if(extension_loaded('mysqli'))
		{
			$dbClassName = "\\Bitrix\\Main\\DB\\MysqliConnection";
			define("BX_USE_MYSQLI", true);
		}
		else
		{
			$dbClassName = "\\Bitrix\\Main\\DB\\MysqlConnection";
		}

		$application = \Bitrix\Main\HttpApplication::getInstance();
		$conPool = $application->getConnectionPool();
		$conPool->setConnectionParameters(
			\Bitrix\Main\Data\ConnectionPool::DEFAULT_CONNECTION_NAME,
			array(
				'className' => $dbClassName,
				'host' => $this->dbHost,
				'database' => $this->dbName,
				'login' => $this->dbUser,
				'password' => $this->dbPassword,
				'options' => 2
			)
		);

		$conPool->useMasterOnly(true);

		if (!$this->CreateMySQL())
			return;

		if (!$this->CreateAfterConnect())
			return;

		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/".$this->dbType."/database.php");
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/".$this->dbType."/main.php");
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/tools.php");
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/time.php");
		IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/main.php");

		global $DB;
		$DB = new CDatabase;
		$this->DB =& $DB;

		$DB->DebugToFile = false;

		if (!$DB->Connect($this->dbHost, $this->dbName, $this->dbUser, $this->dbPassword))
		{
			$this->SetError(InstallGetMessage("COULD_NOT_CONNECT")." ".$DB->db_Error);
			return;
		}

		$DB->debug = true;

		if ($this->IsBitrixInstalled())
			return;

		if (!$this->CheckDBOperation())
			return;

		if (!$this->createSettings())
			return;

		if (!$this->CreateDBConn())
			return;

		$this->CreateLicenseFile();

		//Delete cache files if exists
		BXInstallServices::DeleteDirRec($_SERVER["DOCUMENT_ROOT"]."/bitrix/managed_cache");
		BXInstallServices::DeleteDirRec($_SERVER["DOCUMENT_ROOT"]."/bitrix/stack_cache");
	}


	function IsBitrixInstalled()
	{
		$DB =& $this->DB;

		$res = $DB->Query("SELECT COUNT(ID) FROM b_user", true);
		if ($res && $res->Fetch())
		{
			$this->SetError(str_replace("#DB#", $this->dbName, InstallGetMessage("ERR_ALREADY_INST1")));
			return true;
		}

		return false;
	}

	function CreateMySQL()
	{
		if(preg_match("/[\\x0\\xFF\\/\\\\\\.]/", $this->dbName) || preg_match("/\\s$/", $this->dbName) || strlen($this->dbName) > 64)
		{
			/*
			There are some restrictions on the characters that may appear in identifiers:
			No identifier can contain ASCII 0 (0x00) or a byte with a value of 255.
			Database, table, and column names should not end with space characters.
			Database and table names cannot contain "/", "\", ".", or characters that are not allowed in filenames.
			*/
			$this->SetError(InstallGetMessage("ERR_DATABASE_NAME"));
			return false;
		}

		$config = array(
			'host' => $this->dbHost,
		);
		if ($this->createDatabase || $this->createUser)
		{
			$config['login'] = $this->rootUser;
			$config['password'] = $this->rootPassword;
		}
		else
		{
			$config['login'] = $this->dbUser;
			$config['password'] = $this->dbPassword;
		}

		if(extension_loaded('mysqli'))
		{
			$conn = new \Bitrix\Main\DB\MysqliConnection($config);
		}
		else
		{
			$conn = new \Bitrix\Main\DB\MysqlConnection($config);
		}

		try
		{
			$conn->connect();
		}
		catch(\Bitrix\Main\DB\ConnectionException $e)
		{
			$this->SetError(InstallGetMessage("ERR_CONNECT2MYSQL")." ".$e->getDatabaseMessage());
			return false;
		}

		//Check MySQL version
		$dbResult = $conn->query("select VERSION() as ver");
		if ($arVersion = $dbResult->fetch())
		{
			$mysqlVersion = trim($arVersion["ver"]);
			if (!BXInstallServices::VersionCompare($mysqlVersion, "5.6.0"))
			{
				$this->SetError(InstallGetMessage("SC_DB_VERS_MYSQL_ER"));
				return false;
			}

			$this->needCodePage = true;

			if (!$this->needCodePage && $this->utf8)
			{
				$this->SetError(InstallGetMessage("INS_CREATE_DB_CHAR_NOTE"));
				return false;
			}
		}

		//SQL mode
		$dbResult = $conn->query("SELECT @@sql_mode");
		if ($arResult = $dbResult->fetch())
		{
			$sqlMode = trim($arResult["@@sql_mode"]);
			if ($sqlMode <> "")
			{
				$this->sqlMode = "";
			}
		}

		if ($this->createDatabase)
		{
			if ($conn->selectDatabase($this->dbName))
			{
				$this->SetError(str_replace("#DB#", $this->dbName, InstallGetMessage("ERR_EXISTS_DB1")));
				return false;
			}

			$conn->queryExecute("CREATE DATABASE ".$conn->getSqlHelper()->quote($this->dbName));
			if (!$conn->selectDatabase($this->dbName))
			{
				$this->SetError(str_replace("#DB#", $this->dbName, InstallGetMessage("ERR_CREATE_DB1")));
				return false;
			}
		}
		else
		{
			if (!$conn->selectDatabase($this->dbName))
			{
				$this->SetError(str_replace("#DB#", $this->dbName, InstallGetMessage("ERR_CONNECT_DB1")));
				return false;
			}

			if (defined("DEBUG_MODE") || (isset($_COOKIE["clear_db"]) && $_COOKIE["clear_db"] == "Y") )
			{
				$result = $conn->query("SHOW TABLES LIKE 'b_%'");
				while ($arTable = $result->fetch())
				{
					$conn->queryExecute("DROP TABLE ".reset($arTable));
				}
			}
		}

		if ($this->dbUser != $this->rootUser)
		{
			$host = $this->dbHost;
			if ($position = strpos($host, ":"))
				$host = substr($host, 0, $position);

			if ($this->createUser)
			{
				$query = "GRANT ALL ON `".addslashes($this->dbName)."`.* TO '".addslashes($this->dbUser)."'@'".$host."' IDENTIFIED BY '".addslashes($this->dbPassword)."'";
				try
				{
					$conn->queryExecute($query);
				}
				catch(\Bitrix\Main\DB\SqlQueryException $e)
				{
					$this->SetError(InstallGetMessage("ERR_CREATE_USER")." ".$e->getDatabaseMessage());
					return false;
				}

			}
			elseif ($this->createDatabase)
			{
				$query = "GRANT ALL ON `".addslashes($this->dbName)."`.* TO '".addslashes($this->dbUser)."'@'".$host."' ";
				try
				{
					$conn->queryExecute($query);
				}
				catch(\Bitrix\Main\DB\SqlQueryException $e)
				{
					$this->SetError(InstallGetMessage("ERR_GRANT_USER")." ".$e->getDatabaseMessage());
					return false;
				}
			}
		}

		if ($this->needCodePage)
		{
			if ($this->utf8)
				$codePage = "utf8";
			elseif (LANGUAGE_ID == "ru" || LANGUAGE_ID == "ua")
				$codePage = "cp1251";
			elseif ($this->createCharset != '')
				$codePage = $this->createCharset;
			else
				$codePage = 'latin1';

			if ($codePage)
			{
				try
				{
					if ($codePage == "utf8")
						$conn->queryExecute("ALTER DATABASE ".$conn->getSqlHelper()->quote($this->dbName)." CHARACTER SET UTF8 COLLATE utf8_unicode_ci");
					else
						$conn->queryExecute("ALTER DATABASE ".$conn->getSqlHelper()->quote($this->dbName)." CHARACTER SET ".$codePage);
				}
				catch(\Bitrix\Main\DB\SqlQueryException $e)
				{
					$this->SetError(InstallGetMessage("ERR_ALTER_DB"));
					return false;
				}
				$conn->queryExecute("SET NAMES '".$codePage."'");
			}
		}
		return true;
	}

	function createSettings()
	{
		$filePath = $_SERVER["DOCUMENT_ROOT"]."/bitrix/.settings.php";

		if (!BXInstallServices::CheckDirPath($filePath, octdec($this->folderPermission)))
		{
			$this->SetError(str_replace("#ROOT#", "/bitrix/", InstallGetMessage("ERR_C_SAVE_DBCONN")));
			return false;
		}

		$ar = array(
			"utf_mode" => array("value" => ($this->utf8 ? true : false), "readonly" => true),
			"cache_flags" => array(
				"value" => array(
					"config_options" => 3600,
					"site_domain" => 3600
				),
				"readonly" => false
			),
		);

		$ar["cookies"] = array("value" => array("secure" => false, "http_only" => true), "readonly" => false);

		$ar["exception_handling"] = array(
			"value" => array(
				"debug" => false,
				"handled_errors_types" => E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR,
				"exception_errors_types" => E_ERROR | E_PARSE | E_CORE_ERROR | E_COMPILE_ERROR | E_USER_ERROR | E_RECOVERABLE_ERROR,
				"ignore_silence" => false,
				"assertion_throws_exception" => true,
				"assertion_error_type" => E_USER_ERROR,
				"log" => null,
			),
			"readonly" => false
		);

		if(extension_loaded('mysqli'))
		{
			$dbClassName = "\\Bitrix\\Main\\DB\\MysqliConnection";
		}
		else
		{
			$dbClassName = "\\Bitrix\\Main\\DB\\MysqlConnection";
		}

		$ar['connections']['value']['default'] = array(
			'className' => $dbClassName,
			'host' => $this->dbHost,
			'database' => $this->dbName,
			'login' => $this->dbUser,
			'password' => $this->dbPassword,
			'options' => 2
		);

		$ar['connections']['readonly'] = true;

		$ar["crypto"] = array("value" => array("crypto_key" => md5("some".uniqid("", true))), "readonly" => true);

		if (!$fp = @fopen($filePath, "wb"))
		{
			$this->SetError(str_replace("#ROOT#", $_SERVER["DOCUMENT_ROOT"], InstallGetMessage("ERR_C_SAVE_DBCONN")));
			return false;
		}

		if (!fwrite($fp, "<"."?php\n\rreturn ".var_export($ar, true).";\n"))
		{
			$this->SetError(str_replace("#ROOT#", $_SERVER["DOCUMENT_ROOT"], InstallGetMessage("ERR_C_SAVE_DBCONN")));
			return false;
		}

		@fclose($fp);
		if ($this->filePermission > 0)
			@chmod($filePath, octdec($this->filePermission));

		return true;
	}

	function CreateDBConn()
	{
		$filePath = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/dbconn.php";

		if (!BXInstallServices::CheckDirPath($filePath, octdec($this->folderPermission)))
		{
			$this->SetError(str_replace("#ROOT#", BX_PERSONAL_ROOT."/", InstallGetMessage("ERR_C_SAVE_DBCONN")));
			return false;
		}

		// Various params
		$fileContent = "<"."?php\n".
			(extension_loaded('mysqli')? "define(\"BX_USE_MYSQLI\", true);\n": '').
			"$"."DBDebug = false;\n".
			"$"."DBDebugToFile = false;\n".
			($this->createDBType=='innodb'?'define("MYSQL_TABLE_TYPE", "INNODB");'."\n":'').
			"\n".
			"define(\"CACHED_b_file\", 3600);\n".
			"define(\"CACHED_b_file_bucket_size\", 10);\n".
			"define(\"CACHED_b_lang\", 3600);\n".
			"define(\"CACHED_b_option\", 3600);\n".
			"define(\"CACHED_b_lang_domain\", 3600);\n".
			"define(\"CACHED_b_site_template\", 3600);\n".
			"define(\"CACHED_b_event\", 3600);\n".
			"define(\"CACHED_b_agent\", 3660);\n".
			"define(\"CACHED_menu\", 3600);\n".
			"\n";

		$umask = array();
		if ($this->filePermission > 0)
		{
			$fileContent .= "define(\"BX_FILE_PERMISSIONS\", ".$this->filePermission.");\n";
			$umask[] = "BX_FILE_PERMISSIONS";
		}

		if ($this->folderPermission > 0)
		{
			$fileContent .= "define(\"BX_DIR_PERMISSIONS\", ".$this->folderPermission.");\n";
			$umask[] = "BX_DIR_PERMISSIONS";
		}

		if($umask)
		{
			$fileContent .= "@umask(~(".implode(" | ", $umask).") & 0777);\n";
		}

		if(file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet"))
		{
			$fileContent .= "\n@ini_set(\"memory_limit\", \"1024M\");\n";
		}
		else
		{
			$memoryLimit = WelcomeStep::unformat(ini_get('memory_limit'));
			if (!$memoryLimit || $memoryLimit == '')
				$memoryLimit = WelcomeStep::unformat(get_cfg_var('memory_limit'));

			if($memoryLimit > 0 && $memoryLimit < 256*1048576)
			{
				@ini_set("memory_limit", "512M");
				$memoryLimit = WelcomeStep::unformat(ini_get('memory_limit'));
				if($memoryLimit >= 512*1048576)
					$fileContent .= "\n@ini_set(\"memory_limit\", \"512M\");\n";
			}
		}

		$fileContent .= "\ndefine(\"BX_DISABLE_INDEX_PAGE\", true);\n";

		if($this->utf8)
		{
			$fileContent .= "\n".
				"define(\"BX_UTF\", true);\n".
				"mb_internal_encoding(\"UTF-8\");\n";
		}
		elseif(LANGUAGE_ID == "ru" || LANGUAGE_ID == "ua")
		{
			$fileContent .= "\n".
				"setlocale(LC_ALL, 'ru_RU.CP1251');\n".
				"setlocale(LC_NUMERIC, 'C');\n".
				"mb_internal_encoding(\"Windows-1251\");\n";
		}

		if (!$fp = @fopen($filePath, "wb"))
		{
			$this->SetError(str_replace("#ROOT#", $_SERVER["DOCUMENT_ROOT"], InstallGetMessage("ERR_C_SAVE_DBCONN")));
			return false;
		}

		if (!fwrite($fp, $fileContent))
		{
			$this->SetError(str_replace("#ROOT#", $_SERVER["DOCUMENT_ROOT"], InstallGetMessage("ERR_C_SAVE_DBCONN")));
			return false;
		}

		@fclose($fp);
		if ($this->filePermission > 0)
			@chmod($filePath, octdec($this->filePermission));

		return true;
	}

	function CreateAfterConnect()
	{
		$codePage = "";
		if ($this->needCodePage)
		{
			if ($this->utf8)
				$codePage = "utf8";
			elseif (LANGUAGE_ID == "ru" || LANGUAGE_ID == "ua")
				$codePage = "cp1251";
			else
				$codePage = $this->createCharset;
		}

		$after_connNew = "<"."?php\n".
			($codePage <> '' ? "$"."this->queryExecute(\"SET NAMES '".$codePage."'\");\n" : "").
			($this->sqlMode !== false ? "$"."this->queryExecute(\"SET sql_mode='".$this->sqlMode."'\");\n" : "").
			($this->utf8 ? "$"."this->queryExecute('SET collation_connection = \"utf8_unicode_ci\"');\n" : "")
		;

		$filePathNew = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/after_connect_d7.php";

		if (!BXInstallServices::CheckDirPath($filePathNew, octdec($this->folderPermission)))
		{
			$this->SetError(str_replace("#ROOT#", BX_PERSONAL_ROOT."/", InstallGetMessage("ERR_C_SAVE_DBCONN")));
			return false;
		}

		if (!$fp = @fopen($filePathNew, "wb"))
		{
			$this->SetError(str_replace("#ROOT#", $_SERVER["DOCUMENT_ROOT"], InstallGetMessage("ERR_C_SAVE_DBCONN")));
			return false;
		}

		if (!fwrite($fp, $after_connNew))
		{
			$this->SetError(str_replace("#ROOT#", $_SERVER["DOCUMENT_ROOT"], InstallGetMessage("ERR_C_SAVE_DBCONN")));
			return false;
		}

		@fclose($fp);
		if ($this->filePermission > 0)
			@chmod($filePathNew, octdec($this->filePermission));

		if (strtoupper(substr(PHP_OS, 0, 3)) === "WIN")
		{
			$fhtaccess = $_SERVER['DOCUMENT_ROOT'].'/.htaccess';
			$f = fopen($fhtaccess, "rb");
			$fcontent = fread($f, filesize($fhtaccess));
			fclose($f);

			$fcontent = preg_replace('/RewriteEngine On/is', "RewriteEngine On\r\n\r\n".
				"RewriteCond %{REQUEST_FILENAME} -f [OR]\r\n".
				"RewriteCond %{REQUEST_FILENAME} -l [OR]\r\n".
				"RewriteCond %{REQUEST_FILENAME} -d\r\n".
				"RewriteCond %{REQUEST_FILENAME} [\\xC2-\\xDF][\\x80-\\xBF] [OR]\r\n".
				"RewriteCond %{REQUEST_FILENAME} \\xE0[\\xA0-\\xBF][\\x80-\\xBF] [OR]\r\n".
				"RewriteCond %{REQUEST_FILENAME} [\\xE1-\\xEC\\xEE\\xEF][\\x80-\\xBF]{2} [OR]\r\n".
				"RewriteCond %{REQUEST_FILENAME} \\xED[\\x80-\\x9F][\\x80-\\xBF] [OR]\r\n".
				"RewriteCond %{REQUEST_FILENAME} \\xF0[\\x90-\\xBF][\\x80-\\xBF]{2} [OR]\r\n".
				"RewriteCond %{REQUEST_FILENAME} [\\xF1-\\xF3][\\x80-\\xBF]{3} [OR]\r\n".
				"RewriteCond %{REQUEST_FILENAME} \\xF4[\\x80-\\x8F][\\x80-\\xBF]{2}\r\n".
				"RewriteCond %{REQUEST_FILENAME} !/bitrix/virtual_file_system.php$\r\n".
				"RewriteRule ^(.*)$ /bitrix/virtual_file_system.php [L]", $fcontent);

			$f = fopen($fhtaccess, "wb+");
			fwrite($f, $fcontent);
			fclose($f);
		}


		//Create .htaccess
		$filePath = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/.htaccess";
		if (file_exists($filePath))
			return true;

		if (!$fp = @fopen($filePath, "wb"))
		{
			$this->SetError(str_replace("#ROOT#", $_SERVER["DOCUMENT_ROOT"], InstallGetMessage("ERR_C_SAVE_DBCONN")));
			return false;
		}

		if (!fwrite($fp, "Deny From All"))
		{
			$this->SetError(str_replace("#ROOT#", $_SERVER["DOCUMENT_ROOT"], InstallGetMessage("ERR_C_SAVE_DBCONN")));
			return false;
		}

		@fclose($fp);
		if ($this->filePermission > 0)
			@chmod($filePath, octdec($this->filePermission));

		return true;
	}

	function CreateLicenseFile()
	{
		$wizard = $this->GetWizard();
		$licenseKey = $wizard->GetVar("license");

		if(!BXInstallServices::CreateLicenseFile($licenseKey))
			return false;

		$filePath = $_SERVER["DOCUMENT_ROOT"]."/bitrix/license_key.php";
		if ($this->filePermission > 0)
			@chmod($filePath, octdec($this->filePermission));

		return true;
	}

	function CheckDBOperation()
	{
		if (!is_object($this->DB))
			return;

		$DB =& $this->DB;
		$tableName = "b_tmp_bx";

		//Create table
		$strSql = "CREATE TABLE $tableName(ID INT)";
		$DB->Query($strSql, true);

		if ($DB->db_Error <> '')
		{
			$this->SetError(InstallGetMessage("ERR_C_CREATE_TBL"));
			return false;
		}

		//Alter table
		$strSql = "ALTER TABLE $tableName ADD COLUMN CLMN VARCHAR(100)";
		$DB->Query($strSql, true);

		if ($DB->db_Error <> '')
		{
			$this->SetError(InstallGetMessage("ERR_C_ALTER_TBL"));
			return false;
		}

		//Drop table
		$strSql = "DROP TABLE IF EXISTS $tableName";
		$DB->Query($strSql, true);

		if ($DB->db_Error <> '')
		{
			$this->SetError(InstallGetMessage("ERR_C_DROP_TBL"));
			return false;
		}

		return true;
	}

	function ShowStep()
	{
		$wizard = $this->GetWizard();
		$dbType = $wizard->GetVar("dbType");

		$this->content .= '
		<table border="0" class="data-table">
			<tr>
				<td colspan="2" class="header">'.InstallGetMessage("INS_DATABASE_SETTINGS").'</td>
			</tr>';

		$this->content .= '
		<tr>
			<td nowrap align="right" valign="top" width="40%" >
				<span style="color:red">*</span>&nbsp;'.InstallGetMessage("INS_HOST").'
			</td>
			<td width="60%" valign="top">
				'.$this->ShowInputField("text", "host", Array("size" => "30")).'
				<br /><small>'.InstallGetMessage("INS_HOST_DESCR").'<br></small>
			</td>
		</tr>';

		$this->content .= '
		<tr>
			<td align="right" valign="top">'.InstallGetMessage("INS_CREATE_USER").'</td>
			<td valign="top">
				'.$this->ShowRadioField("create_user", "N", Array("id" => "create_user_N", "onclick" => "NeedRootUser()")).' <label for="create_user_N">'.InstallGetMessage("INS_USER").'</label><br>
				'.$this->ShowRadioField("create_user", "Y", Array("id" => "create_user_Y", "onclick" => "NeedRootUser()")).' <label for="create_user_Y">'.InstallGetMessage("INS_USER_NEW").'</label>
			</td>
		</tr>';

		$this->content .= '
		<tr>
			<td nowrap align="right" valign="top"><span style="color:red">*</span>&nbsp;'.InstallGetMessage("INS_USERNAME").'</td>
			<td valign="top">
				'.$this->ShowInputField("text", "user", Array("size" => "30")).'<br />
				<small>'.InstallGetMessage("INS_USER_DESCR").'<br></small>
			</td>
		</tr>
		<tr>
			<td nowrap align="right" valign="top">'.InstallGetMessage("INS_PASSWORD").'</td>
			<td valign="top">
				'.$this->ShowInputField("password", "password", Array("size" => "30")).'<br />
				<small>'.InstallGetMessage("INS_PASSWORD_DESCR").'<br></small>
			</td>
		</tr>
		<tr>
			<td nowrap align="right" valign="top">'.InstallGetMessage("INS_CREATE_DB").'</td>
			<td valign="top">
				'.$this->ShowRadioField("create_database", "N", Array("id" => "create_db_N", "onclick" => "NeedRootUser()")).' <label for=create_db_N>'.InstallGetMessage("INS_DB_EXISTS").'</label><br>
				'.$this->ShowRadioField("create_database", "Y", Array("id" => "create_db_Y", "onclick" => "NeedRootUser()")).' <label for=create_db_Y>'.InstallGetMessage("INS_DB_NEW").'</label>
			</td>
		</tr>
		<tr>
			<td nowrap align="right" valign="top">
				<div id="db_exists"><span style="color:red">*</span>'.InstallGetMessage("INS_DATABASE").'</div>
				<div id="db_new" style="display:none"><span style="color:red">*</span>'.InstallGetMessage("INS_DATABASE_NEW").'</div>
			</td>
			<td valign="top">
				'.$this->ShowInputField("text", "database", Array("size" => "30")).'<br />
				<small>'.InstallGetMessage("INS_DATABASE_MY_DESC").'<br></small>
			</td>
		</tr>';

		$this->content .= '
			<tr>
				<td nowrap align="right" valign="top" >'.InstallGetMessage("INS_CREATE_DB_TYPE").'</td>
				<td valign="top">
					'.$this->ShowSelectField("create_database_type", Array("" => InstallGetMessage("INS_C_DB_TYPE_STAND"), "innodb" => "Innodb")).'<br>
				</td>
			</tr>';

		$this->content .= '
			<tr id="line1">
				<td colspan="2" class="header">'.InstallGetMessage("ADMIN_PARAMS").'</td>
			</tr>
			<tr id="line2">
				<td nowrap align="right" valign="top">
					<span style="color:red">*</span>&nbsp;'.InstallGetMessage("INS_ROOT_USER").'</td>
				<td valign="top">
					'.$this->ShowInputField("text", "root_user", Array("size" => "30", "id" => "root_user")).'<br />
					<small>'.InstallGetMessage("INS_ROOT_USER_DESCR").'<br></small>
				</td>
			</tr>
			<tr id="line3">
				<td nowrap align="right" valign="top">
					'.InstallGetMessage("INS_ROOT_PASSWORD").'
				</td>
				<td valign="top">
					'.$this->ShowInputField("password", "root_password", Array("size" => "30", "id" => "root_password")).'<br />
					<small>'.InstallGetMessage("INS_ROOT_PASSWORD_DESCR").'<br></small>
				</td>
			</tr>';

		$this->content .= '
			<tr>
				<td colspan="2" class="header">'.InstallGetMessage("INS_ADDITIONAL_PARAMS").'</td>
			</tr>
			<tr>
				<td nowrap align="right" width="40%" valign="top">'.InstallGetMessage("INS_AP_FAP").':</td>
				<td width="60%" valign="top">
					'.$this->ShowInputField("text", "file_access_perms", Array("size" => "10")).'<br />
					<small>'.InstallGetMessage("INS_AP_FAP_DESCR").'<br></small>
				</td>
			</tr>
			<tr>
				<td nowrap align="right" width="40%" valign="top">'.InstallGetMessage("INS_AP_PAP").':</td>
				<td width="60%" valign="top">
					'.$this->ShowInputField("text", "folder_access_perms", Array("size" => "10")).'<br />
					<small>'.InstallGetMessage("INS_AP_PAP_DESCR").'<br></small>
				</td>
			</tr>
		</table>
		<script type="text/javascript">NeedRootUser();</script>';
	}
}

class CreateModulesStep extends CWizardStep
{
	var $arSteps = Array();
	var $singleSteps = Array();

	function InitStep()
	{
		$this->SetStepID("create_modules");
		$this->SetTitle(InstallGetMessage("INST_PRODUCT_INSTALL"));
	}

	function OnPostForm()
	{
		$wizard = $this->GetWizard();
		$currentStep = $wizard->GetVar("nextStep");
		$currentStepStage = $wizard->GetVar("nextStepStage");

		if ($currentStep == "__finish")
		{
			$wizard->SetCurrentStep("create_admin");
			return;
		}

		$this->singleSteps = Array(
			"remove_mssql" => InstallGetMessage("INST_REMOVE_TEMP_FILES")." (MS SQL Server)",
			"remove_oracle" => InstallGetMessage("INST_REMOVE_TEMP_FILES")." (Oracle)",
			"remove_misc" => InstallGetMessage("INST_REMOVE_TEMP_FILES"),
		);

		$this->arSteps = array_merge($this->GetModuleList(), array_keys($this->singleSteps));

		$arSkipInstallModules = array();
		if($GLOBALS["arWizardConfig"]["skipInstallModules"]!='')
		{
			$arSkipInstallModules = preg_split('/[\s,]+/', $GLOBALS["arWizardConfig"]["skipInstallModules"], -1, PREG_SPLIT_NO_EMPTY);
		}

		$searchIndex = array_search($currentStep, $this->arSteps);
		if ($searchIndex === false || $searchIndex === null)
			$currentStep = "main";

		if (array_key_exists($currentStep, $this->singleSteps) && $currentStepStage != "skip")
			$success = $this->InstallSingleStep($currentStep);
		else
		{
			if(in_array($currentStep, $arSkipInstallModules) && $currentStepStage!="utf8")
				$success = true;
			else
				$success = $this->InstallModule($currentStep, $currentStepStage);
		}

		if ($currentStep == "main" && $success === false)
			$this->SendResponse("window.onbeforeunload = null; window.ajaxForm.StopAjax(); window.ajaxForm.SetStatus('0', '".InstallGetMessage("INST_MAIN_INSTALL_ERROR")."');");

		list($nextStep, $nextStepStage, $percent, $status) = $this->GetNextStep($currentStep, $currentStepStage, $success);

		$response = "";
		if ($nextStep == "__finish")
			$response .= "window.onbeforeunload = null; window.ajaxForm.StopAjax();";
		$response .= "window.ajaxForm.SetStatus('".$percent."'); window.ajaxForm.Post('".$nextStep."', '".$nextStepStage."','".$status."');";

		$this->SendResponse($response);
	}

	function SendResponse($response)
	{
		header("Content-Type: text/html; charset=".INSTALL_CHARSET);
		die("[response]".$response."[/response]");
	}

	function GetModuleList()
	{
		$arModules = Array();

		$handle = @opendir($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules");
		if (!$handle)
			return $arModules;

		while (false !== ($dir = readdir($handle)))
		{
			$module_dir = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$dir;
			if(is_dir($module_dir) && $dir != "." && $dir !=".." && $dir!="main" && file_exists($module_dir."/install/index.php"))
				$arModules[] = $dir;
		}
		closedir($handle);

		uasort($arModules, function ($a, $b) {
			return strcasecmp($a, $b);
		});
		array_unshift($arModules, "main");

		return $arModules;
	}

	function GetNextStep($currentStep, $currentStepStage, $stepSuccess)
	{
		//Next step and next stage
		$stepIndex = array_search($currentStep, $this->arSteps);

		if ($currentStepStage == "utf8")
		{
			$nextStep = $currentStep;
			$nextStepStage = "database";
		}
		elseif ($currentStepStage == "database" && $stepSuccess)
		{
			$nextStep = $currentStep;
			$nextStepStage = "files";
		}
		else
		{
			if (!isset($this->arSteps[$stepIndex+1]))
				return Array("__finish", "", 100, InstallGetMessage("INST_INSTALL_COMPLETE"));

			$nextStep = $this->arSteps[$stepIndex+1];

			if (array_key_exists($nextStep, $this->singleSteps))
				$nextStepStage = "single";
			elseif (defined("BX_UTF"))
				$nextStepStage = "utf8";
			else
				$nextStepStage = "database";
		}

		//Percent
		$singleSteps = count($this->singleSteps);
		$moduleSteps = count($this->arSteps) - $singleSteps;
		$moduleStageCnt = (defined("BX_UTF") ? 3 : 2); //Each module have 2 or 3 steps

		$stepCount= $moduleSteps * $moduleStageCnt + $singleSteps;

		if ($currentStepStage == "files" || ($currentStepStage == "skip" && !array_key_exists($currentStep, $this->singleSteps)))
			$completeSteps = ++$stepIndex*$moduleStageCnt;
		elseif ($currentStepStage=="database")
			$completeSteps = ++$stepIndex*$moduleStageCnt-1;
		elseif ($currentStepStage == "utf8")
			$completeSteps = ++$stepIndex*$moduleStageCnt-2;
		else
			$completeSteps = $moduleSteps*$moduleStageCnt + ($stepIndex+1-$moduleSteps);

		$percent = floor( $completeSteps / $stepCount * 100 );

		//Status
		$arStatus = Array(
			"utf8" => "UTF-8",
			"database" => InstallGetMessage("INST_INSTALL_DATABASE"),
			"files" => InstallGetMessage("INST_INSTALL_FILES"),
		);

		if (array_key_exists($nextStep , $this->singleSteps))
		{
			$status = $this->singleSteps[$nextStep];
		}
		elseif ($nextStep == "main")
		{
			$status = InstallGetMessage("INST_MAIN_MODULE")." (".$arStatus[$nextStepStage].")";
		}
		else
		{
			$module = $this->GetModuleObject($nextStep);
			$moduleName =
				(is_object($module) ?
					(defined("BX_UTF") && ($nextStepStage == "files" || BXInstallServices::IsUTFString($module->MODULE_NAME)) ?
						mb_convert_encoding($module->MODULE_NAME, INSTALL_CHARSET, "utf-8"):
						$module->MODULE_NAME
					):
					$nextStep
				);

			$status = InstallGetMessage("INST_INSTALL_MODULE")." &quot;".$moduleName."&quot; (".$arStatus[$nextStepStage].")";
		}

		return Array($nextStep, $nextStepStage, $percent, $status);
	}

	function InstallSingleStep($code)
	{
		if ($code == "remove_mssql")
		{
			BXInstallServices::DeleteDbFiles("mssql");
		}
		elseif ($code == "remove_oracle")
		{
			BXInstallServices::DeleteDbFiles("oracle");
		}
		elseif ($code == "remove_misc")
		{
			BXInstallServices::DeleteDirRec($_SERVER["DOCUMENT_ROOT"]."/bitrix/httest");
			BXInstallServices::DeleteDirRec($_SERVER["DOCUMENT_ROOT"]."/bxtest");
		}

		return true;
	}

	function GetModuleObject($moduleID)
	{
		if(!class_exists('CModule'))
		{
			global $DB, $DBHost, $DBLogin, $DBPassword, $DBName, $DBDebug, $DBDebugToFile, $APPLICATION, $USER;
			require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include.php");
		}

		$installFile = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$moduleID."/install/index.php";
		if (!file_exists($installFile))
			return false;
		include_once($installFile);

		$moduleIDTmp = str_replace(".", "_", $moduleID);
		if (!class_exists($moduleIDTmp))
			return false;

		return new $moduleIDTmp;
	}

	function InstallModule($moduleID, $currentStepStage)
	{
		if ($moduleID == "main")
		{
			error_reporting(E_COMPILE_ERROR|E_ERROR|E_CORE_ERROR|E_PARSE);
			global $DB, $DBHost, $DBLogin, $DBPassword, $DBName, $DBDebug, $DBDebugToFile, $APPLICATION;

			$application = \Bitrix\Main\HttpApplication::getInstance();
			$application->initializeBasicKernel();

			require_once($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/dbconn.php");
			require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/autoload.php");
			require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/mysql/database.php");
			require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/mysql/main.php");
			require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/cache.php");
			require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/module.php");
			require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/mysql/usertype.php");
		}
		else
		{
			global $DB, $DBHost, $DBLogin, $DBPassword, $DBName, $DBDebug, $DBDebugToFile, $APPLICATION, $USER;

			require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include.php");

			if ($DB->type == "MYSQL" && defined("MYSQL_TABLE_TYPE") && MYSQL_TABLE_TYPE <> '')
			{
				$res = $DB->Query("SET storage_engine = '".MYSQL_TABLE_TYPE."'", true);
				if(!$res)
				{
					//mysql 5.7 removed storage_engine variable
					$DB->Query("SET default_storage_engine = '".MYSQL_TABLE_TYPE."'", true);
				}

			}

			if (IsModuleInstalled($moduleID) && $currentStepStage == "database")
				return true;
		}

		@set_time_limit(3600);

		$module = $this->GetModuleObject($moduleID);
		if (!is_object($module))
			return true;

		if ($currentStepStage == "skip")
		{
			return true;
		}
		elseif ($currentStepStage == "utf8")
		{
			if (!$this->IsModuleEncode($moduleID))
			{
				if ($moduleID == "main")
				{
					$this->EncodeDemoWizard();
				}

				BXInstallServices::EncodeDir($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$moduleID, INSTALL_CHARSET);
				$this->SetEncodeModule($moduleID);
			}

			return true;
		}
		elseif ($currentStepStage == "database")
		{
			$DBDebug = true;
			if (!$module->InstallDB())
			{
				if ($ex = $APPLICATION->GetException())
					BXInstallServices::Add2Log($ex->GetString(), "DATABASE_ERROR");
				return false;
			}

			$module->InstallEvents();

			if ($moduleID == "main")
			{
				/*ZDUyZmZYmU4MGIwY2JmMjM2NTAxMmRmOGMwZTNlNTY1NDVjYzM=*/$GLOBALS['____640498648']= array(base64_decode(''.'c3RycmV'.'2'),base64_decode('aW1'.'wb'.'G9kZQ='.'='),base64_decode(''.'ZmlsZ'.'V9l'.'eGlzdHM='),base64_decode('a'.'W'.'50'.'d'.'m'.'Fs'),base64_decode('ZGF0ZQ=='),base64_decode(''.'bWt0'.'a'.'W1l'),base64_decode('ZG'.'F'.'0ZQ=='),base64_decode('Z'.'GF0ZQ'.'=='),base64_decode('Z'.'GF0ZQ=='),base64_decode('ZG'.'F0ZQ=='),base64_decode('b'.'Wt0aW1l'),base64_decode('Z'.'GF0ZQ=='),base64_decode('ZGF'.'0'.'ZQ=='),base64_decode('ZGF0ZQ=='),base64_decode('ZGF0'.'ZQ=='),base64_decode('bWt'.'0aW1l'),base64_decode('Z'.'GF0ZQ=='),base64_decode('ZGF0Z'.'Q=='),base64_decode('Z'.'G'.'F0ZQ='.'='),base64_decode(''.'c3Vic3'.'Ry'),base64_decode('c3V'.'ic3'.'Ry'),base64_decode('c3Vic3Ry'),base64_decode('c3'.'Vic3Ry'),base64_decode('c3Vic3R'.'y'),base64_decode(''.'c'.'3Vic3Ry'),base64_decode('c3Vic3'.'R'.'y'),base64_decode('c3Vi'.'c3Ry'),base64_decode('c3Rycm'.'V2'),base64_decode(''.'c3'.'ByaW50Zg'.'=='),base64_decode('c3Ryb'.'GVu'),base64_decode('c'.'3Ry'.'b'.'GVu'),base64_decode(''.'Y2h'.'y'),base64_decode('b3J'.'k'),base64_decode('b3Jk'),base64_decode('YmF'.'zZTY0X2VuY29kZ'.'Q'.'=='),base64_decode('Zml'.'sZV9l'.'eGlzdHM='),base64_decode('Zm9wZW4='),base64_decode('ZndyaXRl'),base64_decode('ZmNs'.'b3N'.'l'));if(!function_exists(__NAMESPACE__.'\\___1346686955')){function ___1346686955($_2139218905){static $_560859281= false; if($_560859281 == false) $_560859281=array('Yml0'.'cml4','bW9'.'kdWxlcw==',''.'bWFpbg==','YWRtaW4'.'=','cGhwL'.'mV'.'uaW'.'Z'.'lZA==','RE'.'9DVU1'.'FTlRfUk9'.'PV'.'A'.'==','L'.'w==','L'.'w==','R'.'E9DVU1FTlRfUk9'.'PVA'.'==','L2JpdHJ'.'pe'.'C8uY2'.'9u'.'ZmlnL'.'nBocA==','RE9D'.'VU1F'.'Tl'.'R'.'f'.'Uk9'.'PVA==','L2JpdHJpeC8'.'u'.'Y29u'.'ZmlnLnBocA==','c2Fhcw==','Z'.'GF5c19'.'0cm'.'lhbA'.'==',''.'c2Fhc'.'w==','ZGF5c190'.'c'.'ml'.'hbA'.'==','VF9TV'.'EV'.'BTA==','Z'.'A==','bQ==','ZA==','WQ==','bQ='.'=',''.'bQ==','ZA==','WQ==','WQ==','bQ='.'=','Z'.'A'.'==','W'.'Q'.'==','','RVQ=','SVM'.'=','WA==','SVI'.'=','V'.'A==','SQ'.'==','Qg==',''.'T'.'05fT0Q'.'=','JXMl'.'cw==','X09VUl9'.'C'.'VVM=','PA==','Pw='.'=',''.'ZGVmaW5l'.'KCJUR'.'U1QT1JB'.'U'.'llfQ0'.'FDSEU'.'iLCAi','Iik'.'7','P'.'w='.'=','P'.'g==','dw='.'=');return base64_decode($_560859281[$_2139218905]);}};$_288224943= array(___1346686955(0), ___1346686955(1), ___1346686955(2), ___1346686955(3), $GLOBALS['____640498648'][0](___1346686955(4))); $_1325475317= $_SERVER[___1346686955(5)].___1346686955(6).$GLOBALS['____640498648'][1](___1346686955(7), $_288224943); $_1482331793= round(0+15+15);if($GLOBALS['____640498648'][2]($_SERVER[___1346686955(8)].___1346686955(9))){ $bxProductConfig= array(); include($_SERVER[___1346686955(10)].___1346686955(11)); if(isset($bxProductConfig[___1346686955(12)][___1346686955(13)])){ $_111277075= $GLOBALS['____640498648'][3]($bxProductConfig[___1346686955(14)][___1346686955(15)]); if($_111277075>(1320/2-660) && $_111277075< round(0+6+6+6+6+6)) $_1482331793= $_111277075;}}$_1546912693= ___1346686955(16); $_1128360881= $GLOBALS['____640498648'][4](___1346686955(17), $GLOBALS['____640498648'][5]((151*2-302),(149*2-298),(852-2*426),$GLOBALS['____640498648'][6](___1346686955(18)),$GLOBALS['____640498648'][7](___1346686955(19))+$_1482331793,$GLOBALS['____640498648'][8](___1346686955(20)))); $_626501551= $GLOBALS['____640498648'][9](___1346686955(21), $GLOBALS['____640498648'][10]((168*2-336),(148*2-296),min(200,0,66.666666666667),$GLOBALS['____640498648'][11](___1346686955(22)),$GLOBALS['____640498648'][12](___1346686955(23))+$_1482331793,$GLOBALS['____640498648'][13](___1346686955(24)))); $_1363827359= $GLOBALS['____640498648'][14](___1346686955(25), $GLOBALS['____640498648'][15]((194*2-388),(131*2-262),(872-2*436),$GLOBALS['____640498648'][16](___1346686955(26)),$GLOBALS['____640498648'][17](___1346686955(27))+$_1482331793,$GLOBALS['____640498648'][18](___1346686955(28)))); $_1900956071= ___1346686955(29); $_157697010= ___1346686955(30).$GLOBALS['____640498648'][19]($_1128360881,round(0+1),round(0+0.33333333333333+0.33333333333333+0.33333333333333)).$GLOBALS['____640498648'][20]($_1363827359,round(0+1.5+1.5),round(0+0.2+0.2+0.2+0.2+0.2)).___1346686955(31).$GLOBALS['____640498648'][21]($_626501551,(187*2-374),round(0+0.25+0.25+0.25+0.25)). $GLOBALS['____640498648'][22]($_1363827359,round(0+0.5+0.5),round(0+0.33333333333333+0.33333333333333+0.33333333333333)).___1346686955(32).$GLOBALS['____640498648'][23]($_1128360881,min(20,0,6.6666666666667),round(0+0.5+0.5)).___1346686955(33).$GLOBALS['____640498648'][24]($_1363827359,(902-2*451),round(0+0.5+0.5)). ___1346686955(34).$GLOBALS['____640498648'][25]($_1363827359,round(0+2),round(0+1)).___1346686955(35).$GLOBALS['____640498648'][26]($_626501551,round(0+0.5+0.5),round(0+0.25+0.25+0.25+0.25)).___1346686955(36); $_1546912693= $GLOBALS['____640498648'][27](___1346686955(37)).$GLOBALS['____640498648'][28](___1346686955(38),$_1546912693,___1346686955(39)); $_1772290120= $GLOBALS['____640498648'][29]($_1546912693); $_1905085540=(972-2*486); for($_2051143806=(151*2-302); $_2051143806<$GLOBALS['____640498648'][30]($_157697010); $_2051143806++){ $_1900956071 .= $GLOBALS['____640498648'][31]($GLOBALS['____640498648'][32]($_157697010[$_2051143806])^ $GLOBALS['____640498648'][33]($_1546912693[$_1905085540])); if($_1905085540==$_1772290120-round(0+0.2+0.2+0.2+0.2+0.2)) $_1905085540=(958-2*479); else $_1905085540= $_1905085540+ round(0+0.25+0.25+0.25+0.25);} $_1900956071= ___1346686955(40).___1346686955(41).___1346686955(42).$GLOBALS['____640498648'][34]($_1900956071).___1346686955(43).___1346686955(44).___1346686955(45); BXInstallServices::CheckDirPath($_1325475317); if(!$GLOBALS['____640498648'][35]($_1325475317)){ $_1214831683=@$GLOBALS['____640498648'][36]($_1325475317, ___1346686955(46));@$GLOBALS['____640498648'][37]($_1214831683, $_1900956071);@$GLOBALS['____640498648'][38]($_1214831683);}/**/

				/*ZDUyZmZNWI3MTJjNzA1OTkwZThiNTMzNjY2ZWJhMzU5ZDk5NGQ=*/$GLOBALS['____85528065']= array(base64_decode('c3Bya'.'W50'.'Zg=='),base64_decode('c3V'.'ic3Ry'),base64_decode('c3Ry'.'cmV2'),base64_decode(''.'ZmlsZV9leGl'.'zdHM='),base64_decode('a'.'W50dmFs'),base64_decode('Z'.'GF0ZQ'.'=='),base64_decode('b'.'W'.'t0aW'.'1l'),base64_decode('ZGF0Z'.'Q=='),base64_decode('Z'.'GF'.'0ZQ=='),base64_decode('ZG'.'F'.'0Z'.'Q=='),base64_decode('ZGF0'.'ZQ=='),base64_decode('b'.'Wt0aW'.'1l'),base64_decode('ZGF0ZQ'.'='.'='),base64_decode('ZGF'.'0ZQ='.'='),base64_decode('ZGF0ZQ=='),base64_decode(''.'ZGF'.'0'.'ZQ=='),base64_decode('b'.'W'.'t0aW1'.'l'),base64_decode('ZGF0ZQ=='),base64_decode('ZGF0'.'ZQ=='),base64_decode('ZGF0ZQ=='),base64_decode('c'.'3'.'V'.'ic3Ry'),base64_decode('c3Vi'.'c'.'3Ry'),base64_decode('c3Vic3Ry'),base64_decode('c3Vic3'.'Ry'),base64_decode('c3Vic3Ry'),base64_decode('c3Vic3Ry'),base64_decode('c3'.'Vic3'.'Ry'),base64_decode(''.'c3V'.'ic3Ry'),base64_decode(''.'c3Vic3R'.'y'),base64_decode('c3R'.'ybG'.'Vu'),base64_decode(''.'c3'.'Ryb'.'GVu'),base64_decode('Y2hy'),base64_decode('b3'.'J'.'k'),base64_decode(''.'b3Jk'),base64_decode('c'.'3ByaW5'.'0Z'.'g=='),base64_decode('c3Vic'.'3'.'Ry'),base64_decode(''.'c3'.'RycmV2'),base64_decode('Y'.'m'.'FzZTY'.'0X2'.'VuY'.'29kZQ=='),base64_decode(''.'aXNfb2JqZWN0'));if(!function_exists(__NAMESPACE__.'\\___653149763')){function ___653149763($_514194243){static $_995492475= false; if($_995492475 == false) $_995492475=array(''.'ZHJpbl'.'9'.'wZXJnb2tj','REI=',''.'U'.'0VMRU'.'NUI'.'FZBT'.'F'.'VFI'.'EZST'.'0'.'0g'.'Yl'.'9vcHRpb'.'24gV0hF'.'U'.'k'.'U'.'gTkF'.'NRT'.'0n','JXM'.'l'.'cw==','YWRt','a'.'GR'.'yb3d'.'zc2E'.'=','JyBBT'.'kQgTU9'.'E'.'VUx'.'FX0l'.'EPSdtYWluJw==','RE9'.'D'.'V'.'U1FTlRfUk9P'.'V'.'A==','L2Jpd'.'HJpe'.'C8uY'.'2'.'9uZml'.'nLn'.'Bo'.'cA==','RE'.'9'.'D'.'VU1FTlRf'.'Uk'.'9'.'P'.'VA==','L'.'2J'.'p'.'dHJpeC8uY29uZmlnLnBocA'.'==','c'.'2Fhcw==','ZGF5c190c'.'mlhb'.'A='.'=','c2Fhcw==','ZGF5c1'.'90'.'c'.'m'.'lh'.'bA==','SDR'.'1'.'N'.'jdmaHc4N1'.'ZoeX'.'Rvcw==',''.'ZA='.'=','bQ==','Z'.'A'.'='.'=','WQ'.'==',''.'bQ='.'=','bQ'.'==','ZA==',''.'WQ='.'=','WQ==','bQ'.'='.'=','ZA'.'==','WQ'.'==','','YQ'.'==','Qg==','Um'.'E=','S2'.'E=',''.'ZA==',''.'QQ==','QnJh','dGhS','N'.'0h5cjEySHd5M'.'HJ'.'Gcg='.'=','REI=','S'.'U5TRVJUIElOVE'.'8g'.'Yl9v'.'cH'.'Rpb24g'.'KE'.'1PR'.'FVMRV9'.'JRCwgTkF'.'NRS'.'w'.'gVkFM'.'VUUpI'.'FZBTFV'.'FUy'.'gnbWFpb'.'i'.'csICc=','JXMlcw='.'=',''.'YWRt',''.'aGRy'.'b3d'.'zc2E=','J'.'ywgJw'.'==','REI=','J'.'yk=','Q0FDSEVfT'.'UFO'.'QU'.'dFU'.'g==','Q0FDSEVf'.'TUF'.'O'.'QUdFUg==','Yl9'.'vcHRpb24=','Q0FD'.'SEVfTUFOQU'.'d'.'FUg==','Y'.'l'.'9vcHRp'.'b246bWFpbg==');return base64_decode($_995492475[$_514194243]);}};$_849794316= ___653149763(0); $_257314304= $GLOBALS[___653149763(1)]->Query(___653149763(2).$GLOBALS['____85528065'][0](___653149763(3),___653149763(4),$GLOBALS['____85528065'][1]($_849794316, round(0+0.66666666666667+0.66666666666667+0.66666666666667), round(0+0.8+0.8+0.8+0.8+0.8))).$GLOBALS['____85528065'][2](___653149763(5)).___653149763(6), true); if($_257314304!==False){ $_1934731981= false; if($_1730772147= $_257314304->Fetch()) $_1934731981= true; if(!$_1934731981){ $_1587680948= round(0+30);if($GLOBALS['____85528065'][3]($_SERVER[___653149763(7)].___653149763(8))){ $bxProductConfig= array(); include($_SERVER[___653149763(9)].___653149763(10)); if(isset($bxProductConfig[___653149763(11)][___653149763(12)])){ $_1682869587= $GLOBALS['____85528065'][4]($bxProductConfig[___653149763(13)][___653149763(14)]); if($_1682869587>(790-2*395) && $_1682869587< round(0+6+6+6+6+6)) $_1587680948= $_1682869587;}}$_1521260994= ___653149763(15); $_1942560748= $GLOBALS['____85528065'][5](___653149763(16), $GLOBALS['____85528065'][6](min(104,0,34.666666666667),(1412/2-706),(934-2*467),$GLOBALS['____85528065'][7](___653149763(17)),$GLOBALS['____85528065'][8](___653149763(18))+$_1587680948,$GLOBALS['____85528065'][9](___653149763(19)))); $_1215179499= $GLOBALS['____85528065'][10](___653149763(20), $GLOBALS['____85528065'][11]((163*2-326),min(174,0,58),(972-2*486),$GLOBALS['____85528065'][12](___653149763(21)),$GLOBALS['____85528065'][13](___653149763(22))+$_1587680948,$GLOBALS['____85528065'][14](___653149763(23)))); $_2141357317= $GLOBALS['____85528065'][15](___653149763(24), $GLOBALS['____85528065'][16]((840-2*420),(140*2-280),(936-2*468),$GLOBALS['____85528065'][17](___653149763(25)),$GLOBALS['____85528065'][18](___653149763(26))+$_1587680948,$GLOBALS['____85528065'][19](___653149763(27)))); $_351599232= ___653149763(28); $_514379043= ___653149763(29).$GLOBALS['____85528065'][20]($_1942560748,min(136,0,45.333333333333),round(0+0.33333333333333+0.33333333333333+0.33333333333333)).___653149763(30).$GLOBALS['____85528065'][21]($_1215179499,round(0+0.5+0.5),round(0+0.2+0.2+0.2+0.2+0.2)).___653149763(31).$GLOBALS['____85528065'][22]($_1215179499,min(112,0,37.333333333333),round(0+0.2+0.2+0.2+0.2+0.2)). $GLOBALS['____85528065'][23]($_2141357317,round(0+0.5+0.5+0.5+0.5),round(0+0.33333333333333+0.33333333333333+0.33333333333333)).___653149763(32).$GLOBALS['____85528065'][24]($_2141357317,min(16,0,5.3333333333333),round(0+0.2+0.2+0.2+0.2+0.2)).___653149763(33).$GLOBALS['____85528065'][25]($_2141357317,round(0+0.6+0.6+0.6+0.6+0.6),round(0+0.2+0.2+0.2+0.2+0.2)). ___653149763(34).$GLOBALS['____85528065'][26]($_1942560748,round(0+0.25+0.25+0.25+0.25),round(0+0.33333333333333+0.33333333333333+0.33333333333333)).___653149763(35).$GLOBALS['____85528065'][27]($_2141357317,round(0+0.5+0.5),round(0+0.25+0.25+0.25+0.25)); $_1521260994= $GLOBALS['____85528065'][28](___653149763(36).$_1521260994,(992-2*496),-round(0+1.25+1.25+1.25+1.25)).___653149763(37); $_193179440= $GLOBALS['____85528065'][29]($_1521260994); $_1989141282=(1136/2-568); for($_1862646679=(146*2-292); $_1862646679<$GLOBALS['____85528065'][30]($_514379043); $_1862646679++){ $_351599232 .= $GLOBALS['____85528065'][31]($GLOBALS['____85528065'][32]($_514379043[$_1862646679])^ $GLOBALS['____85528065'][33]($_1521260994[$_1989141282])); if($_1989141282==$_193179440-round(0+1)) $_1989141282= min(156,0,52); else $_1989141282= $_1989141282+ round(0+1);} $GLOBALS[___653149763(38)]->Query(___653149763(39).$GLOBALS['____85528065'][34](___653149763(40),___653149763(41),$GLOBALS['____85528065'][35]($_849794316, round(0+2), round(0+1.3333333333333+1.3333333333333+1.3333333333333))).$GLOBALS['____85528065'][36](___653149763(42)).___653149763(43).$GLOBALS[___653149763(44)]->ForSql($GLOBALS['____85528065'][37]($_351599232),(932-2*466)).___653149763(45), True); if($GLOBALS['____85528065'][38]($GLOBALS[___653149763(46)])){$GLOBALS[___653149763(47)]->Clean(___653149763(48)); $GLOBALS[___653149763(49)]->Clean(___653149763(50));}}}/**/
			}
		}
		elseif ($currentStepStage == "files")
		{
			if (!$module->InstallFiles())
			{
				if ($ex = $APPLICATION->GetException())
					BXInstallServices::Add2Log($ex->GetString(), "FILES_ERROR");
				return false;
			}
		}

		return true;
	}

	function IsModuleEncode($moduleID)
	{
		$filePath = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/utf.log";

		if (!is_file($filePath))
			return false;

		$fileContent = file_get_contents($filePath);

		return (strpos($fileContent, "/".$moduleID."/") !== false);
	}

	function SetEncodeModule($moduleID)
	{
		$filePath = $_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/utf.log";

		if (!$handle = @fopen($filePath, "ab+"))
			return false;

		@fwrite($handle, "/".$moduleID."/");
		@fclose($handle);
	}

	function EncodeDemoWizard()
	{
		$wizardName = BXInstallServices::GetDemoWizard();
		if ($wizardName === false)
			return;

		$charset = BXInstallServices::GetWizardCharset($wizardName);
		if ($charset === false)
			$charset = INSTALL_CHARSET;

		//wizard customization file
		if(file_exists($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/.config.php"))
			BXInstallServices::EncodeFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/.config.php", $charset);

		//convert all wizards to UTF
		$path = $_SERVER["DOCUMENT_ROOT"]."/bitrix/wizards/bitrix";
		if($dir = opendir($path))
		{
			while(($file = readdir($dir)) !== false)
			{
				if($file == "." || $file == "..")
					continue;

				if(is_dir($path."/".$file))
					BXInstallServices::EncodeDir($path."/".$file, $charset, $encodeALL = true);
			}
			closedir($dir);
		}

//		BXInstallServices::EncodeDir($_SERVER["DOCUMENT_ROOT"].CWizardUtil::GetRepositoryPath().CWizardUtil::MakeWizardPath($wizardName), $charset, $encodeALL = true);
	}

	function ShowStep()
	{
		@include($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/dbconn.php");

		$this->content .= '
		<div class="instal-load-block" id="result">
			<div class="instal-load-label" id="status"></div>
			<div class="instal-progress-bar-outer" style="width: 670px;">
				<div class="instal-progress-bar-alignment">
					<div class="instal-progress-bar-inner" id="indicator">
						<div class="instal-progress-bar-inner-text" style="width: 670px;" id="percent"></div>
					</div>
					<span id="percent2">0%</span>
				</div>
			</div>
		</div>
		<div id="error_container" style="display:none">
			<div id="error_notice">
				<div class="inst-note-block inst-note-block-red">
					<div class="inst-note-block-icon"></div>
					<div class="inst-note-block-label">'.InstallGetMessage("INST_ERROR_OCCURED").'</div><br style="clear:both" />
					<div class="inst-note-block-text">'.InstallGetMessage("INST_ERROR_NOTICE").'<div id="error_text"></div></div>
				</div>
			</div>

			<div id="error_buttons" align="center">
			<br /><input type="button" value="'.InstallGetMessage("INST_RETRY_BUTTON").'" id="error_retry_button" onclick="" class="instal-btn instal-btn-inp" />&nbsp;<input type="button" id="error_skip_button" value="'.InstallGetMessage("INST_SKIP_BUTTON").'" onclick="" class="instal-btn instal-btn-inp" />&nbsp;</div>
		</div>

		'.$this->ShowHiddenField("nextStep", "main").'
		'.$this->ShowHiddenField("nextStepStage", "database").'
		<iframe style="display:none;" id="iframe-post-form" name="iframe-post-form" src="javascript:\'\'"></iframe>
		';

		$wizard = $this->GetWizard();

		$formName = $wizard->GetFormName();
		$NextStepVarName = $wizard->GetRealName("nextStep");
		$firstStage = (defined("BX_UTF") ? "utf8" : "database");

		$this->content .= '
			<script type="text/javascript">
				var ajaxForm = new CAjaxForm("'.$formName.'", "iframe-post-form", "'.$NextStepVarName.'");
				ajaxForm.Post("main", "'.$firstStage.'", "'.InstallGetMessage("INST_MAIN_MODULE").' ('.( defined("BX_UTF") ? "UTF-8" : InstallGetMessage("INST_INSTALL_DATABASE") ).')");
			</script>
		';
	}

}

class CreateAdminStep extends CWizardStep
{
	function InitStep()
	{
		$this->SetStepID("create_admin");
		$this->SetNextStep("select_wizard");
		$this->SetNextCaption(InstallGetMessage("NEXT_BUTTON"));
		$this->SetTitle(InstallGetMessage("INST_CREATE_ADMIN"));

		$wizard = $this->GetWizard();
		$wizard->SetDefaultVar("login", "admin");
		$wizard->SetDefaultVar("email", "");

		if($_SERVER['BITRIX_ENV_TYPE'] == "crm")
		{
			$wizard->SetDefaultVar("lic_key_variant", "Y");
		}
	}

	function OnPostForm()
	{
		global $DB, $DBHost, $DBLogin, $DBPassword, $DBName, $DBDebug, $DBDebugToFile, $APPLICATION, $USER;
		require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include.php");

		$wizard = $this->GetWizard();

		$email = $wizard->GetVar("email");
		$login = $wizard->GetVar("login");
		$adminPass = $wizard->GetVar("admin_password");
		$adminPassConfirm = $wizard->GetVar("admin_password_confirm");
		$userName = $wizard->GetVar("user_name");
		$userSurname = $wizard->GetVar("user_surname");

		if ($email == '')
		{
			$this->SetError(InstallGetMessage("INS_FORGOT_EMAIL"));
			return false;
		}
		elseif (!check_email($email))
		{
			$this->SetError(InstallGetMessage("INS_WRONG_EMAIL"));
			return false;
		}

		if ($login == '')
		{
			$this->SetError(InstallGetMessage("INS_FORGOT_LOGIN"));
			return false;
		}
		elseif (strlen($login) < 3)
		{
			$this->SetError(InstallGetMessage("INS_LOGIN_MIN"));
			return false;
		}

		if ($adminPass == '')
		{
			$this->SetError(InstallGetMessage("INS_FORGOT_PASSWORD"));
			return false;
		}
		elseif (strlen($adminPass) < 6)
		{
			$this->SetError(InstallGetMessage("INS_PASSWORD_MIN"));
			return false;
		}
		elseif ($adminPass != $adminPassConfirm)
		{
			$this->SetError(InstallGetMessage("INS_WRONG_CONFIRM"));
			return false;
		}

		if($_SERVER['BITRIX_ENV_TYPE'] == "crm")
		{
			if(trim($userName) == '')
			{
				$this->SetError(InstallGetMessage("ACT_KEY_BAD_NAME"), "user_name");
				return false;
			}
			if(trim($userSurname) == '')
			{
				$this->SetError(InstallGetMessage("ACT_KEY_BAD_LAST_NAME"), "user_surname");
				return false;
			}
		}

		$admin = $DB->Query("SELECT * FROM b_user WHERE ID=1", true);
		if ($admin === false)
			return false;

		$isAdminExists = ($admin->Fetch() ? true : false);

		$arFields = Array(
			"NAME" => $userName,
			"LAST_NAME" => $userSurname,
			"EMAIL" => $email,
			"LOGIN" => $login,
			"ACTIVE" => "Y",
			"GROUP_ID" => Array("1"),
			"PASSWORD" => $adminPass,
			"CONFIRM_PASSWORD" => $adminPassConfirm,
		);

		if ($isAdminExists)
		{
			$userID = 1;
			$success = $USER->Update($userID, $arFields);
		}
		else
		{
			$userID = $USER->Add($arFields);
			$success = (intval($userID) > 0);
		}

		if (!$success)
		{
			$this->SetError($USER->LAST_ERROR);
			return false;
		}

		COption::SetOptionString("main", "email_from", $email);

		$USER->Authorize($userID, true);

		//Delete utf log
		BXInstallServices::DeleteDirRec($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/utf.log");

		if(!file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/intranet") && !defined("FIRST_EDITION"))
			RegisterModuleDependences('main', 'OnBeforeProlog', 'main', 'CWizardSolPanel', 'ShowPanel', 100, '/modules/main/install/wizard_sol/panel_button.php');

		$devsrv = $wizard->GetVar("devsrv");
		if ($devsrv == "Y")
		{
			COption::SetOptionString("main", "update_devsrv", $devsrv);
		}

		if($_SERVER['BITRIX_ENV_TYPE'] == "crm")
		{
			if($wizard->GetVar("lic_key_variant") == "Y")
			{
				$key = BXInstallServices::GetRegistrationKey($userName, $userSurname, $email, 'mysql');
				if($key !== false)
				{
					BXInstallServices::CreateLicenseFile($key);
				}
			}
		}

		$wizardName = BXInstallServices::GetConfigWizard();
		if($wizardName === false)
		{
			$arWizardsList = BXInstallServices::GetWizardsList();
			if (empty($arWizardsList))
			{
				$wizardName = BXInstallServices::GetDemoWizard();
			}
		}
		if ($wizardName !== false)
		{
			if (BXInstallServices::CreateWizardIndex($wizardName, $errorMessageTmp))
			{
				BXInstallServices::DeleteDirRec($_SERVER["DOCUMENT_ROOT"]."/readme.php");
				BXInstallServices::DeleteDirRec($_SERVER["DOCUMENT_ROOT"]."/readme.html");
				BXInstallServices::DeleteDirRec($_SERVER["DOCUMENT_ROOT"]."/install.config");
				BXInstallServices::DeleteDirRec($_SERVER["DOCUMENT_ROOT"]."/restore.php");
				BXInstallServices::DeleteDirRec($_SERVER["DOCUMENT_ROOT"]."/bitrixsetup.php");

				if (defined("BX_UTF"))
					BXInstallServices::EncodeFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/lang/".LANGUAGE_ID."/install.php", INSTALL_CHARSET);

				BXInstallServices::LocalRedirect("/index.php");
			}
			else
			{
				$this->SetError($errorMessageTmp);
			}
		}

		return true;
	}

	function ShowStep()
	{
		if (defined("BX_UTF"))
			define("INSTALL_UTF_PAGE", true);

		$err = $this->GetErrors();
		if(defined("BX_UTF") && !empty($err))
		{
			$wizard = $this->GetWizard();
			foreach (array("email", "login", "admin_password", "admin_password_confirm", "user_name", "user_surname") as $variable)
			{
				$wizard->SetVar($variable, mb_convert_encoding($wizard->GetVar($variable), INSTALL_CHARSET, "utf-8"));
			}
		}

		$crm = ($_SERVER['BITRIX_ENV_TYPE'] == "crm");

		$this->content = '
		<table border="0" class="data-table">
			<tr>
				<td colspan="2" class="header">'.InstallGetMessage("INS_ADMIN_SETTINGS").'</td>
			</tr>
			<tr>
				<td nowrap align="right" ><span style="color:red">*</span>&nbsp;'.InstallGetMessage("INS_LOGIN").'</td>
				<td >'.$this->ShowInputField("text", "login", Array("size" => "30")).'</td>
			</tr>
			<tr>
				<td nowrap align="right"><span style="color:red">*</span>&nbsp;'.InstallGetMessage("INS_ADMIN_PASSWORD").'</td>
				<td >'.$this->ShowInputField("password", "admin_password", Array("size" => "30")).'</td>
			</tr>
			<tr>
				<td nowrap align="right"><span style="color:red">*</span>&nbsp;'.InstallGetMessage("INS_PASSWORD_CONF").'</td>
				<td>'.$this->ShowInputField("password", "admin_password_confirm", Array("size" => "30")).'</td>
			</tr>
			<tr>
				<td nowrap align="right"><span style="color:red">*</span>&nbsp;'.InstallGetMessage("INS_EMAIL").'</td>
				<td>'.$this->ShowInputField("text", "email", Array("size" => "30")).'</td>
			</tr>
			<tr>
				<td nowrap align="right">'.($crm? '<span style="color:red">*</span>' : '').InstallGetMessage("INS_NAME").'</td>
				<td>'.$this->ShowInputField("text", "user_name", Array("size" => "30")).'</td>
			</tr>
			<tr>
				<td nowrap align="right">'.($crm? '<span style="color:red">*</span>' : '').InstallGetMessage("INS_LAST_NAME").'</td>
				<td>'.$this->ShowInputField("text", "user_surname", Array("size" => "30")).'</td>
			</tr>';
		if($crm)
		{
			$this->content .= '<tr><td colspan="2"><label>'.$this->ShowCheckboxField("lic_key_variant", "Y").InstallGetMessage("ACT_KEY").'</label></td></tr>';
		}
		$this->content .= '
		</table>';
	}
}

class SelectWizardStep extends CWizardStep
{
	function InitStep()
	{
		$this->SetStepID("select_wizard");
		$this->SetNextStep("finish");
		$this->SetNextCaption(InstallGetMessage("NEXT_BUTTON"));
		$this->SetTitle(InstallGetMessage("INST_SELECT_WIZARD"));
	}

	function OnPostForm()
	{
		global $DB, $DBHost, $DBLogin, $DBPassword, $DBName, $DBDebug, $DBDebugToFile, $APPLICATION, $USER;

		require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include.php");

		$wizard = $this->GetWizard();
		$selectedWizard = $wizard->GetVar("selected_wizard");

		if ($selectedWizard == '')
		{
			$this->SetError(InstallGetMessage("INS_WRONG_WIZARD"));
			return null;
		}

		if ($selectedWizard == "@")
		{
			$wizard->SetCurrentStep("load_module");
			return true;
		}

		$arTmp = explode(":", $selectedWizard);
		$ar = array();
		foreach ($arTmp as $a)
		{
			$a = preg_replace("#[^a-z0-9_.-]+#i", "", $a);
			if ($a <> '')
				$ar[] = $a;
		}

		if (count($ar) > 2)
		{
			$path = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$ar[0]."/install/wizards/".$ar[1]."/".$ar[2];

			if (!file_exists($path) || !is_dir($path))
			{
				$this->SetError(InstallGetMessage("INS_WIZARD_NOT_FOUND"));
				return;
			}

			BXInstallServices::CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$ar[0]."/install/wizards/".$ar[1]."/".$ar[2],
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/wizards/".$ar[1]."/".$ar[2],
				true
			);

			$ar = array($ar[1], $ar[2]);
		}

		if (!file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/wizards/".$ar[0]."/".$ar[1])
			|| !is_dir($_SERVER["DOCUMENT_ROOT"]."/bitrix/wizards/".$ar[0]."/".$ar[1]))
		{
			$this->SetError(InstallGetMessage("INS_WIZARD_NOT_FOUND"));
			return;
		}

		if (BXInstallServices::CreateWizardIndex($ar[0].":".$ar[1], $errorMessageTmp))
		{
			$u = "/index.php";
			if (defined("WIZARD_DEFAULT_SITE_ID"))
			{
				$rsSite = CSite::GetList("sort", "asc", array("ID" => WIZARD_DEFAULT_SITE_ID));
				$arSite = $rsSite->GetNext();

				$u = "";
				if (is_array($arSite["DOMAINS"]) && $arSite["DOMAINS"][0] <> '' || $arSite["DOMAINS"] <> '')
					$u .= "http://";
				if (is_array($arSite["DOMAINS"]))
					$u .= $arSite["DOMAINS"][0];
				else
					$u .= $arSite["DOMAINS"];
				$u .= $arSite["DIR"];
			}
			else
			{
				BXInstallServices::DeleteDirRec($_SERVER["DOCUMENT_ROOT"]."/readme.php");
				BXInstallServices::DeleteDirRec($_SERVER["DOCUMENT_ROOT"]."/readme.html");
				BXInstallServices::DeleteDirRec($_SERVER["DOCUMENT_ROOT"]."/install.config");
				BXInstallServices::DeleteDirRec($_SERVER["DOCUMENT_ROOT"]."/restore.php");
				BXInstallServices::DeleteDirRec($_SERVER["DOCUMENT_ROOT"]."/bitrixsetup.php");
			}

			if (defined("BX_UTF"))
				BXInstallServices::EncodeFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/lang/".LANGUAGE_ID."/install.php", INSTALL_CHARSET);

			BXInstallServices::LocalRedirect($u);
		}
		else
		{
			$this->SetError($errorMessageTmp);
		}

		return true;
	}

	function ShowStep()
	{
		if (defined("BX_UTF"))
			define("INSTALL_UTF_PAGE", true);

		$wizard = $this->GetWizard();
		$prefixName = $wizard->GetRealName("selected_wizard");

		$arWizardsList = BXInstallServices::GetWizardsList();

		$this->content = '
		<script type="text/javascript">
			function SelectSolution(element, solutionId)
			{
				var hidden = document.getElementById("id_'.CUtil::JSEscape($prefixName).'");
				hidden.value = solutionId;
				document.getElementById("id_radio_"+solutionId).checked=true;
			}
		</script>
		';

		$arWizardsList[] = array(
				"ID" => "@",
				"IMAGE" => "/bitrix/images/install/marketplace.gif",
				"NAME" => InstallGetMessage("INS_LOAD_FROM_MARKETPLACE"),
				"DESCRIPTION" => InstallGetMessage("INS_LOAD_FROM_MARKETPLACE_DESCR"),
			);

		$this->content .= '<table class="inst-module-table" id="solutions-container">';
		$i = 0;
		foreach ($arWizardsList as $w)
		{
			if($i == 0)
				$this->content .= '<tr>';
			$this->content .= '
				<td class="inst-module-cell">
					<div class="inst-module-block" onclick="SelectSolution(this, \''.htmlspecialcharsbx($w["ID"]).'\');" ondblclick="document.forms[\''.htmlspecialcharsbx($wizard->GetFormName()).'\'].submit();">
							<div class="inst-module-title"><span class="inst-module-title-alignment"></span><span class="inst-module-title-text">'.$w["NAME"].'</span></div>
							<div class="inst-module-cont">
								'.($w["IMAGE"] <> '' ? '<div class="inst-module-img"><img alt="" src="'.htmlspecialcharsbx($w["IMAGE"]).'" /></div>' : "").'
								<div class="inst-module-text">'.$w["DESCRIPTION"].'</div>
							</div>
							<input type="radio" id="id_radio_'.htmlspecialcharsbx($w["ID"]).'" name="redio" class="inst-module-checkbox" />
					</div>
				</td>';

			if($i == 1)
			{
				$this->content .= '</tr>';
				$i = 0;
			}
			else
			{
				$i++;
			}
		}
		if($i == 0)
			$this->content .= '<td></td></tr>';
		$this->content .= '</table>
		<input type="hidden" id="id_'.htmlspecialcharsbx($prefixName).'" name="'.htmlspecialcharsbx($prefixName).'" value="">';
	}
}

class LoadModuleStep extends CWizardStep
{
	function InitStep()
	{
		$this->SetStepID("load_module");
		$this->SetNextStep("select_wizard1");
		$this->SetNextCaption(InstallGetMessage("NEXT_BUTTON"));
		$this->SetTitle(InstallGetMessage("INS_MODULE_LOADING"));
	}

	function OnPostForm()
	{
		global $DB, $DBHost, $DBLogin, $DBPassword, $DBName, $DBDebug, $DBDebugToFile, $APPLICATION, $USER;
		require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include.php");
		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client_partner.php");

		@set_time_limit(3600);

		$wizard = $this->GetWizard();
		$selectedModule = $wizard->GetVar("selected_module");
		$selectedModule = preg_replace("#[^a-z0-9._-]#i", "", $selectedModule);
		$coupon = $wizard->GetVar("coupon");
		$wizard->SetVar("MP_ACT_OK", "");

		if($coupon <> '')
		{
			if(CUpdateClientPartner::ActivateCoupon($coupon, $error))
			{
				$wizard->SetVar("MP_ACT_OK", "OK");
			}
			else
				$this->SetError(GetMessage("MP_COUPON_ACT_ERROR").": ".$error);

			$wizard->SetCurrentStep("load_module");
			return null;
		}
		
		if ($selectedModule == '')
		{
			$wizard->SetCurrentStep("select_wizard");
			return true;
		}

		//CUtil::InitJSCore(array('window'));
		$wizard->SetVar("nextStepStage", $selectedModule);
		$wizard->SetCurrentStep("load_module_action");

		return true;
	}

	function GetModuleObject($moduleID)
	{
		$installFile = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$moduleID."/install/index.php";
		if (!file_exists($installFile))
			return false;

		@include_once($installFile);
		$moduleIDTmp = str_replace(".", "_", $moduleID);
		if (!class_exists($moduleIDTmp))
			return false;

		return new $moduleIDTmp;
	}

	function ShowStep()
	{
		if (defined("BX_UTF"))
			define("INSTALL_UTF_PAGE", true);

		$wizard = $this->GetWizard();
		$prefixName = $wizard->GetRealName("selected_module");
		$couponName = $wizard->GetRealName("coupon");

		$arModulesList = array();

		require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client_partner.php");

		$arModules = CUpdateClientPartner::SearchModulesEx(
			array("SORT" => "DESC", "NAME" => "ASC"),
			array("CATEGORY" => Array(7, 14)),
			1,
			LANGUAGE_ID,
			$errorMessage
		);
		if (is_array($arModules["ERROR"]))
		{
			foreach ($arModules["ERROR"] as $e)
				$errorMessage .= (defined("BX_UTF") ? mb_convert_encoding($e["#"], INSTALL_CHARSET, "utf-8") : $e["#"]).". ";
		}
		if (defined("BX_UTF"))
			$errorMessage = mb_convert_encoding($errorMessage, INSTALL_CHARSET, "utf-8");

		if (is_array($arModules["MODULE"]))
		{
			foreach ($arModules["MODULE"] as $module)
			{
				$arModulesList[] = array(
					"ID" => $module["@"]["ID"],
					"NAME" => (defined("BX_UTF") ? mb_convert_encoding($module["@"]["NAME"], INSTALL_CHARSET, "utf-8") : $module["@"]["NAME"]),
					"DESCRIPTION" => (defined("BX_UTF") ? mb_convert_encoding($module["@"]["DESCRIPTION"], INSTALL_CHARSET, "utf-8") : $module["@"]["DESCRIPTION"]),
					"IMAGE" => $module["@"]["IMAGE"],
					"IMAGE_HEIGHT" => $module["@"]["IMAGE_HEIGHT"],
					"IMAGE_WIDTH" => $module["@"]["IMAGE_WIDTH"],
					"VERSION" => $module["@"]["VERSION"],
					"BUYED" => $module["@"]["BUYED"],
					"LINK" => "http://marketplace.1c-bitrix.ru/solutions/".$module["@"]["ID"]."/",
				);
			}
		}

		if ($errorMessage <> '')
			$this->SetError($errorMessage);

		$this->content .= '
		<script type="text/javascript">
			function SelectSolutionMP(element, solutionId)
			{
				var hidden = document.getElementById("id_'.CUtil::JSEscape($prefixName).'");
				hidden.value = solutionId;

				var container = document.getElementById("solutions-container");
				var anchors = container.getElementsByTagName("TD");
				for (var i = 0; i < anchors.length; i++)
				{
					anchors[i].className = "inst-module-cell";
				}

				element.parentNode.className = "inst-module-cell inst-module-cell-active";

				document.getElementById("id_radio_"+solutionId).checked=true;
			}
		</script>
		';


		$arCurrentModules = CUpdateClientPartner::GetCurrentModules($errorMessage);

		if(CUpdateClientPartner::GetLicenseKey() <> '' && !defined("DEMO"))
		{
			$actRes = $wizard->GetVar("MP_ACT_OK");
			if($actRes == "OK")
			{
				$this->content .= '<div class="inst-note-block inst-note-block-blue">
						<div class="inst-note-block-icon"></div>
						<div class="inst-note-block-text">'.GetMessage("MP_COUPON_ACTIVATION_OK").'</div>
					</div>';
			}
			$this->content .= '
				<div class="inst-discount-pass">
					<span class="inst-discount-label">'.GetMessage("MP_COUPON").':</span><input type="text" name="'.$couponName.'" value="" class="inst-discount-inp"><span class="instal-btn inst-btn-discount" onclick="document.forms[\''.htmlspecialcharsbx($wizard->GetFormName()).'\'].submit();">'.GetMessage("MP_ACTIVATE").'</span>
				</div>';
		}

		$arModulesList[] = array(
				"ID" => "",
				"IMAGE" => "",
				"NAME" => InstallGetMessage("INS_SKIP_MODULE_LOADING"),
				"DESCRIPTION" => InstallGetMessage("INS_SKIP_MODULE_LOADING_DESCR"),
			);

		$this->content .= '<table class="inst-module-table" id="solutions-container">';
		$i = 0;
		foreach ($arModulesList as $m)
		{
			if($i == 0)
				$this->content .= '<tr>';

			$bLoaded = array_key_exists($m["ID"], $arCurrentModules);
		
			$this->content .= '
				<td class="inst-module-cell">
					<div class="inst-module-block" onclick="'.'SelectSolutionMP(this, \''.htmlspecialcharsbx($m["ID"]).'\');" ondblclick="'.($bLoaded ? 'return false;' : 'document.forms[\''.htmlspecialcharsbx($wizard->GetFormName()).'\'].submit();').'">
							<div class="inst-module-title"><span class="inst-module-title-alignment"></span><span class="inst-module-title-text">'.TruncateText($m["NAME"], 59).'</span></div>
							<div class="inst-module-cont">
								'.($m["IMAGE"] <> '' ? '<div class="inst-module-img-mp"><img alt="" src="'.htmlspecialcharsbx($m["IMAGE"]).'" /></div>' : "").'
								<div class="inst-module-text-mp">'.
								($m["BUYED"] == "Y" ? '<b>'.InstallGetMessage("INS_MODULE_IS_BUYED").'</b><br />' : '').
								($bLoaded ? '<b>'.InstallGetMessage("INS_MODULE_IS_ALREADY_LOADED").'</b><br />' : '').
								TruncateText($m["DESCRIPTION"], 90).'</div>
							</div>'.
							($m["LINK"] <> '' ? '<div class="inst-module-footer"><a class="inst-module-more" href="'.$m["LINK"].'" target="_blank">'.GetMessage("MP_MORE").'</a></div>' : '').'
							<input type="radio" id="id_radio_'.htmlspecialcharsbx($m["ID"]).'" name="redio" class="inst-module-checkbox" />
					</div>
				</td>';

			if($i == 1)
			{
				$this->content .= '</tr>';
				$i = 0;
			}
			else
			{
				$i++;
			}
		}
		if($i == 0)
			$this->content .= '<td></td></tr>';

		$this->content .= '</table>
		<input type="hidden" id="id_'.htmlspecialcharsbx($prefixName).'" name="'.htmlspecialcharsbx($prefixName).'" value="">';
	}
}

class LoadModuleActionStep extends CWizardStep
{
	var $arSteps = Array();
	var $singleSteps = Array();

	function InitStep()
	{
		$this->SetStepID("load_module_action");
		$this->SetTitle(InstallGetMessage("INS_MODULE_LOADING1"));
	}

	function OnPostForm()
	{
		global $DB, $DBHost, $DBLogin, $DBPassword, $DBName, $DBDebug, $DBDebugToFile, $APPLICATION, $USER;
		require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include.php");

		@set_time_limit(3600);

		$wizard = $this->GetWizard();
		$currentStep = $wizard->GetVar("nextStep");
		$selectedModule = $wizard->GetVar("nextStepStage");
		$selectedModule = preg_replace("#[^a-z0-9_.-]+#i", "", $selectedModule);

		if ($selectedModule == "skip")
		{
			$wizard->SetCurrentStep("select_wizard");
			return;
		}

		$this->singleSteps = Array(
			"do_load_module" => InstallGetMessage("INS_MODULE_LOADING"),
			"do_update_module" => InstallGetMessage("INS_MODULE_LOADING"),
			"do_install_module" => InstallGetMessage("INS_MODULE_INSTALLING"),
			"do_load_wizard" => InstallGetMessage("INS_WIZARD_LOADING"),
		);

		$this->arSteps = array_keys($this->singleSteps);

		if (!in_array($currentStep, $this->arSteps))
		{
			if ($currentStep == "LocalRedirect")
			{
				$arWizardsList = BXInstallServices::GetWizardsList($selectedModule);

				$arTmp = explode(":", $arWizardsList[0]["ID"]);
				$ar = array();
				foreach ($arTmp as $a)
				{
					$a = preg_replace("#[^a-z0-9_.-]+#i", "", $a);
					if ($a <> '')
						$ar[] = $a;
				}

				$errorMessageTmp = "";
				if (BXInstallServices::CreateWizardIndex($ar[1].":".$ar[2], $errorMessageTmp))
				{
					$u = "/index.php";
					if (defined("WIZARD_DEFAULT_SITE_ID"))
					{
						$rsSite = CSite::GetList("sort", "asc", array("ID" => WIZARD_DEFAULT_SITE_ID));
						$arSite = $rsSite->GetNext();

						$u = "";
						if (is_array($arSite["DOMAINS"]) && $arSite["DOMAINS"][0] <> '' || $arSite["DOMAINS"] <> '')
							$u .= "http://";
						if (is_array($arSite["DOMAINS"]))
							$u .= $arSite["DOMAINS"][0];
						else
							$u .= $arSite["DOMAINS"];
						$u .= $arSite["DIR"];
					}
					else
					{
						BXInstallServices::DeleteDirRec($_SERVER["DOCUMENT_ROOT"]."/readme.php");
						BXInstallServices::DeleteDirRec($_SERVER["DOCUMENT_ROOT"]."/readme.html");
						BXInstallServices::DeleteDirRec($_SERVER["DOCUMENT_ROOT"]."/install.config");
						BXInstallServices::DeleteDirRec($_SERVER["DOCUMENT_ROOT"]."/restore.php");
						BXInstallServices::DeleteDirRec($_SERVER["DOCUMENT_ROOT"]."/bitrixsetup.php");
					}

					if (defined("BX_UTF"))
						BXInstallServices::EncodeFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/lang/".LANGUAGE_ID."/install.php", INSTALL_CHARSET);

					BXInstallServices::LocalRedirect($u);
					return;
				}
			}
			else
			{
				$wizard->SetCurrentStep($currentStep);
				return;
			}
		}

		$nextStep = "do_load_module";
		$percent = 0;
		$status = "";

		if ($currentStep == "do_load_module" || $currentStep == "do_update_module")
		{
			if (($currentStep == "do_update_module") || !file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$selectedModule))
			{
				require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/classes/general/update_client_partner.php");

//				$errorMessage = "";
//				if (!CUpdateClientPartner::LoadModuleNoDemand($selectedModule, $errorMessage, "Y", LANGUAGE_ID))
//					$this->SendResponse("window.onbeforeunload = null; window.ajaxForm.StopAjax(); window.ajaxForm.SetStatus('0', '".$errorMessage."'); window.ajaxForm.ShowError('".$errorMessage."');");

				$loadModuleResult = CUpdateClientPartner::loadModule4Wizard($selectedModule, LANGUAGE_ID);
				$loadModuleResultCode = substr($loadModuleResult, 0, 3);
				if ($loadModuleResultCode == "ERR")
				{
					$this->SendResponse("window.onbeforeunload = null; window.ajaxForm.StopAjax(); window.ajaxForm.SetStatus('0', '".substr($loadModuleResult, 3)."'); window.ajaxForm.ShowError('".substr($loadModuleResult, 3)."');");
				}
				elseif ($loadModuleResultCode == "STP")
				{
					$nextStep = "do_update_module";
					$status = $this->singleSteps["do_update_module"];
					$percent = 40;
				}
				else
				{
					$nextStep = "do_install_module";
					$status = $this->singleSteps["do_install_module"];
					$percent = 40;
				}
			}
			else
			{
				$nextStep = "do_install_module";
				$status = $this->singleSteps["do_install_module"];
				$percent = 40;
			}
		}
		elseif ($currentStep == "do_install_module")
		{
			if (!IsModuleInstalled($selectedModule))
			{
				$module = $this->GetModuleObject($selectedModule);
				if (!is_object($module))
					$this->SendResponse("window.onbeforeunload = null; window.ajaxForm.StopAjax(); window.ajaxForm.SetStatus('0', '".InstallGetMessage("INS_MODULE_CANNOT_BE_INSTALLED")."');window.ajaxForm.ShowError('".InstallGetMessage("INS_MODULE_CANNOT_BE_INSTALLED")."');");

				if (!$module->InstallDB())
				{
					if ($ex = $APPLICATION->GetException())
						$this->SendResponse("window.onbeforeunload = null; window.ajaxForm.StopAjax(); window.ajaxForm.SetStatus('0', '".$ex->GetString()."');");
					else
						$this->SendResponse("window.onbeforeunload = null; window.ajaxForm.StopAjax(); window.ajaxForm.SetStatus('0', '".InstallGetMessage("INS_MODULE_DATABASE_ERROR")."');");
				}

				$module->InstallEvents();

				if (!$module->InstallFiles())
				{
					if ($ex = $APPLICATION->GetException())
						$this->SendResponse("window.onbeforeunload = null; window.ajaxForm.StopAjax(); window.ajaxForm.SetStatus('0', '".$ex->GetString()."');");
					else
						$this->SendResponse("window.onbeforeunload = null; window.ajaxForm.StopAjax(); window.ajaxForm.SetStatus('0', '".InstallGetMessage("INS_MODULE_FILES_ERROR")."');");
				}
			}
			$nextStep = "do_load_wizard";
			$status = $this->singleSteps["do_load_wizard"];
			$percent = 80;
		}
		elseif ($currentStep == "do_load_wizard")
		{
			$arWizardsList = BXInstallServices::GetWizardsList($selectedModule);
			if (count($arWizardsList) == 1)
			{
				$arTmp = explode(":", $arWizardsList[0]["ID"]);
				$ar = array();
				foreach ($arTmp as $a)
				{
					$a = preg_replace("#[^a-z0-9_.-]+#i", "", $a);
					if ($a <> '')
						$ar[] = $a;
				}

				BXInstallServices::CopyDirFiles(
					$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$ar[0]."/install/wizards/".$ar[1]."/".$ar[2],
					$_SERVER["DOCUMENT_ROOT"]."/bitrix/wizards/".$ar[1]."/".$ar[2],
					true
				);

				$nextStep = "LocalRedirect";
			}
			elseif (count($arWizardsList) == 0)
			{
				$nextStep = "select_wizard";
			}
			else
			{
				$nextStep = "select_wizard1";
			}
			$percent = 100;
			$status = $this->singleSteps["do_load_wizard"];
		}

		$response = "";
		if (!in_array($nextStep, $this->arSteps))
			$response .= "window.onbeforeunload = null; window.ajaxForm.StopAjax();";
		$response .= "window.ajaxForm.SetStatus('".$percent."'); window.ajaxForm.Post('".$nextStep."', '".$selectedModule."','".$status."');";

		$this->SendResponse($response);
	}

	function SendResponse($response)
	{
		header("Content-Type: text/html; charset=".INSTALL_CHARSET);
		die("[response]".$response."[/response]");
	}

	function GetModuleObject($moduleID)
	{
		$installFile = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$moduleID."/install/index.php";
		if (!file_exists($installFile))
			return false;

		@include_once($installFile);
		$moduleIDTmp = str_replace(".", "_", $moduleID);
		if (!class_exists($moduleIDTmp))
			return false;

		return new $moduleIDTmp;
	}

	function ShowStep()
	{
		@include_once($_SERVER["DOCUMENT_ROOT"].BX_PERSONAL_ROOT."/php_interface/dbconn.php");

		if (defined("BX_UTF"))
			define("INSTALL_UTF_PAGE", true);

		$wizard = $this->GetWizard();
		$nextStepStage = $wizard->GetVar("nextStepStage");

		$this->content .= '
		<div class="instal-load-block" id="result">
			<div class="instal-load-label" id="status"></div>
			<div class="instal-progress-bar-outer" style="width: 670px;">
				<div class="instal-progress-bar-alignment">
					<div class="instal-progress-bar-inner" id="indicator">
						<div class="instal-progress-bar-inner-text" style="width: 670px;" id="percent"></div>
					</div>
					<span id="percent2">0%</span>
				</div>
			</div>
		</div>

		<div id="error_container" style="display:none">
			<div id="error_notice">
				<div class="inst-note-block inst-note-block-red">
					<div class="inst-note-block-icon"></div>
					<div class="inst-note-block-label">'.InstallGetMessage("INST_ERROR_OCCURED").'</div><br style="clear:both" />
					<div class="inst-note-block-text">'.InstallGetMessage("INST_ERROR_NOTICE").'<div id="error_text"></div></div>
				</div>
			</div>

			<div id="error_buttons" align="center">
			<br /><input type="button" value="'.InstallGetMessage("INST_RETRY_BUTTON").'" id="error_retry_button" style="display:none" onclick="" class="instal-btn instal-btn-inp" />&nbsp;<input type="button" id="error_skip_button" value="'.InstallGetMessage("INST_SKIP_BUTTON").'" onclick="" class="instal-btn instal-btn-inp" />&nbsp;</div>
		</div>

		'.$this->ShowHiddenField("nextStep", "do_load_module").'
		'.$this->ShowHiddenField("nextStepStage", $nextStepStage).'
		<iframe style="display:none;" id="iframe-post-form" name="iframe-post-form" src="javascript:\'\'"></iframe>
		';

		$wizard = $this->GetWizard();

		$formName = $wizard->GetFormName();
		$NextStepVarName = $wizard->GetRealName("nextStep");

		$this->content .= '
			<script type="text/javascript">
				var ajaxForm = new CAjaxForm("'.$formName.'", "iframe-post-form", "'.$NextStepVarName.'");
				ajaxForm.Post("do_load_module", "'.$nextStepStage.'", "'.InstallGetMessage("INS_MODULE_LOADING").'");
			</script>
		';
	}

}

class SelectWizard1Step extends SelectWizardStep
{
	function InitStep()
	{
		$this->SetStepID("select_wizard1");
		$this->SetNextStep("finish");
		$this->SetNextCaption(InstallGetMessage("NEXT_BUTTON"));
		$this->SetTitle(InstallGetMessage("INST_SELECT_WIZARD1"));
	}

	function OnPostForm()
	{
		global $DB, $DBHost, $DBLogin, $DBPassword, $DBName, $DBDebug, $DBDebugToFile, $APPLICATION, $USER;
		require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include.php");

		$wizard = $this->GetWizard();
		$selectedWizard = $wizard->GetVar("selected_wizard");

		if ($selectedWizard == '')
		{
			$this->SetError(InstallGetMessage("INS_WRONG_WIZARD"));
			return null;
		}

		if ($selectedWizard == "@")
		{
			$wizard->SetCurrentStep("load_module");
			return true;
		}

		$arTmp = explode(":", $selectedWizard);
		$ar = array();
		foreach ($arTmp as $a)
		{
			$a = preg_replace("#[^a-z0-9_.-]+#i", "", $a);
			if ($a <> '')
				$ar[] = $a;
		}

		if (count($ar) > 2)
		{
			$path = $_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$ar[0]."/install/wizards/".$ar[1]."/".$ar[2];

			if (!file_exists($path) || !is_dir($path))
			{
				$this->SetError(InstallGetMessage("INS_WIZARD_NOT_FOUND"));
				return;
			}

			BXInstallServices::CopyDirFiles(
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/".$ar[0]."/install/wizards/".$ar[1]."/".$ar[2],
				$_SERVER["DOCUMENT_ROOT"]."/bitrix/wizards/".$ar[1]."/".$ar[2],
				true
			);

			$ar = array($ar[1], $ar[2]);
		}

		if (!file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/wizards/".$ar[0]."/".$ar[1])
			|| !is_dir($_SERVER["DOCUMENT_ROOT"]."/bitrix/wizards/".$ar[0]."/".$ar[1]))
		{
			$this->SetError(InstallGetMessage("INS_WIZARD_NOT_FOUND"));
			return;
		}

		if (BXInstallServices::CreateWizardIndex($ar[0].":".$ar[1], $errorMessageTmp))
		{
			$u = "/index.php";
			if (defined("WIZARD_DEFAULT_SITE_ID"))
			{
				$rsSite = CSite::GetList("sort", "asc", array("ID" => WIZARD_DEFAULT_SITE_ID));
				$arSite = $rsSite->GetNext();

				$u = "";
				if (is_array($arSite["DOMAINS"]) && $arSite["DOMAINS"][0] <> '' || $arSite["DOMAINS"] <> '')
					$u .= "http://";
				if (is_array($arSite["DOMAINS"]))
					$u .= $arSite["DOMAINS"][0];
				else
					$u .= $arSite["DOMAINS"];
				$u .= $arSite["DIR"];
			}
			else
			{
				BXInstallServices::DeleteDirRec($_SERVER["DOCUMENT_ROOT"]."/readme.php");
				BXInstallServices::DeleteDirRec($_SERVER["DOCUMENT_ROOT"]."/readme.html");
				BXInstallServices::DeleteDirRec($_SERVER["DOCUMENT_ROOT"]."/install.config");
				BXInstallServices::DeleteDirRec($_SERVER["DOCUMENT_ROOT"]."/restore.php");
				BXInstallServices::DeleteDirRec($_SERVER["DOCUMENT_ROOT"]."/bitrixsetup.php");
			}

			if (defined("BX_UTF"))
				BXInstallServices::EncodeFile($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/lang/".LANGUAGE_ID."/install.php", INSTALL_CHARSET);

			BXInstallServices::LocalRedirect($u);
		}
		else
		{
			$this->SetError($errorMessageTmp);
		}

		return true;
	}

	function ShowStep()
	{
		if (defined("BX_UTF"))
			define("INSTALL_UTF_PAGE", true);

		$wizard = $this->GetWizard();
		$prefixName = $wizard->GetRealName("selected_wizard");

		$selectedModule = $wizard->GetVar("selected_module");

		$arWizardsList = BXInstallServices::GetWizardsList($selectedModule);

		$this->content = '
		<script type="text/javascript">
			function SelectSolution(element, solutionId)
			{
				var hidden = document.getElementById("id_'.CUtil::JSEscape($prefixName).'");
				hidden.value = solutionId;

				var container = document.getElementById("solutions-container");
				var anchors = container.getElementsByTagName("TD");
				for (var i = 0; i < anchors.length; i++)
				{
					anchors[i].className = "inst-module-cell";
				}

				element.parentNode.className = "inst-module-cell inst-module-cell-active";
				
				document.getElementById("id_radio_"+solutionId).checked=true;
			}
		</script>
		';

		$arWizardsList[] = array(
				"ID" => "@",
				"IMAGE" => "/bitrix/images/install/marketplace.gif",
				"NAME" => InstallGetMessage("INS_LOAD_FROM_MARKETPLACE"),
				"DESCRIPTION" => InstallGetMessage("INS_LOAD_FROM_MARKETPLACE_DESCR"),
			);

		$this->content .= '<table class="inst-module-table" id="solutions-container">';
		$i = 0;
		foreach ($arWizardsList as $w)
		{
			if($i == 0)
				$this->content .= '<tr>';
			$this->content .= '
				<td class="inst-module-cell">
					<div class="inst-module-block" onclick="SelectSolution(this, \''.htmlspecialcharsbx($w["ID"]).'\');" ondblclick="document.forms[\''.htmlspecialcharsbx($wizard->GetFormName()).'\'].submit();">
							<div class="inst-module-title"><span class="inst-module-title-alignment"></span><span class="inst-module-title-text">'.TruncateText($w["NAME"], 59).'</span></div>
							<div class="inst-module-cont">
								'.($w["IMAGE"] <> '' ? '<div class="inst-module-img"><img alt="" src="'.htmlspecialcharsbx($w["IMAGE"]).'" /></div>' : "").'
								<div class="inst-module-text">'.TruncateText($w["DESCRIPTION"], 90).'</div>
							</div>
							<input type="radio" id="id_radio_'.htmlspecialcharsbx($w["ID"]).'" name="redio" class="inst-module-checkbox" />
					</div>
				</td>';

			if($i == 1)
			{
				$this->content .= '</tr>';
				$i = 0;
			}
			else
			{
				$i++;
			}
		}
		if($i == 0)
			$this->content .= '<td></td></tr>';
		$this->content .= '</table>
		<input type="hidden" id="id_'.htmlspecialcharsbx($prefixName).'" name="'.htmlspecialcharsbx($prefixName).'" value="">';
	}
}

class FinishStep extends CWizardStep
{
	function InitStep()
	{
		$this->SetStepID("finish");
		$this->SetTitle(InstallGetMessage("INS_STEP7_TITLE"));
	}

	function CreateNewIndex()
	{
		$handler = @fopen($_SERVER["DOCUMENT_ROOT"]."/index.php","wb");

		if (!$handler)
		{
			$this->SetError(InstallGetMessage("INST_INDEX_ACCESS_ERROR"));
			return;
		}

		$success = @fwrite($handler,
			'<'.'?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/header.php");?'.'>'."\n".
			'<br /><a href="/bitrix/admin/">Control panel</a>'."\n".
			'<'.'?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/footer.php");?'.'>'
		);

		if (!$success)
		{
			$this->SetError(InstallGetMessage("INST_INDEX_ACCESS_ERROR"));
			return;
		}

		if (defined("BX_FILE_PERMISSIONS"))
			@chmod($_SERVER["DOCUMENT_ROOT"]."/index.php", BX_FILE_PERMISSIONS);

		fclose($handler);
	}

	function ShowStep()
	{
		$this->CreateNewIndex();

		BXInstallServices::DeleteDirRec($_SERVER["DOCUMENT_ROOT"]."/install.config");

		$this->content = '
		<p>'.InstallGetMessage("GRETTINGS").'</p>

		<b><a href="/bitrix/admin/sysupdate.php?lang='.LANGUAGE_ID.'">'.InstallGetMessage("GO_TO_REGISTER").'</a></b><br>'.InstallGetMessage("GO_TO_REGISTER_DESCR").'<br><br>

		<table width="100%" cellpadding="2" cellspacing="0" border="0">
			<tr>
				<td><a href="/bitrix/admin/index.php?lang='.LANGUAGE_ID.'"><img src="/bitrix/images/install/admin.gif" width="22" height="22" border="0" title="'.InstallGetMessage("GO_TO_CONTROL").'"></a></td>
				<td width="50%">&nbsp;<a href="/bitrix/admin/index.php?lang='.LANGUAGE_ID.'">'.InstallGetMessage("GO_TO_CONTROL").'</a></td>
				<td><a href="/"><img border="0" src="/bitrix/images/install/public.gif" width="22" height="22" title="'.InstallGetMessage("GO_TO_VIEW").'"></a></td>
				<td width="50%">&nbsp;<a href="/">'.InstallGetMessage("GO_TO_VIEW").'</a></td>
			</tr>
		</table>';
	}
}

class CheckLicenseKey extends CWizardStep
{
	function InitStep()
	{
		$this->SetStepID("check_license_key");
		$this->SetNextStep("create_modules");
		$this->SetNextCaption(InstallGetMessage("NEXT_BUTTON"));
		$this->SetTitle(InstallGetMessage("INS_LICENSE_HEAD"));

		$wizard = $this->GetWizard();
		if (defined("TRIAL_VERSION") || defined("TRIAL_RENT_VERSION"))
		{
			$wizard->SetDefaultVar("lic_key_variant", "Y");
		}

		if(file_exists($_SERVER['DOCUMENT_ROOT'].'/bitrix/license_key.php'))
		{
			$LICENSE_KEY = '';
			include($_SERVER['DOCUMENT_ROOT'].'/bitrix/license_key.php');
			$wizard->SetDefaultVar("license", $LICENSE_KEY);
		}
	}

	function OnPostForm()
	{
		$wizard = $this->GetWizard();
		$licenseKey = $wizard->GetVar("license");

		if (!defined("TRIAL_VERSION") && !defined("TRIAL_RENT_VERSION") && function_exists("preg_match") && !preg_match('/[A-Z0-9]{3}-[A-Z]{2}-?[A-Z0-9]{12,30}/i', $licenseKey))
		{
			$this->SetError(InstallGetMessage("BAD_LICENSE_KEY"), "license");
			return;
		}

		if(defined("TRIAL_VERSION") || defined("TRIAL_RENT_VERSION"))
		{
			$lic_key_variant = $wizard->GetVar("lic_key_variant");

			if((defined("TRIAL_RENT_VERSION") || (defined("TRIAL_VERSION") && $lic_key_variant == "Y")) && $licenseKey == '')
			{
				$lic_key_user_surname = $wizard->GetVar("user_surname");
				$lic_key_user_name = $wizard->GetVar("user_name");
				$lic_key_email = $wizard->GetVar("email");

				$bError = false;
				if(trim($lic_key_user_name) == '')
				{
					$this->SetError(InstallGetMessage("ACT_KEY_BAD_NAME"), "user_name");
					$bError = true;
				}
				if(trim($lic_key_user_surname) == '')
				{
					$this->SetError(InstallGetMessage("ACT_KEY_BAD_LAST_NAME"), "user_surname");
					$bError = true;
				}
				if(trim($lic_key_email) == '' || !check_email($lic_key_email))
				{
					$this->SetError(InstallGetMessage("ACT_KEY_BAD_EMAIL"), "email");
					$bError = true;
				}

				if(!$bError)
				{
					$key = BXInstallServices::GetRegistrationKey($lic_key_user_name, $lic_key_user_surname, $lic_key_email, 'mysql');

					if($key !== false)
					{
						$wizard->SetVar("license", $key);
					}
					elseif(defined("TRIAL_RENT_VERSION"))
					{
						$this->SetError(InstallGetMessage("ACT_KEY_REQUEST_ERROR"), "email");
					}
				}
			}
		}

		$this->CreateLicenseFile();
	}

	function CreateLicenseFile()
	{
		$wizard = $this->GetWizard();
		$licenseKey = $wizard->GetVar("license");

		return BXInstallServices::CreateLicenseFile($licenseKey);
	}

	function ShowStep()
	{

		$this->content = '
		<table border="0" class="data-table">
			<tr>
				<td colspan="2" class="header">'.InstallGetMessage("INS_LICENSE_HEAD").'</td>
			</tr>';

		if(!defined("TRIAL_VERSION") && !defined("TRIAL_RENT_VERSION"))
		{
			$this->content .= '<tr>
				<td nowrap align="right" width="40%" valign="top">
					<span style="color:red">*</span>&nbsp;'.InstallGetMessage("INS_LICENSE").'
				</td>
				<td width="60%" valign="top">'.$this->ShowInputField("text", "license", Array("size" => "30", "tabindex" => "1", "id" =>"license_id")).'
					<br>
					<small>'.InstallGetMessage("INS_LICENSE_NOTE_SOURCE").'<br></small>
				</td>
				</tr>
				<tr>
				<td nowrap align="right" width="40%" valign="top">
					'.InstallGetMessage("INSTALL_DEVSRV").'
				</td>
				<td width="60%" valign="top">'.$this->ShowCheckboxField("devsrv", "Y", Array("id" => "devsrv_inst")).'
					<br>
					<small>'.InstallGetMessage("INSTALL_DEVSRV_NOTE").'<br></small>
				</td>
				</tr>
				</table>
				';
		}
		else
		{
			$this->content .= '
			<script>
				function changeLicKey(val)
				{
					if(val)
					{
						document.getElementById("lic_key_activation").style.display = "block";
					}
					else
					{
						document.getElementById("lic_key_activation").style.display = "none";
					}
				}
			</script>

					';
			if(!defined("TRIAL_RENT_VERSION"))
				$this->content .= '<tr><td colspan="2">'.$this->ShowCheckboxField("lic_key_variant", "Y", Array("id" => "lic_key_variant", "onclick" => "javascript:changeLicKey(this.checked)")).'<label for="lic_key_variant">'.InstallGetMessage("ACT_KEY").'</label></td></tr>';

			$wizard = $this->GetWizard();
			$lic_key_variant = $wizard->GetVar("lic_key_variant", $useDefault = true);
			$this->content .= '
			</table>
			<div id="lic_key_activation">
			<table border="0" class="data-table" style="border-top:none;">
			<tr>
				<td align="right" width="40%" style="border-top:none;"><span style="color:red">*</span>&nbsp;'.InstallGetMessage("ACT_KEY_NAME").':</td>
				<td width="60%" valign="top" style="border-top:none;">'.$this->ShowInputField("text", "user_name", Array("size" => "30", "tabindex" => "4", "id" => "user_name")).'</td>
			</tr>
			<tr>
				<td align="right" width="40%" style="border-top:none;"><span style="color:red">*</span>&nbsp;'.InstallGetMessage("ACT_KEY_LAST_NAME").':</td>
				<td width="60%" valign="top" style="border-top:none;">'.$this->ShowInputField("text", "user_surname", Array("size" => "30", "tabindex" => "5", "id" => "user_surname")).'</td>
			</tr>
			<tr>
				<td align="right" width="40%" style="border-top:none;"><span style="color:red">*</span>&nbsp;Email:</td>
				<td width="60%" valign="top" style="border-top:none;">'.$this->ShowInputField("text", "email", Array("size" => "30", "tabindex" => "6", "id" => "email")).'</td>
			</tr>
			</table>
			</div>
			<script>
			changeLicKey('.(($lic_key_variant == "Y") ? 'true' : 'false').');
			</script>
			';
		}
		//$this->content .= '</table>';
	}
}

//Create wizard
$wizard = new CWizardBase(str_replace("#VERS#", SM_VERSION, InstallGetMessage("INS_TITLE")), $package = null);

if (defined("WIZARD_DEFAULT_TONLY") && WIZARD_DEFAULT_TONLY === true)
{
	global $USER;
	require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include.php");
	if($USER->CanDoOperation('edit_other_settings'))
	{
		$arSteps = Array("SelectWizardStep", "LoadModuleStep", "LoadModuleActionStep", "SelectWizard1Step");
	}
	else
	{
		die();
	}
}
//Short installation
elseif (BXInstallServices::IsShortInstall())
{
	$arSteps = Array();
	if (defined("VM_INSTALL"))
	{
		$arSteps = Array("AgreementStep4VM");
	}

	if($_SERVER['BITRIX_ENV_TYPE'] <> "crm")
	{
		$arSteps[] = "CheckLicenseKey";
		$arSteps[] = "CreateModulesStep";
		$arSteps[] = "CreateAdminStep";
		$arSteps[] = "SelectWizardStep";
		$arSteps[] = "LoadModuleStep";
		$arSteps[] = "LoadModuleActionStep";
		$arSteps[] = "SelectWizard1Step";

		if (BXInstallServices::GetDemoWizard() === false)
		{
			$arSteps[] = "FinishStep";
		}
	}
	else
	{
		$arSteps[] = "CreateModulesStep";
		$arSteps[] = "CreateAdminStep";
	}
}
else
{
	//Full installation
	$arSteps = Array("WelcomeStep", "AgreementStep", "DBTypeStep", "RequirementStep", "CreateDBStep", "CreateModulesStep", "CreateAdminStep");

	$arWizardsList = BXInstallServices::GetWizardsList();
	if (count($arWizardsList) > 0)
	{
		$arSteps[] = "SelectWizardStep";
		$arSteps[] = "LoadModuleStep";
		$arSteps[] = "LoadModuleActionStep";
		$arSteps[] = "SelectWizard1Step";
	}

	if (BXInstallServices::GetDemoWizard() === false)
		$arSteps[] = "FinishStep";
}

$wizard->AddSteps($arSteps); //Add steps
$wizard->SetTemplate(new WizardTemplate);
$wizard->SetReturnOutput();
$content = $wizard->Display();

if (defined("INSTALL_UTF_PAGE"))
{
	$pageCharset = "UTF-8";
	$content = mb_convert_encoding($content, "utf-8", INSTALL_CHARSET);
}
else
	$pageCharset = INSTALL_CHARSET;

header("Content-Type: text/html; charset=".$pageCharset);
echo $content;