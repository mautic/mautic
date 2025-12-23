<?php

declare(strict_types=1);

namespace Mautic\AssetBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\BuildJsEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class AutoTrackingJsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RouterInterface $router,
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::BUILD_MAUTIC_JS => ['onBuildJs', 0],
        ];
    }

    public function onBuildJs(BuildJsEvent $event): void
    {
        $event->appendJs($this->getAutoAssetTrackingJs(), 'Mautic Auto Asset Tracking');
    }

    private function getAutoAssetTrackingJs(): string
    {
        $downloadTrackUrl = str_replace(
            ['http://', 'https://'],
            '',
            $this->router->generate('mautic_asset_auto_track', [], UrlGeneratorInterface::ABSOLUTE_URL)
        );

        $extensions       = $this->coreParametersHelper->get('allowed_extensions');
        $extensionsJson   = json_encode(array_values($extensions));
        $autoTrackEnabled = $this->coreParametersHelper->get('auto_asset_tracking_enabled') ? 'true' : 'false';

        return <<<JS

// MauticAssetBundle: Auto-track download file clicks
MauticJS.downloadTrackUrl = location.protocol + '//{$downloadTrackUrl}';
MauticJS.trackableExtensions = {$extensionsJson};
MauticJS.autoAssetTrackingEnabled = {$autoTrackEnabled};

MauticJS.getFileExtension = function(url) {
    try {
        var path = new URL(url).pathname;
        var ext = path.split('.').pop().toLowerCase();
        return ext;
    } catch (e) {
        return '';
    }
};

MauticJS.isTrackableDownload = function(url) {
    if (!url) return false;
    var ext = MauticJS.getFileExtension(url);
    return MauticJS.trackableExtensions.indexOf(ext) !== -1;
};

MauticJS.shouldTrackLink = function(link) {
    // Check for explicit opt-out: data-mautic-ignore
    if (link.hasAttribute('data-mautic-ignore')) {
        return false;
    }

    // Check for explicit opt-in: data-mautic-track
    if (link.hasAttribute('data-mautic-track')) {
        return true;
    }

    // If auto-tracking is disabled, only track explicit opt-in links
    if (!MauticJS.autoAssetTrackingEnabled) {
        return false;
    }

    // Auto-tracking is enabled, check if it's a trackable file type
    return MauticJS.isTrackableDownload(link.href);
};

MauticJS.isNewTabClick = function(e, link) {
    if (e.ctrlKey || e.metaKey || e.shiftKey || e.button !== 0) return true;
    var target = link.getAttribute('target');
    return target && target !== '_self';
};

MauticJS.navigateTo = function(url, openInNewTab) {
    if (openInNewTab) {
        window.open(url, '_blank');
    } else {
        window.location.href = url;
    }
};

MauticJS.trackDownloadClick = function(e) {
    var link = e.target.closest('a');
    if (!link || !link.href) return;

    if (!MauticJS.shouldTrackLink(link)) return;

    e.preventDefault();

    var url = link.href;
    var openInNewTab = MauticJS.isNewTabClick(e, link);
    var forceTrack = link.hasAttribute('data-mautic-track') ? '1' : '0';
    var customTitle = link.getAttribute('data-mautic-track-title') || '';
    var xhr = new XMLHttpRequest();
    xhr.open('POST', MauticJS.downloadTrackUrl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.timeout = 10000;
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.trackingUrl) {
                        MauticJS.navigateTo(response.trackingUrl, openInNewTab);
                        return;
                    }
                    if (response.skip) {
                        MauticJS.navigateTo(url, openInNewTab);
                        return;
                    }
                } catch (err) {
                    // JSON parse failed, fall through to redirect
                }
            }
            MauticJS.navigateTo(url, openInNewTab);
        }
    };
    xhr.onerror = function() {
        MauticJS.navigateTo(url, openInNewTab);
    };
    xhr.ontimeout = function() {
        MauticJS.navigateTo(url, openInNewTab);
    };
    var params = 'url=' + encodeURIComponent(url) + '&force=' + forceTrack;
    if (customTitle) {
        params += '&title=' + encodeURIComponent(customTitle);
    }
    xhr.send(params);
};

document.addEventListener('click', MauticJS.trackDownloadClick);
JS;
    }
}
