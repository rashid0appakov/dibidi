<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2013 Bitrix
 */

use Bitrix\Main\Session\Legacy\HealerEarlySessionStart;

require_once(__DIR__."/bx_root.php");
require_once(__DIR__."/start.php");

$application = \Bitrix\Main\Application::getInstance();
$application->initializeExtendedKernel(array(
	"get" => $_GET,
	"post" => $_POST,
	"files" => $_FILES,
	"cookie" => $_COOKIE,
	"server" => $_SERVER,
	"env" => $_ENV
));

//define global application object
$GLOBALS["APPLICATION"] = new CMain;

if(defined("SITE_ID"))
	define("LANG", SITE_ID);

if(defined("LANG"))
{
	if(defined("ADMIN_SECTION") && ADMIN_SECTION===true)
		$db_lang = CLangAdmin::GetByID(LANG);
	else
		$db_lang = CLang::GetByID(LANG);

	$arLang = $db_lang->Fetch();

	if(!$arLang)
	{
		throw new \Bitrix\Main\SystemException("Incorrect site: ".LANG.".");
	}
}
else
{
	$arLang = $GLOBALS["APPLICATION"]->GetLang();
	define("LANG", $arLang["LID"]);
}

if($arLang["CULTURE_ID"] == '')
{
	throw new \Bitrix\Main\SystemException("Culture not found, or there are no active sites or languages.");
}

$lang = $arLang["LID"];
if (!defined("SITE_ID"))
	define("SITE_ID", $arLang["LID"]);
define("SITE_DIR", ($arLang["DIR"] ?? ''));
define("SITE_SERVER_NAME", ($arLang["SERVER_NAME"] ?? ''));
define("SITE_CHARSET", $arLang["CHARSET"]);
define("FORMAT_DATE", $arLang["FORMAT_DATE"]);
define("FORMAT_DATETIME", $arLang["FORMAT_DATETIME"]);
define("LANG_DIR", ($arLang["DIR"] ?? ''));
define("LANG_CHARSET", $arLang["CHARSET"]);
define("LANG_ADMIN_LID", $arLang["LANGUAGE_ID"]);
define("LANGUAGE_ID", $arLang["LANGUAGE_ID"]);

$culture = \Bitrix\Main\Localization\CultureTable::getByPrimary($arLang["CULTURE_ID"], ["cache" => ["ttl" => CACHED_b_lang]])->fetchObject();

$context = $application->getContext();
$context->setLanguage(LANGUAGE_ID);
$context->setCulture($culture);

$request = $context->getRequest();
if (!$request->isAdminSection())
{
	$context->setSite(SITE_ID);
}

$application->start();

$GLOBALS["APPLICATION"]->reinitPath();

if (!defined("POST_FORM_ACTION_URI"))
{
	define("POST_FORM_ACTION_URI", htmlspecialcharsbx(GetRequestUri()));
}

$GLOBALS["MESS"] = [];
$GLOBALS["ALL_LANG_FILES"] = [];
IncludeModuleLangFile(__DIR__."/tools.php");
IncludeModuleLangFile(__FILE__);

error_reporting(COption::GetOptionInt("main", "error_reporting", E_COMPILE_ERROR|E_ERROR|E_CORE_ERROR|E_PARSE) & ~E_STRICT & ~E_DEPRECATED);

if(!defined("BX_COMP_MANAGED_CACHE") && COption::GetOptionString("main", "component_managed_cache_on", "Y") <> "N")
{
	define("BX_COMP_MANAGED_CACHE", true);
}

// global functions
require_once(__DIR__."/filter_tools.php");

define('BX_AJAX_PARAM_ID', 'bxajaxid');

