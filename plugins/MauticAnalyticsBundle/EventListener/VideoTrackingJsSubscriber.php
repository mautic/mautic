<?php

declare(strict_types=1);

namespace MauticPlugin\MauticAnalyticsBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\BuildJsEvent;
use MauticPlugin\MauticAnalyticsBundle\Integration\Config;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class VideoTrackingJsSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private RouterInterface $router,
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

        if (!$this->config->isAutoVideoTrackingEnabled()) {
            return;
        }
        $videoTrackUrl = $this->router->generate('mautic_analytics_video_track', [], UrlGeneratorInterface::ABSOLUTE_URL);
        $scheme        = parse_url($videoTrackUrl, PHP_URL_SCHEME) ?: 'https';
        $videoTrackUrl = str_replace(['http://', 'https://'], '', $videoTrackUrl);

        $js = <<<JS

// MauticAnalyticsBundle: Auto-track video views (HTML5, YouTube, Vimeo)
MauticJS.videoTrackUrl = (location.protocol == 'https:' ? 'https:' : '{$scheme}:') + '//{$videoTrackUrl}';
MauticJS.trackedVideos = {};
MauticJS.videoTrackingInterval = 5000; // Send update every 5 seconds during playback

MauticJS.generateVideoGuid = function() {
    return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
        var r = Math.random() * 16 | 0, v = c == 'x' ? r : (r & 0x3 | 0x8);
        return v.toString(16);
    });
};

MauticJS.sendVideoTrack = function(videoData) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', MauticJS.videoTrackUrl, true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    var params = 'guid=' + encodeURIComponent(videoData.guid) +
                 '&url=' + encodeURIComponent(videoData.url) +
                 '&total_watched=' + Math.floor(videoData.timeWatched) +
                 '&duration=' + Math.floor(videoData.duration);

    xhr.send(params);
};

// HTML5 Video Tracking
MauticJS.initHtml5VideoTracking = function() {
    var videos = document.querySelectorAll('video');

    videos.forEach(function(video) {
        if (video.dataset.mauticTracked) return;
        video.dataset.mauticTracked = 'true';

        var videoUrl = video.currentSrc || video.src || video.querySelector('source')?.src || window.location.href;
        var guid = MauticJS.generateVideoGuid();

        MauticJS.trackedVideos[guid] = {
            guid: guid,
            url: videoUrl,
            timeWatched: 0,
            duration: 0,
            lastUpdate: 0,
            playing: false,
            intervalId: null
        };

        video.dataset.mauticGuid = guid;

        video.addEventListener('loadedmetadata', function() {
            MauticJS.trackedVideos[guid].duration = video.duration || 0;
        });

        video.addEventListener('play', function() {
            var data = MauticJS.trackedVideos[guid];
            data.playing = true;
            data.duration = video.duration || 0;

            // Start periodic tracking
            if (!data.intervalId) {
                data.intervalId = setInterval(function() {
                    if (data.playing) {
                        data.timeWatched = video.currentTime;
                        MauticJS.sendVideoTrack(data);
                    }
                }, MauticJS.videoTrackingInterval);
            }
        });

        video.addEventListener('pause', function() {
            var data = MauticJS.trackedVideos[guid];
            data.playing = false;
            data.timeWatched = video.currentTime;
            MauticJS.sendVideoTrack(data);
        });

        video.addEventListener('ended', function() {
            var data = MauticJS.trackedVideos[guid];
            data.playing = false;
            data.timeWatched = video.duration;
            MauticJS.sendVideoTrack(data);

            if (data.intervalId) {
                clearInterval(data.intervalId);
                data.intervalId = null;
            }
        });
    });
};

// YouTube Tracking (via YouTube IFrame API)
MauticJS.youtubeApiReady = false;
MauticJS.youtubeApiLoading = false;
MauticJS.youtubePendingIframes = [];

