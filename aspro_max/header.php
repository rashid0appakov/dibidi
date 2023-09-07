<? if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED !== true) die(); ?>
<? if ($_GET["debug"] == "y")
    error_reporting(E_ERROR | E_PARSE);
IncludeTemplateLangFile(__FILE__);
global $APPLICATION, $arRegion, $arSite, $arTheme, $bIndexBot, $bIframeMode;
$arSite = CSite::GetByID(SITE_ID)->Fetch();
$htmlClass = ($_REQUEST && isset($_REQUEST['print']) ? 'print' : false);
$bIncludedModule = (\Bitrix\Main\Loader::includeModule("aspro.max"));
$filename1=$_SERVER["DOCUMENT_ROOT"]."/local/config/linkor.redirect/upload_urls.txt";
$sur=unserialize(file_get_contents($filename1));
 ?>
<?  $splitArGet = explode('?', $_SERVER['REQUEST_URI']);
$uncode=urldecode($splitArGet[0]);
if($sur[$uncode]){
    header("HTTP/1.1 301 Moved Permanently");
    header("Location: ".$sur[$uncode]);
    exit();
}
?>
    <!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?= LANGUAGE_ID ?>"
      lang="<?= LANGUAGE_ID ?>" <?= ($htmlClass ? 'class="' . $htmlClass . '"' : '') ?> <?= ($bIncludedModule ? CMax::getCurrentHtmlClass() : '') ?>>
    <head>
	<!-- Google Tag Manager -->
	<script data-skip-moving="true">(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
	new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
	j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
	'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
	})(window,document,'script','dataLayer','GTM-NVGBVSX');</script>
	<!-- End Google Tag Manager -->
        <title><? $APPLICATION->ShowTitle() ?></title>
        <? $APPLICATION->ShowMeta("viewport"); ?>
        <? $APPLICATION->ShowMeta("HandheldFriendly"); ?>
        <? $APPLICATION->ShowMeta("apple-mobile-web-app-capable", "yes"); ?>
        <? $APPLICATION->ShowMeta("apple-mobile-web-app-status-bar-style"); ?>
        <? $APPLICATION->ShowMeta("SKYPE_TOOLBAR"); ?>
        <? $APPLICATION->ShowHead(); ?>
        <? $APPLICATION->AddHeadString('<script>BX.message(' . CUtil::PhpToJSObject($MESS, false) . ')</script>', true); ?>
        <? if ($bIncludedModule)
            CMax::Start(SITE_ID); ?>
        <? include_once(str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'] . '/' . SITE_DIR . 'include/header_include/head.php')); ?>
        <meta name="format-detection" content="telephone=no">
        <meta http-equiv="x-rim-auto-match" content="none">
        <meta name="google-site-verification" content="c4VL-oqR2SQyMCUyIjLWRThEq2ImkfDa2N0wqGHbZFM"/>
        <meta name="yandex-verification" content="28e0d84b11a1e8f9"/>
        <meta name="yandex-verification" content="8cf2f1e236da9309"/>
        <meta name='wmail-verification' content='dec22d568d7c745463a2da5249bed207'/>

<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-147253511-1"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'UA-147253511-1');
</script>


        <script src="//code-ya.jivosite.com/widget/hqIahKKKtk" async></script>
        <!-- Yandex.Metrika counter -->

        <script type="text/javascript">

            (function (m, e, t, r, i, k, a) {
                m[i] = m[i] || function () {
                    (m[i].a = m[i].a || []).push(arguments)
                };

                m[i].l = 1 * new Date();
                k = e.createElement(t), a = e.getElementsByTagName(t)[0], k.async = 1, k.src = r, a.parentNode.insertBefore(k, a)
            })
            (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");
            ym(55174600, "init", {
                clickmap: true,
                trackLinks: true,
                accurateTrackBounce: true,
                webvisor: true,
                ecommerce: "dataLayer"
            });
        </script>



        <!-- /Yandex.Metrika counter -->

<script type="text/javascript">!function(){var t=document.createElement("script");t.type="text/javascript",t.async=!0,t.src="https://vk.com/js/api/openapi.js?163",t.onload=function(){VK.Retargeting.Init("VK-RTRG-443188-dxVZQ"),VK.Retargeting.Hit()},document.head.appendChild(t)}();</script><noscript><img src="https://vk.com/rtrg?p=VK-RTRG-443188-dxVZQ" style="position:fixed; left:-999px;" alt=""/></noscript>
<script type="text/javascript" src="https://vk.com/js/api/openapi.js?163"></script>


        <!-- Rating Mail.ru counter -->

        <script type="text/javascript">

            var _tmr = window._tmr || (window._tmr = []);

            _tmr.push({id: "3213337", type: "pageView", start: (new Date()).getTime(), pid: "USER_ID"});

            (function (d, w, id) {

                if (d.getElementById(id)) return;

                var ts = d.createElement("script");
                ts.type = "text/javascript";
                ts.async = true;
                ts.id = id;

                ts.src = "https://top-fwz1.mail.ru/js/code.js";

                var f = function () {
                    var s = d.getElementsByTagName("script")[0];
                    s.parentNode.insertBefore(ts, s);
                };

                if (w.opera == "[object Opera]") {
                    d.addEventListener("DOMContentLoaded", f, false);
                } else {
                    f();
                }

            })(document, window, "topmailru-code");

        </script>


        <!-- //Rating Mail.ru counter -->


        <!-- Rating@Mail.ru counter dynamic remarketing appendix -->

        <script type="text/javascript">

            var _tmr = _tmr || [];

            _tmr.push({

                type: 'itemView',

                productid: 'VALUE',

                pagetype: 'product',

                list: '3213337',

                totalvalue: 'VALUE'

            });

        </script>

        <!-- // Rating@Mail.ru counter dynamic remarketing appendix -->

        <!-- Global site tag (gtag.js) - Google Ads -->

        <script async src="https://www.googletagmanager.com/gtag/js?id=AW-687833919"></script>

        <script>

            window.dataLayer = window.dataLayer || [];

            function gtag() {
                dataLayer.push(arguments);
            }

            gtag('js', new Date());


            gtag('config', 'AW-687833919');

        </script>
        <script type="text/javascript">
var __cs = __cs || [];
__cs.push(["setCsAccount", "_FGVBhwzTE8V7xx1CQSxnc5g0MvTOpuk"]);
</script>
<script type="text/javascript" async src="https://app.comagic.ru/static/cs.min.js"></script>

    </head>
<? $bIndexBot = CMax::checkIndexBot(); ?>
<body class="<?= ($bIndexBot ? "wbot" : ""); ?> site_<?= SITE_ID ?> <?= ($bIncludedModule ? CMax::getCurrentBodyClass() : '') ?>"
      id="main" data-site="<?= SITE_DIR ?>">
	  
	<!-- Google Tag Manager (noscript) -->
	<noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-NVGBVSX"
	height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
	<!-- End Google Tag Manager (noscript) -->
    <noscript>
        <div><img src="https://mc.yandex.ru/watch/55174600" style="position:absolute; left:-9999px;" alt=""/></div>
    </noscript>
    <noscript>
        <div>

            <img src="https://top-fwz1.mail.ru/counter?id=3213337;js=na"
                 style="border:0;position:absolute;left:-9999px;" alt="Top.Mail.Ru"/>

        </div>
    </noscript>
<? if (!$bIncludedModule): ?>
    <? $APPLICATION->SetTitle(GetMessage("ERROR_INCLUDE_MODULE_ASPRO_MAX_TITLE")); ?>
    <center><? $APPLICATION->IncludeFile(SITE_DIR . "include/error_include_module.php"); ?></center></body></html><? die(); ?>
<? endif; ?>

<? include_once(str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'] . '/' . SITE_DIR . 'include/header_include/body_top.php')); ?>

<? $arTheme = $APPLICATION->IncludeComponent("aspro:theme.max", ".default", array("COMPONENT_TEMPLATE" => ".default"), false, array("HIDE_ICONS" => "Y")); ?>
<? include_once('defines.php'); ?>
<? CMax::SetJSOptions(); ?>

<? include_once(str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'] . '/' . SITE_DIR . 'include/header_include/under_wrapper1.php')); ?>
<div class="wrapper1 <?= ($isIndex && $isShowIndexLeftBlock ? "with_left_block" : ""); ?> <?= CMax::getCurrentPageClass(); ?> <? $APPLICATION->AddBufferContent(array('CMax', 'getCurrentThemeClasses')) ?>  ">
<? include_once(str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'] . '/' . SITE_DIR . 'include/header_include/top_wrapper1.php')); ?>

<div class="wraps hover_<?= $arTheme["HOVER_TYPE_IMG"]["VALUE"]; ?>" id="content">
<? include_once(str_replace('//', '/', $_SERVER['DOCUMENT_ROOT'] . '/' . SITE_DIR . 'include/header_include/top_wraps.php')); ?>

<? if ($isIndex): ?>
    <? $APPLICATION->ShowViewContent('front_top_big_banner'); ?>
    <div class="wrapper_inner front <?= ($isShowIndexLeftBlock ? "" : "wide_page"); ?> <?= $APPLICATION->ShowViewContent('wrapper_inner_class') ?>">
    <? elseif (!$isWidePage): ?>
    <div class="wrapper_inner <?= ($isHideLeftBlock ? "wide_page" : ""); ?> <?= $APPLICATION->ShowViewContent('wrapper_inner_class') ?>">
<? endif; ?>

<div class="container_inner clearfix <?= $APPLICATION->ShowViewContent('container_inner_class') ?>">
<? if (($isIndex && ($isShowIndexLeftBlock || $bActiveTheme)) || (!$isIndex && !$isHideLeftBlock)): ?>
    <div class="right_block <?= (defined("ERROR_404") ? "error_page" : ""); ?> wide_<?= CMax::ShowPageProps("HIDE_LEFT_BLOCK"); ?> <?= $APPLICATION->ShowViewContent('right_block_class') ?>">
<? endif; ?>
<div class="middle <?= ($is404 ? 'error-page' : ''); ?> <?= $APPLICATION->ShowViewContent('middle_class') ?>">
<? CMax::get_banners_position('CONTENT_TOP'); ?>
<? if (!$isIndex): ?>
    <div class="container">
    <? //h1?>
    <? if ($isHideLeftBlock && !$isWidePage): ?>
    <div class="maxwidth-theme">
<? endif; ?>
<? endif; ?>
<? CMax::checkRestartBuffer(); ?>