/*ZDUyZmZNGIzMTRmY2M3ODkwMGVhMjkwZTVkMDI1YWUzNGUwZWY=*/$GLOBALS['_____363473648']= array(base64_decode('R2V'.'0T'.'W9k'.'dWxl'.'RX'.'ZlbnR'.'z'),base64_decode(''.'RXhlY3V0'.'ZU1'.'vZHVs'.'ZUV'.'2ZW50RXg='),base64_decode('V3J'.'pd'.'GVG'.'aW5hbE1lc3N'.'hZ2U='));$GLOBALS['____900957568']= array(base64_decode(''.'ZGV'.'m'.'a'.'W5l'),base64_decode(''.'c3RybGVu'),base64_decode(''.'YmF'.'zZTY0X'.'2Rl'.'Y29kZQ=='),base64_decode(''.'d'.'W'.'5zZX'.'J'.'pYWxp'.'e'.'m'.'U='),base64_decode(''.'aXNfYXJ'.'yY'.'X'.'k'.'='),base64_decode('Y291bnQ'.'='),base64_decode('aW5fYXJyYXk='),base64_decode('c'.'2V'.'y'.'aW'.'Fs'.'aXpl'),base64_decode('Y'.'mFzZTY0X2VuY29kZQ=='),base64_decode('c3RybGVu'),base64_decode('YX'.'JyYXlfa2V5'.'X2'.'V4aXN0cw='.'='),base64_decode('YXJyYXlfa2V5X2V'.'4aXN0'.'cw'.'=='),base64_decode('bWt0aW1l'),base64_decode('ZGF0ZQ'.'='.'='),base64_decode('ZGF0'.'ZQ=='),base64_decode(''.'YX'.'Jy'.'YXlfa2V5X2V'.'4'.'aXN0'.'cw'.'=='),base64_decode('c3Ry'.'bGVu'),base64_decode(''.'Y'.'XJ'.'yYXl'.'fa'.'2V5'.'X2'.'V4'.'a'.'XN0cw=='),base64_decode('c3R'.'ybGVu'),base64_decode('YXJyYXlfa2'.'V5X'.'2V4'.'aXN0c'.'w='.'='),base64_decode('YXJ'.'y'.'YXlfa2V5X2V4aXN'.'0cw='.'='),base64_decode(''.'b'.'W'.'t0a'.'W'.'1'.'l'),base64_decode(''.'ZGF0ZQ'.'=='),base64_decode('ZGF0Z'.'Q'.'=='),base64_decode('bWV0a'.'G9kX'.'2V4aXN0cw=='),base64_decode('Y2Fsb'.'F9'.'1'.'c'.'2VyX2Z1bmN'.'fYXJyYXk='),base64_decode('c3RybGVu'),base64_decode('YXJyYXlfa2V5X2'.'V4'.'aXN0c'.'w'.'=='),base64_decode('Y'.'XJ'.'y'.'Y'.'Xlfa2V5X2V4'.'aX'.'N0cw=='),base64_decode('c2VyaWFsaXpl'),base64_decode('YmFzZTY0'.'X2'.'VuY2'.'9kZQ=='),base64_decode('c3Ryb'.'GVu'),base64_decode('Y'.'XJy'.'YX'.'lf'.'a2'.'V5X2V4'.'a'.'XN'.'0cw=='),base64_decode(''.'YX'.'JyYX'.'lfa2'.'V5X2'.'V4aX'.'N0cw='.'='),base64_decode('YX'.'JyYXlfa2V5X'.'2V'.'4aX'.'N0cw'.'=='),base64_decode('aX'.'NfYXJyY'.'Xk'.'='),base64_decode('YX'.'JyYX'.'l'.'fa2V5X2V4aXN0cw=='),base64_decode('c2VyaWF'.'s'.'a'.'X'.'pl'),base64_decode('YmFzZTY0X2VuY29kZQ=='),base64_decode(''.'YXJyYXlfa'.'2V5X2V4aXN0cw='.'='),base64_decode('YXJy'.'Y'.'Xlfa2V5X'.'2'.'V4'.'aXN0cw=='),base64_decode('c'.'2'.'VyaWFsa'.'X'.'pl'),base64_decode(''.'Ym'.'F'.'zZ'.'TY0X2VuY29kZQ=='),base64_decode('a'.'X'.'NfY'.'X'.'JyYXk='),base64_decode('aXNfY'.'XJ'.'yY'.'Xk='),base64_decode('aW5fYXJyY'.'Xk='),base64_decode('YXJ'.'yYXlfa'.'2V5X'.'2V4aXN0cw=='),base64_decode(''.'aW5fYXJyY'.'Xk='),base64_decode('bWt0aW1l'),base64_decode('ZGF'.'0'.'ZQ=='),base64_decode('ZG'.'F0ZQ=='),base64_decode('Z'.'GF0Z'.'Q='.'='),base64_decode('bWt'.'0aW1l'),base64_decode('ZGF0ZQ'.'=='),base64_decode('Z'.'GF'.'0ZQ=='),base64_decode('aW5f'.'YXJ'.'y'.'YXk='),base64_decode('YXJyYX'.'lfa2'.'V5X2V4aXN'.'0cw='.'='),base64_decode('Y'.'X'.'J'.'yYX'.'lfa2V5X2V4aX'.'N0cw=='),base64_decode('c2V'.'yaWF'.'saXpl'),base64_decode('YmF'.'zZTY0'.'X2V'.'uY29kZQ'.'=='),base64_decode('YXJy'.'YX'.'lfa2V5X2'.'V4aXN0'.'cw=='),base64_decode('aW50dmF'.'s'),base64_decode('dGl'.'tZQ=='),base64_decode('YXJyYXlfa'.'2V5X'.'2V'.'4aXN0cw=='),base64_decode('ZmlsZ'.'V9le'.'Gl'.'zdHM='),base64_decode('c3R'.'y'.'X3'.'J'.'lcGxhY'.'2'.'U='),base64_decode(''.'Y2xh'.'c3NfZ'.'Xhp'.'c3R'.'z'),base64_decode('ZGV'.'maW5'.'l'),base64_decode(''.'c3R'.'ycmV2'),base64_decode('c'.'3RydG'.'91'.'cHBlcg'.'=='),base64_decode('c3ByaW'.'50Zg=='),base64_decode('c3By'.'aW50Zg=='),base64_decode('c3Vic3Ry'),base64_decode('c3RycmV2'),base64_decode('YmF'.'zZTY0'.'X2'.'RlY29kZQ='.'='),base64_decode('c3Vic3Ry'),base64_decode('c'.'3R'.'ybG'.'Vu'),base64_decode('c3RybG'.'V'.'u'),base64_decode('Y'.'2'.'hy'),base64_decode('b3J'.'k'),base64_decode('b3Jk'),base64_decode('bWt'.'0aW1'.'l'),base64_decode(''.'aW50'.'d'.'mFs'),base64_decode('aW5'.'0'.'d'.'m'.'F'.'s'),base64_decode('aW5'.'0dmFs'),base64_decode('a3N'.'vcnQ='),base64_decode('c3Vi'.'c3Ry'),base64_decode('a'.'W1'.'wbG9kZQ=='),base64_decode('ZGVm'.'aW'.'5'.'lZA=='),base64_decode('YmFz'.'ZTY0'.'X2RlY'.'29k'.'Z'.'Q=='),base64_decode('Y29uc'.'3RhbnQ='),base64_decode('c3RycmV2'),base64_decode(''.'c'.'3B'.'y'.'aW50Z'.'g'.'=='),base64_decode('c3RybGVu'),base64_decode('c'.'3RybGVu'),base64_decode('Y2hy'),base64_decode('b3Jk'),base64_decode('b3Jk'),base64_decode(''.'bW'.'t'.'0aW1l'),base64_decode('aW5'.'0dmFs'),base64_decode('aW5'.'0dmFs'),base64_decode(''.'aW50dmF'.'s'),base64_decode('c'.'3V'.'i'.'c3Ry'),base64_decode('c3Vic3'.'Ry'),base64_decode('ZGVmaW5'.'l'.'ZA'.'=='),base64_decode('c3Ry'.'cmV2'),base64_decode('c3Ry'.'dG'.'91c'.'H'.'Bl'.'cg=='),base64_decode(''.'Zm'.'lsZV9'.'leGlzdHM='),base64_decode('aW50dmFs'),base64_decode('dG'.'lt'.'ZQ=='),base64_decode('bWt0aW1l'),base64_decode('b'.'Wt'.'0aW1l'),base64_decode('Z'.'GF0ZQ=='),base64_decode('ZGF'.'0ZQ=='),base64_decode('ZG'.'VmaW'.'5l'),base64_decode('ZGVma'.'W5l'));if(!function_exists(__NAMESPACE__.'\\___390550584')){function ___390550584($_612473165){static $_1396145656= false; if($_1396145656 == false) $_1396145656=array('SU5UU'.'kFORV'.'RfR'.'URJVElPTg='.'=','WQ='.'=','bW'.'Fpbg==','fmNwZ'.'l9tYXBfdmFs'.'dWU'.'=','','ZQ==',''.'Z'.'g==','ZQ==','Rg='.'=','WA==','Zg==',''.'b'.'WF'.'pbg==',''.'fm'.'NwZl9'.'t'.'Y'.'X'.'BfdmFs'.'d'.'W'.'U'.'=','U'.'G9ydGF'.'s','R'.'g='.'=','ZQ==','ZQ='.'=','WA==','Rg='.'=','RA==','RA==','b'.'Q==','ZA'.'==','WQ==','Zg==','Zg==','Z'.'g==','Zg==','UG9ydGF'.'s','Rg==','ZQ'.'==','ZQ==','WA='.'=','Rg==','RA==','RA==','bQ==','ZA='.'=',''.'WQ==','bWFp'.'bg='.'=','T24=','U2V0dG'.'lu'.'Z3NDaG'.'FuZ2U=','Zg='.'=','Zg'.'==','Zg'.'='.'=','Z'.'g==','bWFpb'.'g==','f'.'mNwZl9tYX'.'Bf'.'dmFsdW'.'U'.'=',''.'Z'.'Q'.'==','ZQ'.'==','Z'.'Q='.'=','RA==',''.'ZQ==','ZQ==',''.'Z'.'g==',''.'Zg'.'='.'=',''.'Z'.'g'.'==',''.'ZQ='.'=','bWFp'.'bg==',''.'fmNwZl'.'9'.'t'.'YXB'.'fd'.'mFsdWU=','ZQ='.'=','Zg==','Z'.'g==','Z'.'g==',''.'Zg='.'=',''.'bWFp'.'bg==','fmN'.'wZl9'.'t'.'YXBf'.'dmFsdW'.'U'.'=','ZQ==','Z'.'g==','UG9ydGFs','UG9ydG'.'Fs','ZQ==','ZQ'.'==','UG9y'.'d'.'GFs',''.'Rg'.'==','WA==','Rg==','RA==','ZQ==','ZQ==','RA'.'==','bQ==',''.'ZA==','WQ='.'=',''.'ZQ='.'=','WA==','ZQ'.'==','Rg==','ZQ==','RA==','Zg==','ZQ='.'=','RA==','ZQ='.'=','b'.'Q='.'=','ZA'.'='.'=','W'.'Q==','Zg==','Zg='.'=','Zg==','Z'.'g==','Zg==',''.'Zg'.'==','Zg='.'=','Zg==','bWFpbg==','fmNwZl'.'9'.'tYXBfdmFsdWU=','ZQ==',''.'Z'.'Q==','UG9y'.'dGFs','Rg==','W'.'A'.'==','VFlQRQ==','REFURQ==','RkVBV'.'FVSRVM=',''.'RVhQSVJF'.'RA'.'==','VFlQRQ'.'==',''.'RA'.'==','V'.'FJZ'.'X0RBW'.'VNf'.'Q09VTlQ=','REFURQ==','VFJ'.'ZX0RB'.'WVN'.'fQ09V'.'TlQ=','R'.'V'.'hQSVJFRA'.'==','RkVBVFVSR'.'VM=','Z'.'g==','Zg==','RE9D'.'V'.'U'.'1'.'F'.'TlRfUk9PVA==','L2JpdHJp'.'eC9tb'.'2R1bGVzLw==','L2l'.'uc3Rhb'.'GwvaW5kZXg'.'u'.'cGhw','Lg==','X'.'w==','c'.'2'.'VhcmNo',''.'Tg==','','','QUNUSV'.'ZF','WQ==','c'.'29jaWFs'.'bmV0d29yaw'.'==','YW'.'x'.'sb3d'.'fZnJ'.'pZWxk'.'cw'.'==','WQ==','SUQ=','c29j'.'aW'.'FsbmV0d29yaw==','Y'.'Wxsb3dfZnJpZ'.'Wxkcw==','SUQ=','c'.'29jaWFsbmV'.'0d29yaw==','YWxs'.'b3dfZnJp'.'ZWxkcw='.'=','Tg==','','','QUNUSVZF',''.'WQ'.'==','c'.'29jaWFs'.'bmV0d29yaw'.'==','YWxsb3'.'d'.'fbWl'.'jcm'.'9i'.'bG9nX3VzZ'.'XI=','WQ==','SUQ=','c29jaWFs'.'b'.'mV0d29yaw'.'='.'=','YWx'.'sb3dfbWl'.'jc'.'m9'.'ib'.'G'.'9nX3VzZ'.'XI=','SUQ=','c2'.'9jaWFsbmV'.'0d29yaw='.'=','YW'.'x'.'sb3'.'dfb'.'Wljcm9i'.'bG9nX3V'.'z'.'ZXI=',''.'c'.'29jaWFs'.'b'.'mV0d'.'29yaw'.'==','YWxs'.'b3df'.'bW'.'l'.'jcm9ibG9nX2d'.'yb3Vw','WQ==','SU'.'Q=','c29j'.'aWF'.'sbm'.'V'.'0'.'d'.'2'.'9yaw==','YWxs'.'b'.'3dfbWljcm9ibG9'.'n'.'X2d'.'yb3Vw',''.'SUQ=','c2'.'9'.'ja'.'WF'.'sb'.'mV'.'0d29ya'.'w'.'==','YWxsb3d'.'fbWljc'.'m9ibG9nX'.'2'.'dy'.'b3Vw','Tg='.'=','','','QUNUSVZ'.'F','WQ==','c29ja'.'WFsbmV0d2'.'9yaw==','YWxs'.'b3dfZmlsZXNfdXN'.'lcg==','WQ='.'=','SUQ=','c'.'29jaW'.'FsbmV0d29ya'.'w==','YWxsb3dfZmls'.'ZXNfd'.'XNlcg==','SU'.'Q=','c2'.'9'.'j'.'aW'.'F'.'sbm'.'V0d29'.'y'.'aw==',''.'YWxsb3'.'dfZmlsZXNfd'.'XNlc'.'g'.'==','Tg==','','','Q'.'UNUSV'.'ZF',''.'W'.'Q==','c2'.'9j'.'aWFsb'.'m'.'V0d'.'29'.'yaw==','YWxsb3dfYmxv'.'Z1'.'91c2Vy','W'.'Q'.'==',''.'SUQ=','c29ja'.'WFsbmV0d2'.'9ya'.'w==','Y'.'W'.'x'.'sb3'.'dfY'.'mxvZ1'.'91c2Vy','SUQ=','c29'.'ja'.'WFsbmV0d'.'29yaw='.'=',''.'YWx'.'sb'.'3dfYmxvZ191c2'.'Vy','Tg==','','','QUNUSVZF','WQ'.'==','c29ja'.'WFsbm'.'V0d2'.'9'.'yaw==','YW'.'xs'.'b'.'3df'.'cG'.'hvd'.'G9fdX'.'Nlcg'.'==','WQ==','SUQ'.'=','c29j'.'aW'.'FsbmV0d29yaw==','YWxs'.'b3dfc'.'GhvdG9fd'.'XNl'.'cg='.'=','SUQ'.'=','c29jaW'.'FsbmV0d29yaw==','YWx'.'sb3'.'dfcG'.'hvdG9fdXNlc'.'g==','Tg='.'=','','','Q'.'UN'.'USVZF','WQ==',''.'c2'.'9'.'ja'.'WF'.'sbm'.'V0d29yaw='.'=',''.'Y'.'Wxsb'.'3'.'dfZm9y'.'dW1fd'.'XN'.'lcg'.'==','WQ==','SUQ=','c29'.'jaWFsb'.'mV0d'.'29'.'yaw==','Y'.'Wxs'.'b3dfZm9yd'.'W1'.'fdX'.'N'.'l'.'cg='.'=','SUQ=','c2'.'9jaWFsbmV0d'.'29y'.'aw==','Y'.'Wxsb3dfZ'.'m9ydW1fdXNl'.'cg==','T'.'g='.'=','','','QU'.'NUSVZF',''.'W'.'Q='.'=','c29jaWFsb'.'mV0d29'.'y'.'a'.'w==',''.'YW'.'xsb3d'.'f'.'d'.'GFza'.'3Nf'.'dXNl'.'cg==','WQ==','SUQ=','c29ja'.'W'.'FsbmV0d2'.'9yaw='.'=',''.'YWxs'.'b3'.'dfdGFz'.'a3Nf'.'dXN'.'lcg==','SUQ'.'=',''.'c29jaWF'.'sb'.'mV0d29y'.'aw='.'=','YW'.'xsb3dfdGFza3'.'N'.'fdXNlcg==','c'.'29jaW'.'F'.'sbmV0d29ya'.'w='.'=','YW'.'xsb3dfdG'.'Fz'.'a'.'3Nf'.'Z'.'3JvdXA=','W'.'Q='.'=',''.'S'.'UQ=','c29jaWFsbm'.'V0d'.'29yaw==','Y'.'Wxs'.'b'.'3dfdG'.'Fza3NfZ'.'3JvdXA=',''.'SUQ=','c29j'.'a'.'WFsbmV'.'0'.'d'.'29yaw==','YWxsb3dfdGFza3NfZ3JvdXA=','dGFz'.'a3'.'M=','T'.'g==','','','QUN'.'USV'.'Z'.'F','W'.'Q==',''.'c29jaWFsb'.'mV0d29yaw'.'==','YWxsb3dfY'.'2FsZW5kYXJfdXNlcg==','WQ='.'=','SUQ=','c29'.'j'.'aWFsbmV0d2'.'9y'.'aw='.'=',''.'YWxsb'.'3dfY2FsZ'.'W5kYXJf'.'dXNlc'.'g'.'==','S'.'UQ=','c29jaWFsbm'.'V0d'.'2'.'9y'.'aw==',''.'YWxsb3'.'d'.'fY2Fs'.'ZW5kYXJf'.'d'.'XNl'.'cg==','c2'.'9'.'jaWFsbmV'.'0d29ya'.'w==','YWxsb3'.'dfY'.'2FsZW5kY'.'XJfZ'.'3J'.'vdXA=','WQ==','SUQ=',''.'c'.'29'.'jaWF'.'sbmV'.'0d2'.'9ya'.'w==','YWx'.'sb3df'.'Y2Fs'.'ZW5kY'.'X'.'JfZ3Jv'.'dXA=','S'.'UQ=','c'.'29'.'jaWF'.'s'.'b'.'mV0d29yaw==','YWxsb3dfY'.'2FsZW5k'.'YXJf'.'Z3J'.'v'.'dXA=','QUNU'.'SVZF','WQ==','Tg'.'='.'=','ZXh0cmFuZXQ'.'=','aWJ'.'sb2Nr',''.'T'.'2'.'5BZnRlckl'.'CbG9j'.'a0VsZW'.'1lbnR'.'Vc'.'G'.'Rh'.'dG'.'U=','a'.'W50'.'cmFuZXQ=','Q'.'0lu'.'dHJh'.'bmV0RX'.'Zlb'.'nRIY'.'W5kbGVy'.'cw'.'==','U1BSZWd'.'p'.'c'.'3'.'Rlc'.'lVwZGF0ZWR'.'JdG'.'Vt','Q0ludH'.'J'.'hbmV0U2h'.'hcmVw'.'b2lu'.'dDo6QWdl'.'bnR'.'M'.'a'.'XN0'.'cygpOw='.'=','aW50cmFuZXQ=','Tg==','Q0'.'l'.'udH'.'JhbmV0U2hhcmVwb2lud'.'D'.'o6QWdl'.'bnRR'.'dWV'.'1ZSg'.'pOw==','aW50cmFuZXQ=','Tg==','Q0'.'lud'.'HJhbm'.'V0U2'.'hh'.'cmVwb2ludDo6QWd'.'l'.'bnRV'.'c'.'GRh'.'dGU'.'o'.'KTs=','aW'.'5'.'0cmF'.'u'.'ZXQ=','Tg==','a'.'WJsb2Nr','T25BZnRlc'.'klCb'.'G9ja'.'0V'.'sZW1lb'.'nR'.'BZGQ'.'=','a'.'W'.'5'.'0cm'.'FuZXQ=','Q0ludHJhbmV0RXZlb'.'nRIYW5kb'.'G'.'Vycw==','U1BSZWdp'.'c3R'.'lclVwZG'.'F0'.'ZWRJ'.'d'.'G'.'V'.'t','aWJ'.'sb2Nr','T'.'25BZn'.'Rlck'.'lCbG9ja0V'.'sZW1lbnRVcGR'.'hdG'.'U=',''.'aW'.'50cmFuZ'.'XQ=','Q0ludH'.'JhbmV'.'0R'.'XZlb'.'nRI'.'YW5kbGVycw==','U1BSZ'.'W'.'dpc3RlclV'.'wZGF'.'0Z'.'WRJd'.'GVt','Q0ludHJ'.'hb'.'mV'.'0U2hhcmVwb'.'2ludD'.'o6QWdlbnRM'.'a'.'XN0cy'.'g'.'pOw==','aW50'.'cmFu'.'ZXQ=','Q'.'0'.'lu'.'d'.'H'.'Jhb'.'mV0'.'U2'.'hhcmVwb2ludDo6'.'Q'.'Wdl'.'bnRR'.'dW'.'V1'.'ZS'.'g'.'pOw==','aW5'.'0cmFuZXQ=',''.'Q0lu'.'dH'.'JhbmV0U'.'2hh'.'c'.'mVwb2ludDo6Q'.'WdlbnRVcGRhd'.'GUo'.'KT'.'s=','aW5'.'0cmF'.'uZX'.'Q'.'=',''.'Y3Jt',''.'bWFpb'.'g==','T2'.'5CZ'.'WZvcm'.'VQ'.'cm9sb'.'2c=','bWFpbg='.'=','Q'.'1d'.'p'.'emFyZFNv'.'b'.'FBh'.'b'.'mVsS'.'W50cmFuZX'.'Q=','U2hvd'.'1BhbmVs','L2'.'1vZHVs'.'ZXMvaW50'.'c'.'mFuZXQvcG'.'Fu'.'ZWxfYn'.'V0dG9uLnB'.'ocA==',''.'Z'.'Xh'.'waXJ'.'lX21lc3My','bm9p'.'dGlk'.'ZV90aW1pbG'.'Vta'.'XQ=','WQ==','Z'.'H'.'Jpbl9wZ'.'XJn'.'b2t'.'j',''.'JTAxMHM'.'K','RUVYUEl'.'S','bWFpbg='.'=','JXMlc'.'w'.'==','YWRt',''.'aGRyb'.'3dzc2E'.'=',''.'YWRtaW'.'4'.'=','b'.'W9'.'kdWxlcw==',''.'ZGV'.'maW5l'.'LnBocA==','bWFp'.'bg==','Yml0cml4','Uk'.'hTS'.'VRFRVg=','SDR1N'.'j'.'dmaHc4N1Zo'.'eXRv'.'cw==','',''.'dGhS','N0h5cjEySHd5MHJGcg==',''.'V'.'F'.'9TVE'.'VBTA==','aHR'.'0cDov'.'L2JpdHJpeH'.'NvZ'.'n'.'Q'.'uY29tL2Jp'.'dHJpeC9icy5w'.'aHA=','T0xE','U'.'El'.'SRURBVEV'.'T','RE9'.'DVU1F'.'TlRfUk9P'.'V'.'A='.'=','Lw==','Lw='.'=','VEVN'.'UE9SQ'.'VJZX'.'0'.'NBQ0hF',''.'V'.'EVNUE9SQVJZX'.'0'.'NBQ0'.'h'.'F','','T05fT0Q=',''.'JXMl'.'cw==','X09'.'VU'.'l9C'.'VV'.'M=','U0lU','RURBVEVN'.'QVBFU'.'g==',''.'bm'.'9'.'pdGlkZV'.'90aW1pbGV'.'t'.'aX'.'Q=','R'.'E9'.'D'.'VU1F'.'Tl'.'RfUk9PV'.'A'.'==','L2J'.'p'.'dHJ'.'p'.'eC'.'8uY29uZml'.'nLn'.'Boc'.'A==','R'.'E9DVU'.'1FTlRfUk9'.'PVA='.'=','L2J'.'pdHJpeC'.'8uY'.'29uZmln'.'Ln'.'BocA==','c2Fh'.'c'.'w==','ZGF'.'5c1'.'9hZn'.'Rlcl90'.'c'.'mlhb'.'A==',''.'c2'.'F'.'hcw='.'=','Z'.'GF'.'5c'.'19hZnRlcl90'.'cmlhb'.'A==','c2Fhcw==','dHJpYWx'.'fc3RvcH'.'BlZA==','','c'.'2Fh'.'cw==','dHJpYWxfc3'.'RvcHBl'.'ZA='.'=','b'.'Q==','ZA'.'='.'=','WQ'.'==',''.'U2'.'l0'.'ZUV4cGl'.'yZ'.'URhdGU=');return base64_decode($_1396145656[$_612473165]);}};$GLOBALS['____900957568'][0](___390550584(0), ___390550584(1));class CBXFeatures{ private static $_585292868= 30; private static $_41290535= array( "Portal" => array( "CompanyCalendar", "CompanyPhoto", "CompanyVideo", "CompanyCareer", "StaffChanges", "StaffAbsence", "CommonDocuments", "MeetingRoomBookingSystem", "Wiki", "Learning", "Vote", "WebLink", "Subscribe", "Friends", "PersonalFiles", "PersonalBlog", "PersonalPhoto", "PersonalForum", "Blog", "Forum", "Gallery", "Board", "MicroBlog", "WebMessenger",), "Communications" => array( "Tasks", "Calendar", "Workgroups", "Jabber", "VideoConference", "Extranet", "SMTP", "Requests", "DAV", "intranet_sharepoint", "timeman", "Idea", "Meeting", "EventList", "Salary", "XDImport",), "Enterprise" => array( "BizProc", "Lists", "Support", "Analytics", "crm", "Controller", "LdapUnlimitedUsers",), "Holding" => array( "Cluster", "MultiSites",),); private static $_1059093822= false; private static $_713594584= false; private static function __1655920724(){ if(self::$_1059093822 == false){ self::$_1059093822= array(); foreach(self::$_41290535 as $_1835117342 => $_107459627){ foreach($_107459627 as $_171395563) self::$_1059093822[$_171395563]= $_1835117342;}} if(self::$_713594584 == false){ self::$_713594584= array(); $_1018450720= COption::GetOptionString(___390550584(2), ___390550584(3), ___390550584(4)); if($GLOBALS['____900957568'][1]($_1018450720)>(1456/2-728)){ $_1018450720= $GLOBALS['____900957568'][2]($_1018450720); self::$_713594584= $GLOBALS['____900957568'][3]($_1018450720); if(!$GLOBALS['____900957568'][4](self::$_713594584)) self::$_713594584= array();} if($GLOBALS['____900957568'][5](self::$_713594584) <=(1492/2-746)) self::$_713594584= array(___390550584(5) => array(), ___390550584(6) => array());}} public static function InitiateEditionsSettings($_374510944){ self::__1655920724(); $_1092669164= array(); foreach(self::$_41290535 as $_1835117342 => $_107459627){ $_1465390903= $GLOBALS['____900957568'][6]($_1835117342, $_374510944); self::$_713594584[___390550584(7)][$_1835117342]=($_1465390903? array(___390550584(8)): array(___390550584(9))); foreach($_107459627 as $_171395563){ self::$_713594584[___390550584(10)][$_171395563]= $_1465390903; if(!$_1465390903) $_1092669164[]= array($_171395563, false);}} $_773736164= $GLOBALS['____900957568'][7](self::$_713594584); $_773736164= $GLOBALS['____900957568'][8]($_773736164); COption::SetOptionString(___390550584(11), ___390550584(12), $_773736164); foreach($_1092669164 as $_224993591) self::__35157523($_224993591[(1128/2-564)], $_224993591[round(0+0.33333333333333+0.33333333333333+0.33333333333333)]);} public static function IsFeatureEnabled($_171395563){ if($GLOBALS['____900957568'][9]($_171395563) <= 0) return true; self::__1655920724(); if(!$GLOBALS['____900957568'][10]($_171395563, self::$_1059093822)) return true; if(self::$_1059093822[$_171395563] == ___390550584(13)) $_1032955552= array(___390550584(14)); elseif($GLOBALS['____900957568'][11](self::$_1059093822[$_171395563], self::$_713594584[___390550584(15)])) $_1032955552= self::$_713594584[___390550584(16)][self::$_1059093822[$_171395563]]; else $_1032955552= array(___390550584(17)); if($_1032955552[(203*2-406)] != ___390550584(18) && $_1032955552[(1352/2-676)] != ___390550584(19)){ return false;} elseif($_1032955552[min(10,0,3.3333333333333)] == ___390550584(20)){ if($_1032955552[round(0+1)]< $GLOBALS['____900957568'][12]((846-2*423),(1444/2-722),(211*2-422), Date(___390550584(21)), $GLOBALS['____900957568'][13](___390550584(22))- self::$_585292868, $GLOBALS['____900957568'][14](___390550584(23)))){ if(!isset($_1032955552[round(0+0.5+0.5+0.5+0.5)]) ||!$_1032955552[round(0+2)]) self::__1828926856(self::$_1059093822[$_171395563]); return false;}} return!$GLOBALS['____900957568'][15]($_171395563, self::$_713594584[___390550584(24)]) || self::$_713594584[___390550584(25)][$_171395563];} public static function IsFeatureInstalled($_171395563){ if($GLOBALS['____900957568'][16]($_171395563) <= 0) return true; self::__1655920724(); return($GLOBALS['____900957568'][17]($_171395563, self::$_713594584[___390550584(26)]) && self::$_713594584[___390550584(27)][$_171395563]);} public static function IsFeatureEditable($_171395563){ if($GLOBALS['____900957568'][18]($_171395563) <= 0) return true; self::__1655920724(); if(!$GLOBALS['____900957568'][19]($_171395563, self::$_1059093822)) return true; if(self::$_1059093822[$_171395563] == ___390550584(28)) $_1032955552= array(___390550584(29)); elseif($GLOBALS['____900957568'][20](self::$_1059093822[$_171395563], self::$_713594584[___390550584(30)])) $_1032955552= self::$_713594584[___390550584(31)][self::$_1059093822[$_171395563]]; else $_1032955552= array(___390550584(32)); if($_1032955552[(232*2-464)] != ___390550584(33) && $_1032955552[(998-2*499)] != ___390550584(34)){ return false;} elseif($_1032955552[(1156/2-578)] == ___390550584(35)){ if($_1032955552[round(0+0.2+0.2+0.2+0.2+0.2)]< $GLOBALS['____900957568'][21]((240*2-480),(247*2-494),(1296/2-648), Date(___390550584(36)), $GLOBALS['____900957568'][22](___390550584(37))- self::$_585292868, $GLOBALS['____900957568'][23](___390550584(38)))){ if(!isset($_1032955552[round(0+0.66666666666667+0.66666666666667+0.66666666666667)]) ||!$_1032955552[round(0+2)]) self::__1828926856(self::$_1059093822[$_171395563]); return false;}} return true;} private static function __35157523($_171395563, $_1401264852){ if($GLOBALS['____900957568'][24]("CBXFeatures", "On".$_171395563."SettingsChange")) $GLOBALS['____900957568'][25](array("CBXFeatures", "On".$_171395563."SettingsChange"), array($_171395563, $_1401264852)); $_468842719= $GLOBALS['_____363473648'][0](___390550584(39), ___390550584(40).$_171395563.___390550584(41)); while($_476508410= $_468842719->Fetch()) $GLOBALS['_____363473648'][1]($_476508410, array($_171395563, $_1401264852));} public static function SetFeatureEnabled($_171395563, $_1401264852= true, $_373576595= true){ if($GLOBALS['____900957568'][26]($_171395563) <= 0) return; if(!self::IsFeatureEditable($_171395563)) $_1401264852= false; $_1401264852=($_1401264852? true: false); self::__1655920724(); $_2041496083=(!$GLOBALS['____900957568'][27]($_171395563, self::$_713594584[___390550584(42)]) && $_1401264852 || $GLOBALS['____900957568'][28]($_171395563, self::$_713594584[___390550584(43)]) && $_1401264852 != self::$_713594584[___390550584(44)][$_171395563]); self::$_713594584[___390550584(45)][$_171395563]= $_1401264852; $_773736164= $GLOBALS['____900957568'][29](self::$_713594584); $_773736164= $GLOBALS['____900957568'][30]($_773736164); COption::SetOptionString(___390550584(46), ___390550584(47), $_773736164); if($_2041496083 && $_373576595) self::__35157523($_171395563, $_1401264852);} private static function __1828926856($_1835117342){ if($GLOBALS['____900957568'][31]($_1835117342) <= 0 || $_1835117342 == "Portal") return; self::__1655920724(); if(!$GLOBALS['____900957568'][32]($_1835117342, self::$_713594584[___390550584(48)]) || $GLOBALS['____900957568'][33]($_1835117342, self::$_713594584[___390550584(49)]) && self::$_713594584[___390550584(50)][$_1835117342][min(134,0,44.666666666667)] != ___390550584(51)) return; if(isset(self::$_713594584[___390550584(52)][$_1835117342][round(0+0.4+0.4+0.4+0.4+0.4)]) && self::$_713594584[___390550584(53)][$_1835117342][round(0+2)]) return; $_1092669164= array(); if($GLOBALS['____900957568'][34]($_1835117342, self::$_41290535) && $GLOBALS['____900957568'][35](self::$_41290535[$_1835117342])){ foreach(self::$_41290535[$_1835117342] as $_171395563){ if($GLOBALS['____900957568'][36]($_171395563, self::$_713594584[___390550584(54)]) && self::$_713594584[___390550584(55)][$_171395563]){ self::$_713594584[___390550584(56)][$_171395563]= false; $_1092669164[]= array($_171395563, false);}} self::$_713594584[___390550584(57)][$_1835117342][round(0+0.4+0.4+0.4+0.4+0.4)]= true;} $_773736164= $GLOBALS['____900957568'][37](self::$_713594584); $_773736164= $GLOBALS['____900957568'][38]($_773736164); COption::SetOptionString(___390550584(58), ___390550584(59), $_773736164); foreach($_1092669164 as $_224993591) self::__35157523($_224993591[(201*2-402)], $_224993591[round(0+1)]);} public static function ModifyFeaturesSettings($_374510944, $_107459627){ self::__1655920724(); foreach($_374510944 as $_1835117342 => $_136826941) self::$_713594584[___390550584(60)][$_1835117342]= $_136826941; $_1092669164= array(); foreach($_107459627 as $_171395563 => $_1401264852){ if(!$GLOBALS['____900957568'][39]($_171395563, self::$_713594584[___390550584(61)]) && $_1401264852 || $GLOBALS['____900957568'][40]($_171395563, self::$_713594584[___390550584(62)]) && $_1401264852 != self::$_713594584[___390550584(63)][$_171395563]) $_1092669164[]= array($_171395563, $_1401264852); self::$_713594584[___390550584(64)][$_171395563]= $_1401264852;} $_773736164= $GLOBALS['____900957568'][41](self::$_713594584); $_773736164= $GLOBALS['____900957568'][42]($_773736164); COption::SetOptionString(___390550584(65), ___390550584(66), $_773736164); self::$_713594584= false; foreach($_1092669164 as $_224993591) self::__35157523($_224993591[(227*2-454)], $_224993591[round(0+0.25+0.25+0.25+0.25)]);} public static function SaveFeaturesSettings($_1962553006, $_988108866){ self::__1655920724(); $_1767215648= array(___390550584(67) => array(), ___390550584(68) => array()); if(!$GLOBALS['____900957568'][43]($_1962553006)) $_1962553006= array(); if(!$GLOBALS['____900957568'][44]($_988108866)) $_988108866= array(); if(!$GLOBALS['____900957568'][45](___390550584(69), $_1962553006)) $_1962553006[]= ___390550584(70); foreach(self::$_41290535 as $_1835117342 => $_107459627){ if($GLOBALS['____900957568'][46]($_1835117342, self::$_713594584[___390550584(71)])) $_838374991= self::$_713594584[___390550584(72)][$_1835117342]; else $_838374991=($_1835117342 == ___390550584(73))? array(___390550584(74)): array(___390550584(75)); if($_838374991[(1444/2-722)] == ___390550584(76) || $_838374991[(878-2*439)] == ___390550584(77)){ $_1767215648[___390550584(78)][$_1835117342]= $_838374991;} else{ if($GLOBALS['____900957568'][47]($_1835117342, $_1962553006)) $_1767215648[___390550584(79)][$_1835117342]= array(___390550584(80), $GLOBALS['____900957568'][48](min(168,0,56),(978-2*489),(902-2*451), $GLOBALS['____900957568'][49](___390550584(81)), $GLOBALS['____900957568'][50](___390550584(82)), $GLOBALS['____900957568'][51](___390550584(83)))); else $_1767215648[___390550584(84)][$_1835117342]= array(___390550584(85));}} $_1092669164= array(); foreach(self::$_1059093822 as $_171395563 => $_1835117342){ if($_1767215648[___390550584(86)][$_1835117342][(1216/2-608)] != ___390550584(87) && $_1767215648[___390550584(88)][$_1835117342][(948-2*474)] != ___390550584(89)){ $_1767215648[___390550584(90)][$_171395563]= false;} else{ if($_1767215648[___390550584(91)][$_1835117342][(202*2-404)] == ___390550584(92) && $_1767215648[___390550584(93)][$_1835117342][round(0+0.2+0.2+0.2+0.2+0.2)]< $GLOBALS['____900957568'][52]((131*2-262),(1176/2-588),(938-2*469), Date(___390550584(94)), $GLOBALS['____900957568'][53](___390550584(95))- self::$_585292868, $GLOBALS['____900957568'][54](___390550584(96)))) $_1767215648[___390550584(97)][$_171395563]= false; else $_1767215648[___390550584(98)][$_171395563]= $GLOBALS['____900957568'][55]($_171395563, $_988108866); if(!$GLOBALS['____900957568'][56]($_171395563, self::$_713594584[___390550584(99)]) && $_1767215648[___390550584(100)][$_171395563] || $GLOBALS['____900957568'][57]($_171395563, self::$_713594584[___390550584(101)]) && $_1767215648[___390550584(102)][$_171395563] != self::$_713594584[___390550584(103)][$_171395563]) $_1092669164[]= array($_171395563, $_1767215648[___390550584(104)][$_171395563]);}} $_773736164= $GLOBALS['____900957568'][58]($_1767215648); $_773736164= $GLOBALS['____900957568'][59]($_773736164); COption::SetOptionString(___390550584(105), ___390550584(106), $_773736164); self::$_713594584= false; foreach($_1092669164 as $_224993591) self::__35157523($_224993591[(970-2*485)], $_224993591[round(0+0.25+0.25+0.25+0.25)]);} public static function GetFeaturesList(){ self::__1655920724(); $_1641748942= array(); foreach(self::$_41290535 as $_1835117342 => $_107459627){ if($GLOBALS['____900957568'][60]($_1835117342, self::$_713594584[___390550584(107)])) $_838374991= self::$_713594584[___390550584(108)][$_1835117342]; else $_838374991=($_1835117342 == ___390550584(109))? array(___390550584(110)): array(___390550584(111)); $_1641748942[$_1835117342]= array( ___390550584(112) => $_838374991[(203*2-406)], ___390550584(113) => $_838374991[round(0+1)], ___390550584(114) => array(),); $_1641748942[$_1835117342][___390550584(115)]= false; if($_1641748942[$_1835117342][___390550584(116)] == ___390550584(117)){ $_1641748942[$_1835117342][___390550584(118)]= $GLOBALS['____900957568'][61](($GLOBALS['____900957568'][62]()- $_1641748942[$_1835117342][___390550584(119)])/ round(0+28800+28800+28800)); if($_1641748942[$_1835117342][___390550584(120)]> self::$_585292868) $_1641748942[$_1835117342][___390550584(121)]= true;} foreach($_107459627 as $_171395563) $_1641748942[$_1835117342][___390550584(122)][$_171395563]=(!$GLOBALS['____900957568'][63]($_171395563, self::$_713594584[___390550584(123)]) || self::$_713594584[___390550584(124)][$_171395563]);} return $_1641748942;} private static function __234496139($_688677596, $_1703214192){ if(IsModuleInstalled($_688677596) == $_1703214192) return true; $_351800485= $_SERVER[___390550584(125)].___390550584(126).$_688677596.___390550584(127); if(!$GLOBALS['____900957568'][64]($_351800485)) return false; include_once($_351800485); $_401194266= $GLOBALS['____900957568'][65](___390550584(128), ___390550584(129), $_688677596); if(!$GLOBALS['____900957568'][66]($_401194266)) return false; $_652586779= new $_401194266; if($_1703214192){ if(!$_652586779->InstallDB()) return false; $_652586779->InstallEvents(); if(!$_652586779->InstallFiles()) return false;} else{ if(CModule::IncludeModule(___390550584(130))) CSearch::DeleteIndex($_688677596); UnRegisterModule($_688677596);} return true;} protected static function OnRequestsSettingsChange($_171395563, $_1401264852){ self::__234496139("form", $_1401264852);} protected static function OnLearningSettingsChange($_171395563, $_1401264852){ self::__234496139("learning", $_1401264852);} protected static function OnJabberSettingsChange($_171395563, $_1401264852){ self::__234496139("xmpp", $_1401264852);} protected static function OnVideoConferenceSettingsChange($_171395563, $_1401264852){ self::__234496139("video", $_1401264852);} protected static function OnBizProcSettingsChange($_171395563, $_1401264852){ self::__234496139("bizprocdesigner", $_1401264852);} protected static function OnListsSettingsChange($_171395563, $_1401264852){ self::__234496139("lists", $_1401264852);} protected static function OnWikiSettingsChange($_171395563, $_1401264852){ self::__234496139("wiki", $_1401264852);} protected static function OnSupportSettingsChange($_171395563, $_1401264852){ self::__234496139("support", $_1401264852);} protected static function OnControllerSettingsChange($_171395563, $_1401264852){ self::__234496139("controller", $_1401264852);} protected static function OnAnalyticsSettingsChange($_171395563, $_1401264852){ self::__234496139("statistic", $_1401264852);} protected static function OnVoteSettingsChange($_171395563, $_1401264852){ self::__234496139("vote", $_1401264852);} protected static function OnFriendsSettingsChange($_171395563, $_1401264852){ if($_1401264852) $_1127806445= "Y"; else $_1127806445= ___390550584(131); $_1471351536= CSite::GetList(($_1465390903= ___390550584(132)),($_1120502150= ___390550584(133)), array(___390550584(134) => ___390550584(135))); while($_2032588472= $_1471351536->Fetch()){ if(COption::GetOptionString(___390550584(136), ___390550584(137), ___390550584(138), $_2032588472[___390550584(139)]) != $_1127806445){ COption::SetOptionString(___390550584(140), ___390550584(141), $_1127806445, false, $_2032588472[___390550584(142)]); COption::SetOptionString(___390550584(143), ___390550584(144), $_1127806445);}}} protected static function OnMicroBlogSettingsChange($_171395563, $_1401264852){ if($_1401264852) $_1127806445= "Y"; else $_1127806445= ___390550584(145); $_1471351536= CSite::GetList(($_1465390903= ___390550584(146)),($_1120502150= ___390550584(147)), array(___390550584(148) => ___390550584(149))); while($_2032588472= $_1471351536->Fetch()){ if(COption::GetOptionString(___390550584(150), ___390550584(151), ___390550584(152), $_2032588472[___390550584(153)]) != $_1127806445){ COption::SetOptionString(___390550584(154), ___390550584(155), $_1127806445, false, $_2032588472[___390550584(156)]); COption::SetOptionString(___390550584(157), ___390550584(158), $_1127806445);} if(COption::GetOptionString(___390550584(159), ___390550584(160), ___390550584(161), $_2032588472[___390550584(162)]) != $_1127806445){ COption::SetOptionString(___390550584(163), ___390550584(164), $_1127806445, false, $_2032588472[___390550584(165)]); COption::SetOptionString(___390550584(166), ___390550584(167), $_1127806445);}}} protected static function OnPersonalFilesSettingsChange($_171395563, $_1401264852){ if($_1401264852) $_1127806445= "Y"; else $_1127806445= ___390550584(168); $_1471351536= CSite::GetList(($_1465390903= ___390550584(169)),($_1120502150= ___390550584(170)), array(___390550584(171) => ___390550584(172))); while($_2032588472= $_1471351536->Fetch()){ if(COption::GetOptionString(___390550584(173), ___390550584(174), ___390550584(175), $_2032588472[___390550584(176)]) != $_1127806445){ COption::SetOptionString(___390550584(177), ___390550584(178), $_1127806445, false, $_2032588472[___390550584(179)]); COption::SetOptionString(___390550584(180), ___390550584(181), $_1127806445);}}} protected static function OnPersonalBlogSettingsChange($_171395563, $_1401264852){ if($_1401264852) $_1127806445= "Y"; else $_1127806445= ___390550584(182); $_1471351536= CSite::GetList(($_1465390903= ___390550584(183)),($_1120502150= ___390550584(184)), array(___390550584(185) => ___390550584(186))); while($_2032588472= $_1471351536->Fetch()){ if(COption::GetOptionString(___390550584(187), ___390550584(188), ___390550584(189), $_2032588472[___390550584(190)]) != $_1127806445){ COption::SetOptionString(___390550584(191), ___390550584(192), $_1127806445, false, $_2032588472[___390550584(193)]); COption::SetOptionString(___390550584(194), ___390550584(195), $_1127806445);}}} protected static function OnPersonalPhotoSettingsChange($_171395563, $_1401264852){ if($_1401264852) $_1127806445= "Y"; else $_1127806445= ___390550584(196); $_1471351536= CSite::GetList(($_1465390903= ___390550584(197)),($_1120502150= ___390550584(198)), array(___390550584(199) => ___390550584(200))); while($_2032588472= $_1471351536->Fetch()){ if(COption::GetOptionString(___390550584(201), ___390550584(202), ___390550584(203), $_2032588472[___390550584(204)]) != $_1127806445){ COption::SetOptionString(___390550584(205), ___390550584(206), $_1127806445, false, $_2032588472[___390550584(207)]); COption::SetOptionString(___390550584(208), ___390550584(209), $_1127806445);}}} protected static function OnPersonalForumSettingsChange($_171395563, $_1401264852){ if($_1401264852) $_1127806445= "Y"; else $_1127806445= ___390550584(210); $_1471351536= CSite::GetList(($_1465390903= ___390550584(211)),($_1120502150= ___390550584(212)), array(___390550584(213) => ___390550584(214))); while($_2032588472= $_1471351536->Fetch()){ if(COption::GetOptionString(___390550584(215), ___390550584(216), ___390550584(217), $_2032588472[___390550584(218)]) != $_1127806445){ COption::SetOptionString(___390550584(219), ___390550584(220), $_1127806445, false, $_2032588472[___390550584(221)]); COption::SetOptionString(___390550584(222), ___390550584(223), $_1127806445);}}} protected static function OnTasksSettingsChange($_171395563, $_1401264852){ if($_1401264852) $_1127806445= "Y"; else $_1127806445= ___390550584(224); $_1471351536= CSite::GetList(($_1465390903= ___390550584(225)),($_1120502150= ___390550584(226)), array(___390550584(227) => ___390550584(228))); while($_2032588472= $_1471351536->Fetch()){ if(COption::GetOptionString(___390550584(229), ___390550584(230), ___390550584(231), $_2032588472[___390550584(232)]) != $_1127806445){ COption::SetOptionString(___390550584(233), ___390550584(234), $_1127806445, false, $_2032588472[___390550584(235)]); COption::SetOptionString(___390550584(236), ___390550584(237), $_1127806445);} if(COption::GetOptionString(___390550584(238), ___390550584(239), ___390550584(240), $_2032588472[___390550584(241)]) != $_1127806445){ COption::SetOptionString(___390550584(242), ___390550584(243), $_1127806445, false, $_2032588472[___390550584(244)]); COption::SetOptionString(___390550584(245), ___390550584(246), $_1127806445);}} self::__234496139(___390550584(247), $_1401264852);} protected static function OnCalendarSettingsChange($_171395563, $_1401264852){ if($_1401264852) $_1127806445= "Y"; else $_1127806445= ___390550584(248); $_1471351536= CSite::GetList(($_1465390903= ___390550584(249)),($_1120502150= ___390550584(250)), array(___390550584(251) => ___390550584(252))); while($_2032588472= $_1471351536->Fetch()){ if(COption::GetOptionString(___390550584(253), ___390550584(254), ___390550584(255), $_2032588472[___390550584(256)]) != $_1127806445){ COption::SetOptionString(___390550584(257), ___390550584(258), $_1127806445, false, $_2032588472[___390550584(259)]); COption::SetOptionString(___390550584(260), ___390550584(261), $_1127806445);} if(COption::GetOptionString(___390550584(262), ___390550584(263), ___390550584(264), $_2032588472[___390550584(265)]) != $_1127806445){ COption::SetOptionString(___390550584(266), ___390550584(267), $_1127806445, false, $_2032588472[___390550584(268)]); COption::SetOptionString(___390550584(269), ___390550584(270), $_1127806445);}}} protected static function OnSMTPSettingsChange($_171395563, $_1401264852){ self::__234496139("mail", $_1401264852);} protected static function OnExtranetSettingsChange($_171395563, $_1401264852){ $_1519572269= COption::GetOptionString("extranet", "extranet_site", ""); if($_1519572269){ $_955645129= new CSite; $_955645129->Update($_1519572269, array(___390550584(271) =>($_1401264852? ___390550584(272): ___390550584(273))));} self::__234496139(___390550584(274), $_1401264852);} protected static function OnDAVSettingsChange($_171395563, $_1401264852){ self::__234496139("dav", $_1401264852);} protected static function OntimemanSettingsChange($_171395563, $_1401264852){ self::__234496139("timeman", $_1401264852);} protected static function Onintranet_sharepointSettingsChange($_171395563, $_1401264852){ if($_1401264852){ RegisterModuleDependences("iblock", "OnAfterIBlockElementAdd", "intranet", "CIntranetEventHandlers", "SPRegisterUpdatedItem"); RegisterModuleDependences(___390550584(275), ___390550584(276), ___390550584(277), ___390550584(278), ___390550584(279)); CAgent::AddAgent(___390550584(280), ___390550584(281), ___390550584(282), round(0+166.66666666667+166.66666666667+166.66666666667)); CAgent::AddAgent(___390550584(283), ___390550584(284), ___390550584(285), round(0+150+150)); CAgent::AddAgent(___390550584(286), ___390550584(287), ___390550584(288), round(0+1200+1200+1200));} else{ UnRegisterModuleDependences(___390550584(289), ___390550584(290), ___390550584(291), ___390550584(292), ___390550584(293)); UnRegisterModuleDependences(___390550584(294), ___390550584(295), ___390550584(296), ___390550584(297), ___390550584(298)); CAgent::RemoveAgent(___390550584(299), ___390550584(300)); CAgent::RemoveAgent(___390550584(301), ___390550584(302)); CAgent::RemoveAgent(___390550584(303), ___390550584(304));}} protected static function OncrmSettingsChange($_171395563, $_1401264852){ if($_1401264852) COption::SetOptionString("crm", "form_features", "Y"); self::__234496139(___390550584(305), $_1401264852);} protected static function OnClusterSettingsChange($_171395563, $_1401264852){ self::__234496139("cluster", $_1401264852);} protected static function OnMultiSitesSettingsChange($_171395563, $_1401264852){ if($_1401264852) RegisterModuleDependences("main", "OnBeforeProlog", "main", "CWizardSolPanelIntranet", "ShowPanel", 100, "/modules/intranet/panel_button.php"); else UnRegisterModuleDependences(___390550584(306), ___390550584(307), ___390550584(308), ___390550584(309), ___390550584(310), ___390550584(311));} protected static function OnIdeaSettingsChange($_171395563, $_1401264852){ self::__234496139("idea", $_1401264852);} protected static function OnMeetingSettingsChange($_171395563, $_1401264852){ self::__234496139("meeting", $_1401264852);} protected static function OnXDImportSettingsChange($_171395563, $_1401264852){ self::__234496139("xdimport", $_1401264852);}} $_1179408650= GetMessage(___390550584(312));$_718760895= round(0+14);$GLOBALS['____900957568'][67]($GLOBALS['____900957568'][68]($GLOBALS['____900957568'][69](___390550584(313))), ___390550584(314));$_767788677= round(0+0.33333333333333+0.33333333333333+0.33333333333333); $_51082577= ___390550584(315); unset($_1287528471); $_1952089142= $GLOBALS['____900957568'][70](___390550584(316), ___390550584(317)); $_1287528471= \COption::GetOptionString(___390550584(318), $GLOBALS['____900957568'][71](___390550584(319),___390550584(320),$GLOBALS['____900957568'][72]($_51082577, round(0+0.5+0.5+0.5+0.5), round(0+0.8+0.8+0.8+0.8+0.8))).$GLOBALS['____900957568'][73](___390550584(321))); $_1883106826= array(round(0+4.25+4.25+4.25+4.25) => ___390550584(322), round(0+3.5+3.5) => ___390550584(323), round(0+7.3333333333333+7.3333333333333+7.3333333333333) => ___390550584(324), round(0+3+3+3+3) => ___390550584(325), round(0+1+1+1) => ___390550584(326)); $_1225234785= ___390550584(327); while($_1287528471){ $_759975973= ___390550584(328); $_2036218925= $GLOBALS['____900957568'][74]($_1287528471); $_825468302= ___390550584(329); $_759975973= $GLOBALS['____900957568'][75](___390550584(330).$_759975973,(192*2-384),-round(0+5)).___390550584(331); $_566766102= $GLOBALS['____900957568'][76]($_759975973); $_1403401972= min(44,0,14.666666666667); for($_1935750839=(1488/2-744); $_1935750839<$GLOBALS['____900957568'][77]($_2036218925); $_1935750839++){ $_825468302 .= $GLOBALS['____900957568'][78]($GLOBALS['____900957568'][79]($_2036218925[$_1935750839])^ $GLOBALS['____900957568'][80]($_759975973[$_1403401972])); if($_1403401972==$_566766102-round(0+0.2+0.2+0.2+0.2+0.2)) $_1403401972=(1340/2-670); else $_1403401972= $_1403401972+ round(0+0.25+0.25+0.25+0.25);} $_767788677= $GLOBALS['____900957568'][81]((984-2*492),(1000-2*500), min(140,0,46.666666666667), $GLOBALS['____900957568'][82]($_825468302[round(0+1.5+1.5+1.5+1.5)].$_825468302[round(0+1+1+1)]), $GLOBALS['____900957568'][83]($_825468302[round(0+0.25+0.25+0.25+0.25)].$_825468302[round(0+7+7)]), $GLOBALS['____900957568'][84]($_825468302[round(0+10)].$_825468302[round(0+9+9)].$_825468302[round(0+2.3333333333333+2.3333333333333+2.3333333333333)].$_825468302[round(0+4+4+4)])); unset($_759975973); break;} $_1993019499= ___390550584(332); $GLOBALS['____900957568'][85]($_1883106826); $_567932632= ___390550584(333); $_1225234785= ___390550584(334).$GLOBALS['____900957568'][86]($_1225234785.___390550584(335), round(0+0.5+0.5+0.5+0.5),-round(0+1));@include($_SERVER[___390550584(336)].___390550584(337).$GLOBALS['____900957568'][87](___390550584(338), $_1883106826)); $_1602493503= round(0+2); while($GLOBALS['____900957568'][88](___390550584(339))){ $_1956844443= $GLOBALS['____900957568'][89]($GLOBALS['____900957568'][90](___390550584(340))); $_1641568024= ___390550584(341); $_1993019499= $GLOBALS['____900957568'][91](___390550584(342)).$GLOBALS['____900957568'][92](___390550584(343),$_1993019499,___390550584(344)); $_240746811= $GLOBALS['____900957568'][93]($_1993019499); $_1403401972=(1224/2-612); for($_1935750839=(229*2-458); $_1935750839<$GLOBALS['____900957568'][94]($_1956844443); $_1935750839++){ $_1641568024 .= $GLOBALS['____900957568'][95]($GLOBALS['____900957568'][96]($_1956844443[$_1935750839])^ $GLOBALS['____900957568'][97]($_1993019499[$_1403401972])); if($_1403401972==$_240746811-round(0+0.25+0.25+0.25+0.25)) $_1403401972= min(118,0,39.333333333333); else $_1403401972= $_1403401972+ round(0+0.5+0.5);} $_1602493503= $GLOBALS['____900957568'][98]((786-2*393),(142*2-284), min(204,0,68), $GLOBALS['____900957568'][99]($_1641568024[round(0+6)].$_1641568024[round(0+16)]), $GLOBALS['____900957568'][100]($_1641568024[round(0+4.5+4.5)].$_1641568024[round(0+0.4+0.4+0.4+0.4+0.4)]), $GLOBALS['____900957568'][101]($_1641568024[round(0+12)].$_1641568024[round(0+2.3333333333333+2.3333333333333+2.3333333333333)].$_1641568024[round(0+4.6666666666667+4.6666666666667+4.6666666666667)].$_1641568024[round(0+0.75+0.75+0.75+0.75)])); unset($_1993019499); break;} $_1952089142= ___390550584(345).$GLOBALS['____900957568'][102]($GLOBALS['____900957568'][103]($_1952089142, round(0+3),-round(0+0.2+0.2+0.2+0.2+0.2)).___390550584(346), round(0+0.25+0.25+0.25+0.25),-round(0+5));while(!$GLOBALS['____900957568'][104]($GLOBALS['____900957568'][105]($GLOBALS['____900957568'][106](___390550584(347))))){function __f($_70566383){return $_70566383+__f($_70566383);}__f(round(0+1));};if($GLOBALS['____900957568'][107]($_SERVER[___390550584(348)].___390550584(349))){ $bxProductConfig= array(); include($_SERVER[___390550584(350)].___390550584(351)); if(isset($bxProductConfig[___390550584(352)][___390550584(353)])){ $_1441149009= $GLOBALS['____900957568'][108]($bxProductConfig[___390550584(354)][___390550584(355)]); if($_1441149009 >=(240*2-480) && $_1441149009< round(0+7+7)) $_718760895= $_1441149009;} if($bxProductConfig[___390550584(356)][___390550584(357)] <> ___390550584(358)) $_1179408650= $bxProductConfig[___390550584(359)][___390550584(360)];}for($_1935750839=(1284/2-642),$_1457083273=($GLOBALS['____900957568'][109]()< $GLOBALS['____900957568'][110]((872-2*436),(796-2*398),(848-2*424),round(0+1.25+1.25+1.25+1.25),round(0+0.2+0.2+0.2+0.2+0.2),round(0+403.6+403.6+403.6+403.6+403.6)) || $_767788677 <= round(0+10)),$_2133722587=($_767788677< $GLOBALS['____900957568'][111]((173*2-346),min(14,0,4.6666666666667),(1204/2-602),Date(___390550584(361)),$GLOBALS['____900957568'][112](___390550584(362))-$_718760895,$GLOBALS['____900957568'][113](___390550584(363)))); $_1935750839< round(0+2+2+2+2+2),$_1457083273 || $_2133722587 || $_767788677 != $_1602493503; $_1935750839++,$GLOBALS['_____363473648'][2]($_1179408650));$GLOBALS['____900957568'][114]($_1225234785, $_767788677); $GLOBALS['____900957568'][115]($_1952089142, $_1602493503); $GLOBALS[___390550584(364)]= OLDSITEEXPIREDATE;/**/			//Do not remove this