MauticJS.initYouTubeTracking = function() {
    var iframes = document.querySelectorAll('iframe[src*="youtube.com/embed"], iframe[src*="youtube-nocookie.com/embed"]');
    if (iframes.length === 0) return;

    iframes.forEach(function(iframe) {
        if (iframe.dataset.mauticTracked) return;
        iframe.dataset.mauticTracked = 'true';

        var videoUrl = iframe.src;
        var guid = MauticJS.generateVideoGuid();

        // Enable JS API if not already enabled
        var newSrc = videoUrl;
        if (newSrc.indexOf('enablejsapi=1') === -1) {
            newSrc = newSrc + (newSrc.indexOf('?') > -1 ? '&' : '?') + 'enablejsapi=1';
        }
        if (newSrc.indexOf('origin=') === -1) {
            newSrc = newSrc + '&origin=' + encodeURIComponent(window.location.origin);
        }
        if (newSrc !== videoUrl) {
            iframe.src = newSrc;
        }

        // Ensure iframe has an ID for YouTube API
        if (!iframe.id) {
            iframe.id = 'mautic-yt-' + guid;
        }

        MauticJS.trackedVideos[guid] = {
            guid: guid,
            url: videoUrl,
            timeWatched: 0,
            duration: 0,
            playing: false,
            intervalId: null,
            iframe: iframe,
            player: null
        };

        iframe.dataset.mauticGuid = guid;
        MauticJS.youtubePendingIframes.push({iframe: iframe, guid: guid});
    });

    // Load YouTube IFrame API if not already loaded
    if (!MauticJS.youtubeApiReady && !MauticJS.youtubeApiLoading && typeof YT === 'undefined') {
        MauticJS.youtubeApiLoading = true;
        var tag = document.createElement('script');
        tag.src = 'https://www.youtube.com/iframe_api';
        var firstScriptTag = document.getElementsByTagName('script')[0];
        firstScriptTag.parentNode.insertBefore(tag, firstScriptTag);
    } else if (typeof YT !== 'undefined' && YT.Player) {
        MauticJS.onYouTubeIframeAPIReady();
    }
};

// Called by YouTube API when ready
window.onYouTubeIframeAPIReady = function() {
    MauticJS.youtubeApiReady = true;
    MauticJS.onYouTubeIframeAPIReady();
};

MauticJS.onYouTubeIframeAPIReady = function() {
    if (typeof YT === 'undefined' || !YT.Player) return;

    MauticJS.youtubePendingIframes.forEach(function(item) {
        var iframe = item.iframe;
        var guid = item.guid;
        var videoData = MauticJS.trackedVideos[guid];
        if (!videoData || videoData.player) return;

        try {
            videoData.player = new YT.Player(iframe.id, {
                events: {
                    'onStateChange': function(event) {
                        MauticJS.onYouTubeStateChange(guid, event);
                    },
                    'onReady': function(event) {
                        videoData.duration = event.target.getDuration() || 0;
                    }
                }
            });
        } catch (e) {
            // Fallback: iframe might not be compatible
        }
    });
    MauticJS.youtubePendingIframes = [];
};

MauticJS.onYouTubeStateChange = function(guid, event) {
    var videoData = MauticJS.trackedVideos[guid];
    if (!videoData) return;

    var player = videoData.player;
    if (!player || typeof player.getCurrentTime !== 'function') return;

    // Update duration if available
    if (typeof player.getDuration === 'function') {
        videoData.duration = player.getDuration() || videoData.duration;
    }

    // State: -1 unstarted, 0 ended, 1 playing, 2 paused, 3 buffering, 5 cued
    if (event.data === 1) { // Playing
        videoData.playing = true;
        if (!videoData.intervalId) {
            videoData.intervalId = setInterval(function() {
                if (videoData.playing && player && typeof player.getCurrentTime === 'function') {
                    videoData.timeWatched = player.getCurrentTime();
                    MauticJS.sendVideoTrack(videoData);
                }
            }, MauticJS.videoTrackingInterval);
        }
    } else if (event.data === 2 || event.data === 0) { // Paused or Ended
        videoData.playing = false;
        if (player && typeof player.getCurrentTime === 'function') {
            videoData.timeWatched = player.getCurrentTime();
        }
        MauticJS.sendVideoTrack(videoData);
        if (event.data === 0 && videoData.intervalId) {
            clearInterval(videoData.intervalId);
            videoData.intervalId = null;
        }
    }
};

