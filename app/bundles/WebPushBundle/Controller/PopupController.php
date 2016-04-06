<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebPushBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\PageBundle\Entity\Page;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\PageEvents;
use Symfony\Component\HttpFoundation\Response;

class PopupController extends CommonController
{
    public function indexAction()
    {
        $response = $this->render('MauticWebPushBundle:Popup:index.html.php');

        $content = $response->getContent();

        /** @var \Mautic\CoreBundle\Templating\Helper\AssetsHelper $assetsHelper */
        $assetsHelper = $this->factory->getHelper('template.assets');
        $assetsHelper->addScript('/app/bundles/WebPushBundle/Assets/js/ua-parser.js', 'onPageDisplay_headClose');
        $assetsHelper->addScriptDeclaration($this->getJsEmbed(), 'onPageDisplay_headClose');

        $event = new PageDisplayEvent($content, new Page);
        $this->factory->getDispatcher()->dispatch(PageEvents::PAGE_ON_DISPLAY, $event);
        $content = $event->getContent();

        return $response->setContent($content);
    }

    protected function getJsEmbed()
    {
        return <<<JS
        var entityMap = {
            "&": "&amp;",
            "<": "&lt;",
            ">": "&gt;",
            '"': '&quot;',
            "'": '&#39;',
            "/": '&#x2F;'
        };

        function escapeHtml(string) {
            return String(string).replace(/[&<>"'\/]/g, function (s) {
                return entityMap[s];
            });
        }


        var SDK_VERSION_POSTMAM = 109163;
        var isHttpsPrompt = false;
        var isModal = false;
        var isPopup = true;
        var isPushEnabled = null;
        var isPermissionBlocked = null;
        var requestDomain = "*";
        if (requestDomain === '*') {
            requestDomain = "This website";
        }
        var notificationDomain = "dbhurley.onesignal.com";
        var subscription = null;

        window.addEventListener("load", function (event) {
            var domainNameElements = document.getElementsByClassName("domainName");
            for (var i = 0; i < domainNameElements.length; i++)
                domainNameElements[i].innerHTML = requestDomain;
        });

        /* ACCEPTING OR REJECTING NOTIFICATIONS */
        function continuePressed() {
            document.getElementById("black-wrapper").style.zIndex = "10";
            document.getElementById("black-wrapper").style.opacity = ".75";
            if (Number(OneSignal._VERSION) > SDK_VERSION_POSTMAM) {
                OneSignal.setSubscription(true)
                    .then(function () {
                        OneSignal.popupPostmam.postMessage(OneSignal.POSTMAM_COMMANDS.POPUP_ACCEPTED);
                        OneSignal._initPopup.apply(this.window);
                    });
            } else {
                setTimeout(function () {
                    OneSignal._initHttp({
                        appId: "ab44aea7-ebe8-4bf4-bb7c-aa47e22d0364",
                        subdomainName: "dbhurley",
                        origin: "*",
                        continuePressed: true
                    });
                }, 500);
                if (opener) {
                    opener.postMessage({
                        httpPromptAccepted: true,
                        from: OneSignal.environment.getEnv()
                    }, "*");
                }
            }
        }

        function rejectPrompt() {
            if (Number(OneSignal._VERSION) > SDK_VERSION_POSTMAM) {
                if (OneSignal.environment.isPopup()) {
                    OneSignal.popupPostmam.postMessage(OneSignal.POSTMAM_COMMANDS.POPUP_REJECTED);
                } else if (OneSignal.environment.isIframe()) {
                    OneSignal.iframePostmam.postMessage(OneSignal.POSTMAM_COMMANDS.POPUP_REJECTED);
                }
            } else {
                if (opener) {
                    opener.postMessage({
                        httpPromptCanceled: true,
                        from: OneSignal.environment.getEnv()
                    }, "*");
                }
            }
            window.close();
        }

        if (Number(OneSignal._VERSION) > SDK_VERSION_POSTMAM) {
            OneSignal._initHttp.apply(this.window, [{
                appId: "ab44aea7-ebe8-4bf4-bb7c-aa47e22d0364",
                subdomainName: "dbhurley",
                origin: "*",
                isPopup: isPopup,
                isModal: isModal
            }]);

            window.onunload = function () {
                if (OneSignal.environment.isPopup()) {
                    OneSignal.popupPostmam.postMessage(OneSignal.POSTMAM_COMMANDS.POPUP_CLOSING);
                } else if (OneSignal.environment.isIframe()) {
                    OneSignal.iframePostmam.postMessage(OneSignal.POSTMAM_COMMANDS.POPUP_CLOSING);
                }
            };
        }
JS;
    }
}