//component 2.0 template engines
$GLOBALS["arCustomTemplateEngines"] = [];

require_once(__DIR__."/autoload.php");
require_once(__DIR__."/classes/general/menu.php");
require_once(__DIR__."/classes/mysql/usertype.php");

if(file_exists(($_fname = __DIR__."/classes/general/update_db_updater.php")))
{
	$US_HOST_PROCESS_MAIN = False;
	include($_fname);
}

if(file_exists(($_fname = $_SERVER["DOCUMENT_ROOT"]."/bitrix/init.php")))
	include_once($_fname);

if(($_fname = getLocalPath("php_interface/init.php", BX_PERSONAL_ROOT)) !== false)
	include_once($_SERVER["DOCUMENT_ROOT"].$_fname);

if(($_fname = getLocalPath("php_interface/".SITE_ID."/init.php", BX_PERSONAL_ROOT)) !== false)
	include_once($_SERVER["DOCUMENT_ROOT"].$_fname);

if(!defined("BX_FILE_PERMISSIONS"))
	define("BX_FILE_PERMISSIONS", 0644);
if(!defined("BX_DIR_PERMISSIONS"))
	define("BX_DIR_PERMISSIONS", 0755);

//global var, is used somewhere
$GLOBALS["sDocPath"] = $GLOBALS["APPLICATION"]->GetCurPage();

