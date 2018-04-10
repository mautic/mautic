<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <title><?php echo $view['slots']->get('pageTitle', 'Mautic'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="icon" type="image/x-icon" href="<?php echo $view['assets']->getUrl('media/images/favicon.ico') ?>" />
    <link rel="icon" sizes="192x192" href="<?php echo $view['assets']->getUrl('media/images/favicon.ico') ?>">
    <link rel="apple-touch-icon" href="<?php echo $view['assets']->getUrl('media/images/apple-touch-icon.png') ?>" />

    <?php echo $view['assets']->outputStyles(); ?>
    <script src="/app/bundles/NotificationBundle/Assets/js/popup/usparser.min.js" type="text/javascript"></script>
</head>

<body>
<!-- Directions -->
<!-- overlay -->
<div id="black-wrapper">
</div>

<div id="white-wrapper">
</div>

<div id="mobile">

    <div id="mobile-top-section">
        <div id="mobile-top-section-wrapper">
            <div id="mobile-top-section-content">
                <div class="title domainName">This website</div>
                <p id="mobile-directions">wants to show notifications:</p>

                <div style="display: none;" id="mobile-notification">
                    <img id="mobile-notification-icon" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAFAAAABQCAYAAACOEfKtAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyhpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNi1jMDY3IDc5LjE1Nzc0NywgMjAxNS8wMy8zMC0yMzo0MDo0MiAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTUgKE1hY2ludG9zaCkiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6RDdBOEVEMjU3RTgwMTFFNUIzMjFCOUQ0QjUzN0Q0NDYiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6RDdBOEVEMjY3RTgwMTFFNUIzMjFCOUQ0QjUzN0Q0NDYiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDpDODdGODRDMDdEMTMxMUU1QjMyMUI5RDRCNTM3RDQ0NiIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDpEN0E4RUQyNDdFODAxMUU1QjMyMUI5RDRCNTM3RDQ0NiIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/Pttyb1cAAACySURBVHja7N2xDUMhDEBBO0rJ33++SNkAJKqET1YwTYp7hUV9smtyzvmKiCtUqecG/OzHg0Wp7w9ucCg3bN5hAAECBAhQAAECBCiAAAECFECAAAEKIECAAAUQIECAAggQIEABBAgQoAACBAhQZ4CZSe4EcK1FzgkDBAhQAAECBAhQAAECBCiAAAECFECAAAEKIECAAAUQIECAAggQIEABBPivgA1Dufbc4x2+w6jWbwEGAJZEES0DZiYyAAAAAElFTkSuQmCC">

                    <p id="mobile-notification-title" class="truncatable long desktop message">Example Notification</p>

                    <p id="mobile-notification-message" class="truncatable short desktop message">Notifications will appear on your device</p>

                    <p id="mobile-notification-url" class="truncatable short desktop message"><?php echo $siteUrl; ?></p>
                </div>

                <div id="desktop-notification">
                    <img id="desktop-notification-icon" src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAFAAAABQCAYAAACOEfKtAAAAGXRFWHRTb2Z0d2FyZQBBZG9iZSBJbWFnZVJlYWR5ccllPAAAAyhpVFh0WE1MOmNvbS5hZG9iZS54bXAAAAAAADw/eHBhY2tldCBiZWdpbj0i77u/IiBpZD0iVzVNME1wQ2VoaUh6cmVTek5UY3prYzlkIj8+IDx4OnhtcG1ldGEgeG1sbnM6eD0iYWRvYmU6bnM6bWV0YS8iIHg6eG1wdGs9IkFkb2JlIFhNUCBDb3JlIDUuNi1jMDY3IDc5LjE1Nzc0NywgMjAxNS8wMy8zMC0yMzo0MDo0MiAgICAgICAgIj4gPHJkZjpSREYgeG1sbnM6cmRmPSJodHRwOi8vd3d3LnczLm9yZy8xOTk5LzAyLzIyLXJkZi1zeW50YXgtbnMjIj4gPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9IiIgeG1sbnM6eG1wPSJodHRwOi8vbnMuYWRvYmUuY29tL3hhcC8xLjAvIiB4bWxuczp4bXBNTT0iaHR0cDovL25zLmFkb2JlLmNvbS94YXAvMS4wL21tLyIgeG1sbnM6c3RSZWY9Imh0dHA6Ly9ucy5hZG9iZS5jb20veGFwLzEuMC9zVHlwZS9SZXNvdXJjZVJlZiMiIHhtcDpDcmVhdG9yVG9vbD0iQWRvYmUgUGhvdG9zaG9wIENDIDIwMTUgKE1hY2ludG9zaCkiIHhtcE1NOkluc3RhbmNlSUQ9InhtcC5paWQ6RDdBOEVEMjU3RTgwMTFFNUIzMjFCOUQ0QjUzN0Q0NDYiIHhtcE1NOkRvY3VtZW50SUQ9InhtcC5kaWQ6RDdBOEVEMjY3RTgwMTFFNUIzMjFCOUQ0QjUzN0Q0NDYiPiA8eG1wTU06RGVyaXZlZEZyb20gc3RSZWY6aW5zdGFuY2VJRD0ieG1wLmlpZDpDODdGODRDMDdEMTMxMUU1QjMyMUI5RDRCNTM3RDQ0NiIgc3RSZWY6ZG9jdW1lbnRJRD0ieG1wLmRpZDpEN0E4RUQyNDdFODAxMUU1QjMyMUI5RDRCNTM3RDQ0NiIvPiA8L3JkZjpEZXNjcmlwdGlvbj4gPC9yZGY6UkRGPiA8L3g6eG1wbWV0YT4gPD94cGFja2V0IGVuZD0iciI/Pttyb1cAAACySURBVHja7N2xDUMhDEBBO0rJ33++SNkAJKqET1YwTYp7hUV9smtyzvmKiCtUqecG/OzHg0Wp7w9ucCg3bN5hAAECBAhQAAECBCiAAAECFECAAAEKIECAAAUQIECAAggQIEABBAgQoAACBAhQZ4CZSe4EcK1FzgkDBAhQAAECBAhQAAECBCiAAAECFECAAAEKIECAAAUQIECAAggQIEABBPivgA1Dufbc4x2+w6jWbwEGAJZEES0DZiYyAAAAAElFTkSuQmCC">

                    <p id="x">x</p>

                    <p id="desktop-notification-title" class="truncatable mobile message">This is an example notification</p>

                    <p id="desktop-notification-message" class="truncatable mobile message">Notifications will appear on your desktop</p>

                    <p id="desktop-notification-url" class="truncatable mobile message"><?php echo $siteUrl; ?></p>
                </div>

                <p id="mobile-opt-out" class="truncatable opt-out message">(you can unsubscribe anytime in your browser settings)</p>
            </div>
        </div>
    </div>
</div>
<div id="error-box">
    <div id="error-message-padding">

        <!-- if on ios -->
        <div class="error" id="ios">
            <p> Web Push Notifications are not supported by iOS. </p>
        </div>

        <!-- if not on chrome (Desktop) -->
        <div class="error" id="not-chrome-desktop">
            <p> Web Push Notifications are not supported by your browser. Please install
                <a class="default-link" href="https://www.google.com/chrome/browser/desktop" target="_blank">Chrome</a> to get
                notifications. </p>
        </div>

        <!-- if not on chrome (Android) -->
        <div class="error" id="not-chrome-Android">
            <p> Please install Chrome web browser to get notifications. </p>

            <p><a class="default-link" href="https://play.google.com/store/apps/details?id=com.android.chrome">Tap here</a> to
                download from the Google Play Store.</p>
        </div>

        <!-- not have latest version of chrome (desktop) -->
        <div class="error" id="outdated-chrome-desktop">
            <p> Please update your Chrome web browser to get notifications. </p>
        </div>

        <!-- not have latest version of chrome (mobile) -->
        <div class="error" id="outdated-chrome-mobile">
            <p> Please update your Chrome web browser to get notifications. </p>

            <p><a class="default-link" href="https://play.google.com/store/apps/details?id=com.android.chrome">Tap here</a> to
                download from the Google Play Store.</p>
        </div>

        <!-- if notifications are disabled (desktop)-->
        <div class="error" id="disabled-notifications-desktop">
            <p> Notifications are currently disabled.</p>

            <p>Please re-enable them by clicking on the lock icon in the top left of this window. </p>
        </div>

        <!-- if notifications are disabled (mobile) -->
        <div class="error" id="disabled-notifications-mobile">
            <p> Notifications are currently disabled.</p>

            <p>Please re-enable them by tapping on the lock icon on the top left. </p>
        </div>

        <!-- if notifications are already enabled -->
        <div class="error" id="notifications-already-enabled">
            <p> Notifications are already enabled, you may close this window. </p>

            <p style="font-size: 12px">If you would like to unsubscribe from all notifications from
                <span class="domainName"><?php echo $siteUrl; ?></span> click on the lock icon to the left of the address. </p>
        </div>

    </div>
</div>
<script>
    /* returns true if device is mobile or tablet */
    function detectmob() {
        return navigator.userAgent.match(/Android|webOS|iPhone|iPad|iPod|BlackBerry|Windows Phone/i) != null;
    }

    /* show mobile example notification on mobile, desktop notification on desktop */
    if (detectmob()) {
        document.getElementById("desktop-notification").style.display = 'none';
    } else {
        document.getElementById("mobile-notification").style.display = "none";
    }

    /* ERROR MESSAGES */

    /* instantiate parser */
    var parser = new UAParser();
    var isHttpsPrompt = false;

    // get the UA string result
    var result = parser.getResult();

    // get user agent info
    var browser = result.browser.name;
    var browser_version = result.browser.version;
    var os = result.os.name;
    var engine = result.engine.name;

    if (OneSignal.isPushNotificationsSupported());
    else if (os == "iOS")
        showError("ios");
    else if (browser != "Chrome") {
        if (os == "Android")
            showError("not-chrome-Android");
        else
            showError("not-chrome-desktop");
    } // TODO: Show generic error if SDK reports push notifications not supported.
    else { // They are on Chrome
        if (parseInt(browser_version.substring(0, 2)) < 42) // Check Chrome version
            showError(detectmob() ? "outdated-chrome-mobile" : "outdated-chrome-desktop");
        else if (isHttpsPrompt) {
            if (!isPushEnabled) {
                if (isPermissionBlocked)
                    showError(detectmob() ? "disabled-notifications-mobile" : "disabled-notifications-desktop");
            } else
                showError("notifications-already-enabled");
        } else { // HTTP
            if (Notification.permission == "denied") // Check if the Notification permission is disabled.
                showError(detectmob() ? "disabled-notifications-mobile" : "disabled-notifications-desktop");
            else if (Notification.permission == "granted") {
                navigator.serviceWorker.ready.then(function (event) {
                    if (event) {
                        OneSignal.getIdsAvailable(function (ids) {
                            if (ids.registrationId != null) {
                                OneSignal._getSubscription(function (isSet) {
                                    if (isSet)
                                        showError("notifications-already-enabled");
                                });
                            }
                        });
                    }
                });
            }
        }
    }

    if (!isHttpsPrompt) {
        if (Notification.permission == "denied") // Check if the Notification permission is disabled.
            showError(detectmob() ? "disabled-notifications-mobile" : "disabled-notifications-desktop");
        else if (Notification.permission == "granted") {
            OneSignal._initOptions = {};
            (OneSignal.isPushNotificationsEnabled(function (enabled) {
                if (enabled) {
                    showError("notifications-already-enabled");
                }
            }));
        }
    } else {
        if (isPermissionBlocked) // Check if the Notification permission is disabled.
            showError(detectmob() ? "disabled-notifications-mobile" : "disabled-notifications-desktop");
        else if (isPushEnabled) {
            showError("notifications-already-enabled");
        }
    }

    function showError(error) {
        // put a white overlay over all existing content
        // this also disables all functionality
        document.getElementById("white-wrapper").style.zIndex = "10";
        document.getElementById("white-wrapper").style.opacity = ".75";
        document.getElementById("error-box").style.opacity = "1";
        document.getElementById("error-box").style.display = "block";
        document.getElementById(error).style.display = "block";
    }
</script>
</body></html>