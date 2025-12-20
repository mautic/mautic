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
        if (!$this->coreParametersHelper->get('auto_asset_tracking_enabled')) {
            return;
        }

        $event->appendJs($this->getAutoAssetTrackingJs(), 'Mautic Auto Asset Tracking');
    }

    private function getAutoAssetTrackingJs(): string
    {
        $downloadTrackUrl = str_replace(
            ['http://', 'https://'],
            '',
            $this->router->generate('mautic_asset_auto_track', [], UrlGeneratorInterface::ABSOLUTE_URL)
        );

        $extensions     = $this->coreParametersHelper->get('allowed_extensions');
        $extensionsJson = json_encode(array_values($extensions));

        return <<<JS

// MauticAssetBundle: Auto-track download file clicks
MauticJS.downloadTrackUrl = location.protocol + '//{$downloadTrackUrl}';
MauticJS.trackableExtensions = {$extensionsJson};

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

MauticJS.trackDownloadClick = function(e) {
    var link = e.target.closest('a');
    if (!link) return;

    var url = link.href;
    if (!MauticJS.isTrackableDownload(url)) return;

    e.preventDefault();

    var xhr = new XMLHttpRequest();
    xhr.open('POST', MauticJS.downloadTrackUrl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.trackingUrl) {
                        window.location.href = response.trackingUrl;
                        return;
                    }
                    if (response.skip) {
                        window.location.href = url;
                        return;
                    }
                } catch (err) {}
            }
            window.location.href = url;
        }
    };
    xhr.send('url=' + encodeURIComponent(url));
};

document.addEventListener('click', MauticJS.trackDownloadClick);
JS;
    }
}