if((!(defined("STATISTIC_ONLY") && STATISTIC_ONLY && mb_substr($GLOBALS["APPLICATION"]->GetCurPage(), 0, mb_strlen(BX_ROOT."/admin/")) != BX_ROOT."/admin/")) && COption::GetOptionString("main", "include_charset", "Y")=="Y" && LANG_CHARSET <> '')
	header("Content-Type: text/html; charset=".LANG_CHARSET);

if(COption::GetOptionString("main", "set_p3p_header", "Y")=="Y")
	header("P3P: policyref=\"/bitrix/p3p.xml\", CP=\"NON DSP COR CUR ADM DEV PSA PSD OUR UNR BUS UNI COM NAV INT DEM STA\"");

header("X-Powered-CMS: Bitrix Site Manager (".(LICENSE_KEY == "DEMO"? "DEMO" : md5("BITRIX".LICENSE_KEY."LICENCE")).")");
if (COption::GetOptionString("main", "update_devsrv", "") == "Y")
	header("X-DevSrv-CMS: Bitrix");

if (!defined("BX_CRONTAB_SUPPORT"))
{
	define("BX_CRONTAB_SUPPORT", defined("BX_CRONTAB"));
}

//agents
if(COption::GetOptionString("main", "check_agents", "Y") == "Y")
{
	$application->addBackgroundJob(["CAgent", "CheckAgents"], [], \Bitrix\Main\Application::JOB_PRIORITY_LOW);
}