// Vimeo Tracking (via postMessage API)
MauticJS.initVimeoTracking = function() {
    var iframes = document.querySelectorAll('iframe[src*="player.vimeo.com"]');

    iframes.forEach(function(iframe) {
        if (iframe.dataset.mauticTracked) return;
        iframe.dataset.mauticTracked = 'true';

        var videoUrl = iframe.src;
        var guid = MauticJS.generateVideoGuid();

        MauticJS.trackedVideos[guid] = {
            guid: guid,
            url: videoUrl,
            timeWatched: 0,
            duration: 0,
            playing: false,
            intervalId: null,
            iframe: iframe
        };

        iframe.dataset.mauticGuid = guid;

        // Request player ready
        iframe.contentWindow.postMessage(JSON.stringify({method: 'addEventListener', value: 'play'}), '*');
        iframe.contentWindow.postMessage(JSON.stringify({method: 'addEventListener', value: 'pause'}), '*');
        iframe.contentWindow.postMessage(JSON.stringify({method: 'addEventListener', value: 'finish'}), '*');
        iframe.contentWindow.postMessage(JSON.stringify({method: 'addEventListener', value: 'playProgress'}), '*');
        iframe.contentWindow.postMessage(JSON.stringify({method: 'getDuration'}), '*');
    });

    // Listen for Vimeo postMessage events
    window.addEventListener('message', function(event) {
        if (!event.origin.match(/vimeo\.com$/)) return;
        if (!event.data || typeof event.data !== 'string') return;

        try {
            var data = JSON.parse(event.data);

            var iframes = document.querySelectorAll('iframe[data-mautic-guid][src*="vimeo"]');
            iframes.forEach(function(iframe) {
                if (event.source !== iframe.contentWindow) return;

                var guid = iframe.dataset.mauticGuid;
                var videoData = MauticJS.trackedVideos[guid];
                if (!videoData) return;

                if (data.event === 'ready') {
                    iframe.contentWindow.postMessage(JSON.stringify({method: 'getDuration'}), '*');
                }

                if (data.method === 'getDuration' && data.value) {
                    videoData.duration = data.value;
                }

                if (data.event === 'play') {
                    videoData.playing = true;
                    if (!videoData.intervalId) {
                        videoData.intervalId = setInterval(function() {
                            if (videoData.playing) {
                                MauticJS.sendVideoTrack(videoData);
                            }
                        }, MauticJS.videoTrackingInterval);
                    }
                }

                if (data.event === 'playProgress' && data.data) {
                    videoData.timeWatched = data.data.seconds || 0;
                    videoData.duration = data.data.duration || videoData.duration;
                }

                if (data.event === 'pause' || data.event === 'finish') {
                    videoData.playing = false;
                    MauticJS.sendVideoTrack(videoData);
                    if (data.event === 'finish' && videoData.intervalId) {
                        clearInterval(videoData.intervalId);
                        videoData.intervalId = null;
                    }
                }
            });
        } catch (e) {}
    });
};

// Send final video data on page leave
MauticJS.sendVideoLeaveBeacon = function() {
    for (var guid in MauticJS.trackedVideos) {
        var data = MauticJS.trackedVideos[guid];
        if (data.timeWatched > 0) {
            // Use sendBeacon for reliable delivery during page unload
            if (navigator.sendBeacon) {
                var formData = new FormData();
                formData.append('guid', data.guid);
                formData.append('url', data.url);
                formData.append('total_watched', Math.floor(data.timeWatched));
                formData.append('duration', Math.floor(data.duration));
                navigator.sendBeacon(MauticJS.videoTrackUrl, formData);
            }
        }
    }
};

// Initialize video tracking
MauticJS.initVideoTracking = function() {
    MauticJS.initHtml5VideoTracking();
    MauticJS.initYouTubeTracking();
    MauticJS.initVimeoTracking();
};

// Run on DOM ready and observe for dynamic content
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', MauticJS.initVideoTracking);
} else {
    MauticJS.initVideoTracking();
}

// Re-scan for videos added dynamically
var videoObserver = new MutationObserver(function(mutations) {
    MauticJS.initVideoTracking();
});
videoObserver.observe(document.body, { childList: true, subtree: true });

// Send final data on page leave
window.addEventListener('pagehide', MauticJS.sendVideoLeaveBeacon);
document.addEventListener('visibilitychange', function() {
    if (document.visibilityState === 'hidden') {
        MauticJS.sendVideoLeaveBeacon();
    }
});
JS;

        $event->appendJs($js, 'Mautic Video Tracking');
    }
}
