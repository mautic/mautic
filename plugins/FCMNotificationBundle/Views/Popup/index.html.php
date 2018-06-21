<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <title><?php echo $view['slots']->get('pageTitle', $whitelabelBrandingName); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="icon" type="image/x-icon" href="<?php echo $view['assets']->getUrl($whitelabelBrandingFavicon) ?>" />
    <link rel="icon" sizes="192x192" href="<?php echo $view['assets']->getUrl($whitelabelBrandingFavicon) ?>">
    <link rel="apple-touch-icon" href="<?php echo $view['assets']->getUrl($whitelabelBrandingAppleFavicon) ?>" />

    <?php echo $view['assets']->outputStyles(); ?>
    <script src="/plugins/FCMNotificationBundle/Assets/js/popup/usparser.min.js" type="text/javascript"></script>
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
                <div class="title domainName"><?php echo $view['translator']->trans('mautic.plugin.fcmnotification.popup.title'); ?></div>
                <p id="mobile-directions"><?php echo $view['translator']->trans('mautic.plugin.fcmnotification.popup.subtitle'); ?></p>

                <div style="display: none;" id="mobile-notification">
                    <img id="mobile-notification-icon" src="<?php echo $icon; ?>" width="80" height="80">

                    <p id="mobile-notification-title" class="truncatable long desktop message"><?php echo $sampleNotificationTitle; ?></p>

                    <p id="mobile-notification-message" class="truncatable short desktop message"><?php echo $sampleNotificationText; ?><</p>

                    <p id="mobile-notification-url" class="truncatable short desktop message"><?php echo $siteUrl; ?></p>
                </div>

                <div id="desktop-notification">
                    <img id="desktop-notification-icon" src="<?php echo $icon; ?>" width="80" height="80">

                    <p id="x">x</p>

                    <p id="desktop-notification-title" class="truncatable mobile message"><?php echo $sampleNotificationTitle; ?><</p>

                    <p id="desktop-notification-message" class="truncatable mobile message"><?php echo $sampleNotificationText; ?><</p>

                    <p id="desktop-notification-url" class="truncatable mobile message"><?php echo $siteUrl; ?></p>
                </div>

                <p id="mobile-opt-out" class="truncatable opt-out message">(<?php echo $view['translator']->trans('mautic.plugin.fcmnotification.popup.unsubscribetext'); ?>)</p>
            </div>
        </div>
    </div>
</div>
<div id="error-box">
    <div id="error-message-padding">

        <!-- if on ios -->
        <div class="error" id="ios">
            <p> <?php echo $view['translator']->trans('mautic.plugin.fcmnotification.popup.error_ios'); ?> </p>
        </div>

        <!-- if not on chrome (Desktop) -->
        <div class="error" id="not-chrome-desktop">
            <p> <?php echo $view['translator']->trans('mautic.plugin.fcmnotification.popup.error_notsupported'); ?> </p>
        </div>

        <!-- if not on chrome (Android) -->
        <div class="error" id="not-chrome-Android">
            <p> <?php echo $view['translator']->trans('mautic.plugin.fcmnotification.popup.error_install_chrome'); ?></p>
        </div>

        <!-- not have latest version of chrome (desktop) -->
        <div class="error" id="outdated-chrome-desktop">
            <p> <?php echo $view['translator']->trans('mautic.plugin.fcmnotification.popup.error_update'); ?> </p>
        </div>

        <!-- not have latest version of chrome (mobile) -->
        <div class="error" id="outdated-chrome-mobile">
           <?php echo $view['translator']->trans('mautic.plugin.fcmnotification.popup.error_update'); ?>
        </div>

        <!-- if notifications are disabled (desktop)-->
        <div class="error" id="disabled-notifications-desktop">
            <p> <?php echo $view['translator']->trans('mautic.plugin.fcmnotification.popup.error_disabled_desktop'); ?> </p>
        </div>

        <!-- if notifications are disabled (mobile) -->
        <div class="error" id="disabled-notifications-mobile">
            <p> <?php echo $view['translator']->trans('mautic.plugin.fcmnotification.popup.error_disabled_mobile'); ?> </p>
        </div>

        <!-- if notifications are already enabled -->
        <div class="error" id="notifications-already-enabled">
            <p style="font-size: 12px"> <?php echo $view['translator']->trans('mautic.plugin.fcmnotification.popup.error_already_enabled', array("%siteUrl%" => $siteUrl)); ?> </p>
        </div>

    </div>
</div>
<script>
    (function(w,d,t,u,n,a,m){w['MauticTrackingObject']=n;
        w[n]=w[n]||function(){(w[n].q=w[n].q||[]).push(arguments)},a=d.createElement(t),
        m=d.getElementsByTagName(t)[0];a.src=u;m.parentNode.insertBefore(a,m)
    })(window,document,'script','/mtc.js','mt');    
</script>
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


    var timedStart = window.setInterval(function(){         
        if (typeof MauticJS != 'undefined'){
            window.clearInterval(timedStart);
            MauticJS.conditionalAsyncQueue(function(){                
                if ((browser == "Chrome" && parseInt(browser_version.substring(0, 2)) >= 50) || (browser == "Firefox" && parseInt(browser_version.substring(0, 2)) >= 44) || (browser == "Opera" && parseInt(browser_version.substring(0, 2)) >= 37));
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
                                    this.messaging.getToken().then(function(currentToken) {
                                        if (currentToken) {
                                          showError("notifications-already-enabled");
                                        } else {
                                          
                                        }
                                    }).catch(function(err) {
                                        console.log('An error occurred while retrieving token. ', err);                            
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
                        this.messaging.getToken().then(function(currentToken) {
                            if (currentToken) {
                              showError("notifications-already-enabled");
                            } else {
                              
                            }
                        }).catch(function(err) {
                            console.log('An error occurred while retrieving token. ', err);                            
                        });            
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
            }, function(){                
                return ((typeof firebase !== 'undefined' && firebase) && (typeof messaging !== 'undefined' && messaging))?true:false;
            });
        }    
    },1000);

</script>
</body></html>