//send email events
if(COption::GetOptionString("main", "check_events", "Y") !== "N")
{
	$application->addBackgroundJob(['\Bitrix\Main\Mail\EventManager', 'checkEvents'], [], \Bitrix\Main\Application::JOB_PRIORITY_LOW-1);
}

$healerOfEarlySessionStart = new HealerEarlySessionStart();
$healerOfEarlySessionStart->process($application->getKernelSession());

$kernelSession = $application->getKernelSession();
$kernelSession->start();
$application->getSessionLocalStorageManager()->setUniqueId($kernelSession->getId());

foreach (GetModuleEvents("main", "OnPageStart", true) as $arEvent)
	ExecuteModuleEventEx($arEvent);

//define global user object
$GLOBALS["USER"] = new CUser;

//session control from group policy
$arPolicy = $GLOBALS["USER"]->GetSecurityPolicy();
$currTime = time();
if(
	(
		//IP address changed
		$kernelSession['SESS_IP']
		&& $arPolicy["SESSION_IP_MASK"] <> ''
		&& (
			(ip2long($arPolicy["SESSION_IP_MASK"]) & ip2long($kernelSession['SESS_IP']))
			!=
			(ip2long($arPolicy["SESSION_IP_MASK"]) & ip2long($_SERVER['REMOTE_ADDR']))
		)
	)
	||
	(
		//session timeout
		$arPolicy["SESSION_TIMEOUT"]>0
		&& $kernelSession['SESS_TIME']>0
		&& $currTime-$arPolicy["SESSION_TIMEOUT"]*60 > $kernelSession['SESS_TIME']
	)
	||
	(
		//signed session
		isset($kernelSession["BX_SESSION_SIGN"])
		&& $kernelSession["BX_SESSION_SIGN"] <> bitrix_sess_sign()
	)
	||
	(
		//session manually expired, e.g. in $User->LoginHitByHash
		isSessionExpired()
	)
)
{
	$compositeSessionManager = $application->getCompositeSessionManager();
	$compositeSessionManager->destroy();

	$application->getSession()->setId(md5(uniqid(rand(), true)));
	$compositeSessionManager->start();

	$GLOBALS["USER"] = new CUser;
}
$kernelSession['SESS_IP'] = $_SERVER['REMOTE_ADDR'];
if (empty($kernelSession['SESS_TIME']))
{
	$kernelSession['SESS_TIME'] = $currTime;
}
elseif (($currTime - $kernelSession['SESS_TIME']) > 60)
{
	$kernelSession['SESS_TIME'] = $currTime;
}
if(!isset($kernelSession["BX_SESSION_SIGN"]))
{
	$kernelSession["BX_SESSION_SIGN"] = bitrix_sess_sign();
}

