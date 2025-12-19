<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAnalyticsBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\BuildJsEvent;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use MauticPlugin\MauticAnalyticsBundle\Integration\Config;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class BuildJsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RouterInterface $router,
        private CoreParametersHelper $coreParametersHelper,
        private Config $config,
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
        if (!$this->config->isPublished()) {
            return;
        }

        $siteUrl = $this->coreParametersHelper->get('site_url');
        $scheme  = parse_url($siteUrl, PHP_URL_SCHEME) ?: 'https';

        // Always add dwell time tracking
        $js = $this->getDwellTimeTrackingJs($scheme);

        // Conditionally add auto asset tracking
        if ($this->config->isAutoAssetTrackingEnabled()) {
            $js .= $this->getAutoAssetTrackingJs($scheme);
        }

        $event->appendJs($js);
    }

    private function getDwellTimeTrackingJs(string $scheme): string
    {
        $pageLeaveUrl = str_replace(
            ['http://', 'https://'],
            '',
            $this->router->generate('mautic_analytics_page_leave', [], UrlGeneratorInterface::ABSOLUTE_URL)
        );

        return <<<JS

// MauticAnalyticsBundle: Page leave beacon for accurate dwell time tracking
MauticJS.pageLeaveUrl = (location.protocol == 'https:' ? 'https:' : '{$scheme}:') + '//{$pageLeaveUrl}';

MauticJS.getAnalyticsCookie = function(name) {
    var match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
    return match ? match[2] : null;
};

MauticJS.sendPageLeaveBeacon = function() {
    // Only send if hit cookie exists (server reads hit_id from cookie for security)
    if (!MauticJS.getAnalyticsCookie('mautic_referer_id')) {
        return;
    }

    // Allow multiple calls - each updates date_left to current time
    // So if user switches tabs and comes back, the final leave time is accurate
    if (navigator.sendBeacon) {
        navigator.sendBeacon(MauticJS.pageLeaveUrl, '');
    } else {
        // Fallback for older browsers - synchronous XHR (last resort)
        var xhr = new XMLHttpRequest();
        xhr.open('POST', MauticJS.pageLeaveUrl, false);
        try {
            xhr.send();
        } catch (e) {
            // Ignore errors during page unload
        }
    }
};

// Listen for page hide (navigation, tab close, browser close)
window.addEventListener('pagehide', function(e) {
    MauticJS.sendPageLeaveBeacon();
});

// Listen for visibility change (tab switch, minimize)
// Each leave updates date_left - if user returns and leaves again, final time wins
document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'hidden') {
        MauticJS.sendPageLeaveBeacon();
    }
});
JS;
    }

    private function getAutoAssetTrackingJs(string $scheme): string
    {
        $downloadTrackUrl = str_replace(
            ['http://', 'https://'],
            '',
            $this->router->generate('mautic_analytics_download_track', [], UrlGeneratorInterface::ABSOLUTE_URL)
        );

        // Use Mautic's configured allowed_extensions
        $extensions     = $this->coreParametersHelper->get('allowed_extensions');
        $extensionsJson = json_encode(array_values($extensions));

        return <<<JS

// MauticAnalyticsBundle: Auto-track download file clicks
MauticJS.downloadTrackUrl = (location.protocol == 'https:' ? 'https:' : '{$scheme}:') + '//{$downloadTrackUrl}';
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
    // Only check file extension - server handles duplicate/existing asset detection
    var ext = MauticJS.getFileExtension(url);
    return MauticJS.trackableExtensions.indexOf(ext) !== -1;
};

MauticJS.trackDownloadClick = function(e) {
    var link = e.target.closest('a');
    if (!link) return;

    var url = link.href;
    if (!MauticJS.isTrackableDownload(url)) return;

    // Prevent default navigation
    e.preventDefault();

    // Send tracking request
    var xhr = new XMLHttpRequest();
    xhr.open('POST', MauticJS.downloadTrackUrl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4) {
            if (xhr.status === 200) {
                try {
                    var response = JSON.parse(xhr.responseText);
                    if (response.trackingUrl) {
                        // Redirect to tracking URL (will then redirect to actual file)
                        window.location.href = response.trackingUrl;
                        return;
                    }
                    if (response.skip) {
                        // Server says skip (already tracked asset) - proceed to original URL
                        window.location.href = url;
                        return;
                    }
                } catch (err) {}
            }
            // Fallback: proceed to original URL
            window.location.href = url;
        }
    };
    xhr.send('url=' + encodeURIComponent(url));
};

// Attach click listener for download tracking
document.addEventListener('click', MauticJS.trackDownloadClick);
JS;
    }
}
