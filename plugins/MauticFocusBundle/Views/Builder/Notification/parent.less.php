<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<?php echo $view->render('MauticFocusBundle:Builder\Notification:animations.less.php'); ?>

.mf-notification-iframe {
    position: fixed;
    z-index: 21001;
    margin-top: -100%;

    &.mf-loaded {
        margin-top: 0;
        margin-bottom: 0;


        &.mf-animate {
            .notificationAnimate();
        }

        &.mf-notification-iframe-top-left {
            top: 5px;
            left: 5px;

            &.mf-animate {
                .notificationName(mf-notification-slide-right);
            }
        }

        &.mf-notification-iframe-top-right {
            top: 5px;
            right: 5px;

            &.mf-animate {
                .notificationName(mf-notification-slide-left);
            }
        }

        &.mf-notification-iframe-bottom-left {
            bottom: 5px;
            left: 5px;

            &.mf-animate {
                .notificationName(mf-notification-slide-right);
            }
        }

        &.mf-notification-iframe-bottom-right {
            bottom: 5px;
            right: 5px;

            &.mf-animate {
                .notificationName(mf-notification-slide-left);
            }
        }

        &.mf-responsive {
            left: 0 !important;
            right: 0 !important;
        }
    }
}