//session control from security module
if(
	(COption::GetOptionString("main", "use_session_id_ttl", "N") == "Y")
	&& (COption::GetOptionInt("main", "session_id_ttl", 0) > 0)
	&& !defined("BX_SESSION_ID_CHANGE")
)
{
	if(!isset($kernelSession['SESS_ID_TIME']))
	{
		$kernelSession['SESS_ID_TIME'] = $currTime;
	}
	elseif(($kernelSession['SESS_ID_TIME'] + COption::GetOptionInt("main", "session_id_ttl")) < $kernelSession['SESS_TIME'])
	{
		$compositeSessionManager = $application->getCompositeSessionManager();
		$compositeSessionManager->regenerateId();

		$kernelSession['SESS_ID_TIME'] = $currTime;
	}
}

define("BX_STARTED", true);

if (isset($kernelSession['BX_ADMIN_LOAD_AUTH']))
{
	define('ADMIN_SECTION_LOAD_AUTH', 1);
	unset($kernelSession['BX_ADMIN_LOAD_AUTH']);
}

if(!defined("NOT_CHECK_PERMISSIONS") || NOT_CHECK_PERMISSIONS!==true)
{
	$doLogout = isset($_REQUEST["logout"]) && (strtolower($_REQUEST["logout"]) == "yes");

	if($doLogout && $GLOBALS["USER"]->IsAuthorized())
	{
		$secureLogout = (\Bitrix\Main\Config\Option::get("main", "secure_logout", "N") == "Y");

		if(!$secureLogout || check_bitrix_sessid())
		{
			$GLOBALS["USER"]->Logout();
			LocalRedirect($GLOBALS["APPLICATION"]->GetCurPageParam('', array('logout', 'sessid')));
		}
	}

	// authorize by cookies
	if(!$GLOBALS["USER"]->IsAuthorized())
	{
		$GLOBALS["USER"]->LoginByCookies();
	}

	$arAuthResult = false;

	//http basic and digest authorization
	if(($httpAuth = $GLOBALS["USER"]->LoginByHttpAuth()) !== null)
	{
		$arAuthResult = $httpAuth;
		$GLOBALS["APPLICATION"]->SetAuthResult($arAuthResult);
	}

	//Authorize user from authorization html form
	//Only POST is accepted
	if(isset($_POST["AUTH_FORM"]) && $_POST["AUTH_FORM"] <> '')
	{
		$bRsaError = false;
		if(COption::GetOptionString('main', 'use_encrypted_auth', 'N') == 'Y')
		{
			//possible encrypted user password
			$sec = new CRsaSecurity();
			if(($arKeys = $sec->LoadKeys()))
			{
				$sec->SetKeys($arKeys);
				$errno = $sec->AcceptFromForm(['USER_PASSWORD', 'USER_CONFIRM_PASSWORD', 'USER_CURRENT_PASSWORD']);
				if($errno == CRsaSecurity::ERROR_SESS_CHECK)
					$arAuthResult = array("MESSAGE"=>GetMessage("main_include_decode_pass_sess"), "TYPE"=>"ERROR");
				elseif($errno < 0)
					$arAuthResult = array("MESSAGE"=>GetMessage("main_include_decode_pass_err", array("#ERRCODE#"=>$errno)), "TYPE"=>"ERROR");

				if($errno < 0)
					$bRsaError = true;
			}
		}

		if($bRsaError == false)
		{
			if(!defined("ADMIN_SECTION") || ADMIN_SECTION !== true)
				$USER_LID = SITE_ID;
			else
				$USER_LID = false;

			if($_POST["TYPE"] == "AUTH")
			{
				$arAuthResult = $GLOBALS["USER"]->Login($_POST["USER_LOGIN"], $_POST["USER_PASSWORD"], $_POST["USER_REMEMBER"]);
			}
			elseif($_POST["TYPE"] == "OTP")
			{
				$arAuthResult = $GLOBALS["USER"]->LoginByOtp($_POST["USER_OTP"], $_POST["OTP_REMEMBER"], $_POST["captcha_word"], $_POST["captcha_sid"]);
			}
			elseif($_POST["TYPE"] == "SEND_PWD")
			{
				$arAuthResult = CUser::SendPassword($_POST["USER_LOGIN"], $_POST["USER_EMAIL"], $USER_LID, $_POST["captcha_word"], $_POST["captcha_sid"], $_POST["USER_PHONE_NUMBER"]);
			}
			elseif($_POST["TYPE"] == "CHANGE_PWD")
			{
				$arAuthResult = $GLOBALS["USER"]->ChangePassword($_POST["USER_LOGIN"], $_POST["USER_CHECKWORD"], $_POST["USER_PASSWORD"], $_POST["USER_CONFIRM_PASSWORD"], $USER_LID, $_POST["captcha_word"], $_POST["captcha_sid"], true, $_POST["USER_PHONE_NUMBER"], $_POST["USER_CURRENT_PASSWORD"]);
			}
			elseif(COption::GetOptionString("main", "new_user_registration", "N") == "Y" && $_POST["TYPE"] == "REGISTRATION" && (!defined("ADMIN_SECTION") || ADMIN_SECTION !== true))
			{
				$arAuthResult = $GLOBALS["USER"]->Register($_POST["USER_LOGIN"], $_POST["USER_NAME"], $_POST["USER_LAST_NAME"], $_POST["USER_PASSWORD"], $_POST["USER_CONFIRM_PASSWORD"], $_POST["USER_EMAIL"], $USER_LID, $_POST["captcha_word"], $_POST["captcha_sid"], false, $_POST["USER_PHONE_NUMBER"]);
			}

			if($_POST["TYPE"] == "AUTH" || $_POST["TYPE"] == "OTP")
			{
				//special login form in the control panel
				if($arAuthResult === true && defined('ADMIN_SECTION') && ADMIN_SECTION === true)
				{
					//store cookies for next hit (see CMain::GetSpreadCookieHTML())
					$GLOBALS["APPLICATION"]->StoreCookies();
					$kernelSession['BX_ADMIN_LOAD_AUTH'] = true;

					// die() follows
					CMain::FinalActions('<script type="text/javascript">window.onload=function(){(window.BX || window.parent.BX).AUTHAGENT.setAuthResult(false);};</script>');
				}
			}
		}
		$GLOBALS["APPLICATION"]->SetAuthResult($arAuthResult);
	}
	elseif(!$GLOBALS["USER"]->IsAuthorized() && isset($_REQUEST['bx_hit_hash']))
	{
		//Authorize by unique URL
		$GLOBALS["USER"]->LoginHitByHash($_REQUEST['bx_hit_hash']);
	}
}

