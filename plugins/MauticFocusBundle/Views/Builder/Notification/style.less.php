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

.mf-notification {
    position: fixed;
    opacity: 1;
    z-index: 2000;
    margin: auto;
    background: #fff;
    border-radius: 4px;
    border-width: 6px 1px 1px 1px;
    border-style: solid;
    min-height: 8em;
    padding: 10px 20px;
    width: 350px;

    .mf-content {
        margin-bottom: 30px;

        .mf-headline {
            font-size: 1.2em;
            font-weight: 600;
        }

        .mf-tagline {
            font-size: 1em;
            font-weight: normal;
            margin-top: 4px;
        }
    }

    .mf-notification-close {
        position: absolute;
        top: 0;
        right: 8px;

        a {
            font-size: 1em;
            color: #757575;
            opacity: .4;
            text-decoration: none;

            &:hover {
                opacity: .8;
                text-decoration: none;
            }
        }
    }

    .mauticform-input, .mauticform-row select, .mauticform-button {
        width: 100%;
        height: 28px;
        margin-bottom: 2px;
    }
}

.mf-responsive.mf-notification, .mf-responsive .mf-notification {
    width: 90%;
    padding: 10px;
    left: 0;
    right: 0;
}

<?php if (!empty($preview)): ?>
.mf-notification {
    position: absolute !important;

    &.mf-animate {
        .notificationAnimate();
    }

    &.mf-notification-top-left {
        top: 5px;
        left: 5px;

        &.mf-animate {
            .notificationName(mf-notification-slide-right);
        }
    }

    &.mf-notification-top-right {
        top: 5px;
        right: 5px;

        &.mf-animate {
            .notificationName(mf-notification-slide-left);
        }
    }

    &.mf-notification-bottom-left {
        bottom: 5px;
        left: 5px;

        &.mf-animate {
            .notificationName(mf-notification-slide-right);
        }
    }

    &.mf-notification-bottom-right {
        bottom: 5px;
        right: 5px;

        &.mf-animate {
            .notificationName(mf-notification-slide-left);
        }
    }
}

<?php echo $view->render('MauticFocusBundle:Builder\Notification:animations.less.php'); ?>
<?php endif; ?>
