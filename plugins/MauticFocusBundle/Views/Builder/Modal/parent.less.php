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

<?php echo $view->render('MauticFocusBundle:Builder\Modal:animations.less.php'); ?>
<?php echo $view->render('MauticFocusBundle:Builder\Modal:overlay.less.php'); ?>
.mf-modal-iframe {
    position: fixed;
    z-index: 21003;
    left: 50%;

    &.mf-animate {
        .modalAnimate();
    }

    &.mf-modal-iframe-top {
        top: 10px;
        margin-top: -100%;
        .modalTranslate(-50%, 0);

        &.mf-animate {
            .modalAnimateName(mf-modal-slide-down-top);
        }
    }

    &.mf-modal-iframe-middle {
        top: 50%;
        margin-top: -100%;
        .modalTranslate(-50%, -50%);

        &.mf-animate {
            .modalAnimateName(mf-modal-slide-down-middle);
        }
    }

    &.mf-modal-iframe-bottom {
        bottom: 10px;
        margin-bottom: -100%;
        .modalTranslate(-50%, 0);

        &.mf-animate {
            .modalAnimateName(mf-modal-slide-up-bottom);
        }
    }

    &.mf-loaded {
        margin-top: 0;
        margin-bottom: 0;
    }
}