//logout or re-authorize the user if something importand has changed
$GLOBALS["USER"]->CheckAuthActions();

//magic short URI
if(defined("BX_CHECK_SHORT_URI") && BX_CHECK_SHORT_URI && CBXShortUri::CheckUri())
{
	//local redirect inside
	die();
}

//application password scope control
if(($applicationID = $GLOBALS["USER"]->GetParam("APPLICATION_ID")) !== null)
{
	$appManager = \Bitrix\Main\Authentication\ApplicationManager::getInstance();
	if($appManager->checkScope($applicationID) !== true)
	{
		$event = new \Bitrix\Main\Event("main", "onApplicationScopeError", Array('APPLICATION_ID' => $applicationID));
		$event->send();

		CHTTP::SetStatus("403 Forbidden");
		die();
	}
}

//define the site template
if(!defined("ADMIN_SECTION") || ADMIN_SECTION !== true)
{
	$siteTemplate = "";
	if(isset($_REQUEST["bitrix_preview_site_template"]) && is_string($_REQUEST["bitrix_preview_site_template"]) && $_REQUEST["bitrix_preview_site_template"] <> "" && $GLOBALS["USER"]->CanDoOperation('view_other_settings'))
	{
		//preview of site template
		$signer = new Bitrix\Main\Security\Sign\Signer();
		try
		{
			//protected by a sign
			$requestTemplate = $signer->unsign($_REQUEST["bitrix_preview_site_template"], "template_preview".bitrix_sessid());

			$aTemplates = CSiteTemplate::GetByID($requestTemplate);
			if($template = $aTemplates->Fetch())
			{
				$siteTemplate = $template["ID"];

				//preview of unsaved template
				if(isset($_GET['bx_template_preview_mode']) && $_GET['bx_template_preview_mode'] == 'Y' && $GLOBALS["USER"]->CanDoOperation('edit_other_settings'))
				{
					define("SITE_TEMPLATE_PREVIEW_MODE", true);
				}
			}
		}
		catch(\Bitrix\Main\Security\Sign\BadSignatureException $e)
		{
		}
	}
	if($siteTemplate == "")
	{
		$siteTemplate = CSite::GetCurTemplate();
	}
	define("SITE_TEMPLATE_ID", $siteTemplate);
	define("SITE_TEMPLATE_PATH", getLocalPath('templates/'.SITE_TEMPLATE_ID, BX_PERSONAL_ROOT));
}
else
{
	// prevents undefined constants
	define('SITE_TEMPLATE_ID', '.default');
	define('SITE_TEMPLATE_PATH', '/bitrix/templates/.default');
}

