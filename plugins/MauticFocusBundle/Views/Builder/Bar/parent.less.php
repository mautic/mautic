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
<?php echo $view->render('MauticFocusBundle:Builder\Bar:animations.less.php'); ?>

.mf-bar-iframe {
    width: 100%;
    position: static;
    z-index: 20000;
    left: 0;
    right: 0;

    &.mf-animate {
        .barAnimate();
    }

    &.mf-bar-iframe-top {
        top: 0;
        margin-top: -100px;
    }

    &.mf-bar-iframe-bottom {
        bottom: 0;
        margin-bottom: -100px;
    }

    &.mf-bar-iframe-regular {
        body, html {
            min-height: 30px;
        }

        &.mf-bar-iframe-top {
            margin-top: -30px;
        }

        &.mf-bar-iframe-bottom {
            margin-bottom: -30px;
        }
    }

    &.mf-bar-iframe-large {
        body, html {
            min-height: 50px;
        }

        &.mf-bar-iframe-top {
            margin-top: -50px;
        }

        &.mf-bar-iframe-bottom {
            margin-bottom: -50px;
        }
    }

    &.mf-bar-iframe-sticky {
        position: fixed;
    }
}

<?php echo $view->render('MauticFocusBundle:Builder\Bar:shared.less.php'); ?>
<?php echo $view->render('MauticFocusBundle:Builder\Bar:collapser.less.php'); ?>

@media only screen and (max-width: 667px) {
    .mf-bar-collapser {
        display: none !important;
    }
}
