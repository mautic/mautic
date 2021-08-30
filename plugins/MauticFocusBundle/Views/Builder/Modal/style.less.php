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

.mf-modal {
    position: fixed;
    opacity: 1;
    z-index: 2000;
    margin: auto;
    padding: 45px;
    border-radius: 4px;
    border-width: 6px 1px 1px 1px;
    border-style: solid;
    background: #fff;
    width: 40em;
    max-width: 40em;
    text-align: center;

    .mf-content {
        margin-bottom: 30px;

        .mf-headline {
            font-size: 1.6em;
            font-weight: 600;
        }

        .mf-tagline {
            font-size: 1.2em;
            font-weight: normal;
            margin-top: 4px;
        }

        a.mf-link {
            display: block;
            max-width: 70%;
            padding: 10px;
            margin: auto;
            font-size: 1.2em;
        }
    }

    .mf-modal-close {
        position: absolute;
        top: 0;
        right: 8px;

        a {
            font-size: 1.4em;
            color: #757575;
            opacity: .4;
            text-decoration: none;
        }

        a:hover {
            opacity: .8;
            text-decoration: none;
        }
    }

    .mauticform-input, .mauticform-row select, .mauticform-button {
        width: 75%;
        height: 35px;
        margin-bottom: 5px;
    }
}

.mf-responsive.mf-modal, .mf-responsive .mf-modal {
    width: 90%;
    padding: 10px;
}

<?php if (!empty($preview)): ?>
<?php echo $view->render('MauticFocusBundle:Builder\Modal:animations.less.php'); ?>
<?php echo $view->render('MauticFocusBundle:Builder\Modal:overlay.less.php'); ?>

.mf-modal, .mf-modal-overlay {
    position: absolute !important;
}

.mf-modal {
    z-index: 1023;
    left: 50%;
    &.mf-animate {
        .modalAnimate();
    }

    &.mf-modal-top {
        top: 10px;
        .modalTranslate(-50%, 0);

        &.mf-animate {
            .modalAnimateName(mf-modal-slide-down-top);
        }
    }

    &.mf-modal-middle {
        top: 50%;
        .modalTranslate(-50%, -50%);

        &.mf-animate {
            .modalAnimateName(mf-modal-slide-down-middle);
        }
    }

    &.mf-modal-bottom {
        bottom: 10px;
        .modalTranslate(-50%, 0);

        &.mf-animate {
            .modalAnimateName(mf-modal-slide-up-bottom);
        }
    }

}

.mf-modal-overlay {
    z-index: 1022;
}

<?php endif; ?>