//magic parameters: show page creation time
if(isset($_GET["show_page_exec_time"]))
{
	if($_GET["show_page_exec_time"]=="Y" || $_GET["show_page_exec_time"]=="N")
		$kernelSession["SESS_SHOW_TIME_EXEC"] = $_GET["show_page_exec_time"];
}

//magic parameters: show included file processing time
if(isset($_GET["show_include_exec_time"]))
{
	if($_GET["show_include_exec_time"]=="Y" || $_GET["show_include_exec_time"]=="N")
		$kernelSession["SESS_SHOW_INCLUDE_TIME_EXEC"] = $_GET["show_include_exec_time"];
}

//magic parameters: show include areas
if(isset($_GET["bitrix_include_areas"]) && $_GET["bitrix_include_areas"] <> "")
	$GLOBALS["APPLICATION"]->SetShowIncludeAreas($_GET["bitrix_include_areas"]=="Y");

//magic sound
if($GLOBALS["USER"]->IsAuthorized())
{
	$cookie_prefix = COption::GetOptionString('main', 'cookie_name', 'BITRIX_SM');
	if(!isset($_COOKIE[$cookie_prefix.'_SOUND_LOGIN_PLAYED']))
		$GLOBALS["APPLICATION"]->set_cookie('SOUND_LOGIN_PLAYED', 'Y', 0);
}

//magic cache
\Bitrix\Main\Composite\Engine::shouldBeEnabled();

foreach(GetModuleEvents("main", "OnBeforeProlog", true) as $arEvent)
	ExecuteModuleEventEx($arEvent);

if((!defined("NOT_CHECK_PERMISSIONS") || NOT_CHECK_PERMISSIONS!==true) && (!defined("NOT_CHECK_FILE_PERMISSIONS") || NOT_CHECK_FILE_PERMISSIONS!==true))
{
	$real_path = $request->getScriptFile();

	if(!$GLOBALS["USER"]->CanDoFileOperation('fm_view_file', array(SITE_ID, $real_path)) || (defined("NEED_AUTH") && NEED_AUTH && !$GLOBALS["USER"]->IsAuthorized()))
	{
		/** @noinspection PhpUndefinedVariableInspection */
		if($GLOBALS["USER"]->IsAuthorized() && $arAuthResult["MESSAGE"] == '')
		{
			$arAuthResult = array("MESSAGE"=>GetMessage("ACCESS_DENIED").' '.GetMessage("ACCESS_DENIED_FILE", array("#FILE#"=>$real_path)), "TYPE"=>"ERROR");

			if(COption::GetOptionString("main", "event_log_permissions_fail", "N") === "Y")
			{
				CEventLog::Log("SECURITY", "USER_PERMISSIONS_FAIL", "main", $GLOBALS["USER"]->GetID(), $real_path);
			}
		}

		if(defined("ADMIN_SECTION") && ADMIN_SECTION==true)
		{
			if ($_REQUEST["mode"]=="list" || $_REQUEST["mode"]=="settings")
			{
				echo "<script>top.location='".$GLOBALS["APPLICATION"]->GetCurPage()."?".DeleteParam(array("mode"))."';</script>";
				die();
			}
			elseif ($_REQUEST["mode"]=="frame")
			{
				echo "<script type=\"text/javascript\">
					var w = (opener? opener.window:parent.window);
					w.location.href='".$GLOBALS["APPLICATION"]->GetCurPage()."?".DeleteParam(array("mode"))."';
				</script>";
				die();
			}
			elseif(defined("MOBILE_APP_ADMIN") && MOBILE_APP_ADMIN==true)
			{
				echo json_encode(Array("status"=>"failed"));
				die();
			}
		}

		/** @noinspection PhpUndefinedVariableInspection */
		$GLOBALS["APPLICATION"]->AuthForm($arAuthResult);
	}
}

/*ZDUyZmZNDQzZjA1MmQ4ODE2MTkzNGYwM2E2NDI1MWFmYWUyMGE=*/$GLOBALS['____1512356035']= array(base64_decode('bXR'.'fcmFuZA=='),base64_decode('ZXhw'.'bG'.'9kZQ=='),base64_decode(''.'cGFjaw=='),base64_decode('bWQ1'),base64_decode('Y'.'29'.'uc3RhbnQ='),base64_decode('a'.'GFzaF'.'9'.'o'.'b'.'WFj'),base64_decode('c3Ry'.'Y21w'),base64_decode(''.'aXNf'.'b2JqZWN0'),base64_decode('Y'.'2FsbF91c2VyX2Z1b'.'m'.'M='),base64_decode('Y2FsbF91'.'c2Vy'.'X2Z1bmM='),base64_decode('Y'.'2FsbF'.'91c2VyX2Z1bm'.'M='),base64_decode('Y2FsbF'.'91c2'.'VyX'.'2Z'.'1'.'bm'.'M='),base64_decode(''.'Y'.'2F'.'sb'.'F'.'91c2VyX2Z1bmM='),base64_decode('ZGV'.'maW5l'.'ZA='.'='),base64_decode(''.'c3RybG'.'Vu'));if(!function_exists(__NAMESPACE__.'\\___1397633016')){function ___1397633016($_1488127372){static $_1621696266= false; if($_1621696266 == false) $_1621696266=array('REI'.'=','U0'.'VMR'.'U'.'NUIF'.'ZBT'.'FVFIEZST00g'.'Yl9vc'.'HR'.'pb24gV'.'0hFUk'.'UgTk'.'FNR'.'T0nflB'.'B'.'U'.'kFNX01BW'.'F9VU'.'0V'.'SUyc'.'gQU5'.'EIE'.'1PRFVMRV9J'.'RD0nbWFpbicgQU5EIFN'.'JVEVf'.'SUQgS'.'VM'.'gTlVMTA'.'==','V'.'kFMVUU=','L'.'g==','SCo'.'=','Ym'.'l0'.'cml4',''.'TE'.'lDRU5TRV'.'9L'.'RVk=','c2hhMjU'.'2','VVNFUg'.'==','VV'.'NFUg'.'==','V'.'VNFUg==','SXNBdXRob'.'3Jp'.'emV'.'k','VVN'.'FU'.'g==','SX'.'NBZG1'.'pbg==','QVBQTE'.'lDQ'.'VRJT'.'04'.'=',''.'UmVzdGF'.'yd'.'E'.'J1'.'ZmZlcg==','TG9jYWxS'.'Z'.'WRpcmV'.'jdA'.'='.'=','L2'.'xpY2'.'V'.'uc2Vfc'.'mVzdHJpY3'.'Rp'.'b24ucGhw','XEJpd'.'HJpeFxNYWluX'.'ENvbm'.'Z'.'pZ'.'1xPcHR'.'p'.'b246OnN'.'ldA==','bWF'.'pbg==','UEFSQU'.'1fT'.'UFYX'.'1'.'VTRVJT','T0xEU'.'0'.'l'.'UR'.'U'.'V'.'Y'.'U'.'ElSRURBV'.'EU=','ZX'.'hwaX'.'J'.'l'.'X2'.'1lc3My');return base64_decode($_1621696266[$_1488127372]);}};if($GLOBALS['____1512356035'][0](round(0+0.5+0.5), round(0+5+5+5+5)) == round(0+3.5+3.5)){ $_1010192916= $GLOBALS[___1397633016(0)]->Query(___1397633016(1), true); if($_135089956= $_1010192916->Fetch()){ $_1221184063= $_135089956[___1397633016(2)]; list($_996136059, $_2103282923)= $GLOBALS['____1512356035'][1](___1397633016(3), $_1221184063); $_2103648099= $GLOBALS['____1512356035'][2](___1397633016(4), $_996136059); $_552963694= ___1397633016(5).$GLOBALS['____1512356035'][3]($GLOBALS['____1512356035'][4](___1397633016(6))); $_1716530112= $GLOBALS['____1512356035'][5](___1397633016(7), $_2103282923, $_552963694, true); if($GLOBALS['____1512356035'][6]($_1716530112, $_2103648099) !==(1492/2-746)){ if(isset($GLOBALS[___1397633016(8)]) && $GLOBALS['____1512356035'][7]($GLOBALS[___1397633016(9)]) && $GLOBALS['____1512356035'][8](array($GLOBALS[___1397633016(10)], ___1397633016(11))) &&!$GLOBALS['____1512356035'][9](array($GLOBALS[___1397633016(12)], ___1397633016(13)))){ $GLOBALS['____1512356035'][10](array($GLOBALS[___1397633016(14)], ___1397633016(15))); $GLOBALS['____1512356035'][11](___1397633016(16), ___1397633016(17), true);}}} else{ $GLOBALS['____1512356035'][12](___1397633016(18), ___1397633016(19), ___1397633016(20), round(0+2.4+2.4+2.4+2.4+2.4));}} while(!$GLOBALS['____1512356035'][13](___1397633016(21)) || $GLOBALS['____1512356035'][14](OLDSITEEXPIREDATE) <= min(216,0,72) || OLDSITEEXPIREDATE != SITEEXPIREDATE)die(GetMessage(___1397633016(22)));/**/       //Do not remove this

