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

.mf-bar {
    width: 100%;
    position: fixed;
    left: 0;
    right: 0;
    display: table;
    padding-left: 5px;
    padding-right: 5px;
    z-index: 20000;

    &.mf-bar-top {
        top: 0;
    }

    &.mf-bar-bottom {
        bottom: 0;
    }

    .mf-bar-collapse {
        width: 100px;
        display: table-cell;
        vertical-align: middle;
        line-height: 13px;
    }

    .mf-content {
        display: table-cell;
        vertical-align: middle;
        text-align: center;

        .mf-link {
            margin-left: 10px;
            padding: 2px 15px;
        }

        .mf-headline {
            display: inline-block;
        }
    }

    &.mf-bar-regular {
        height: 30px;
        font-size: 14px;

        &..mf-bar-top .mf-bar-collapser-icon svg {
            margin: 3px 0 0 0;
        }
        &..mf-bar-bottom .mf-bar-collapser-icon svg {
            margin: -3px 0 0 0;
        }

        .mauticform-input, select, .mauticform-button {
            padding: 3px 6px;
            font-size: 0.9em;
        }
    }

    &.mf-bar-large {
        height: 50px;
        font-size: 17px;

        &..mf-bar-top .mf-bar-collapser-icon svg {
            margin: 5px 0 0 0;
        }
        &..mf-bar-bottom .mf-bar-collapser-icon svg {
            margin: -5px 0 0 0;
        }

        .mf-link {
            font-size: 1em;
        }

        .mauticform-input, select, .mauticform-button {
            font-size: 1em;
        }
    }

    .mauticform-row, .mauticform-checkboxgrp-row, .mauticform-radiogrp-row {
        display: inline-block;
        margin-right: 3px;
    }

    .mauticform-row .mauticform-input, .mauticform-row select {
        color: #000000;
    }

    .mauticform-label {
        display: none;
    }

    .mauticform_wrapper {
        display: inline-block;
    }

    .mf-responsive {
        .mf-bar-collapse, .mf-bar-collapser {
            display: none !important;
        }
    }
}

<?php echo $view->render('MauticFocusBundle:Builder\Bar:collapser.less.php'); ?>

@media only screen and (max-width: 667px) {
    & .mf-bar-collapse, & .mf-bar-collapser {
        display: none !important;
    }
}

<?php if (!empty($preview)): ?>
<?php echo $view->render('MauticFocusBundle:Builder\Bar:animations.less.php'); ?>
<?php echo $view->render('MauticFocusBundle:Builder\Bar:shared.less.php'); ?>
.mf-bar {
    &.mf-animate {
        .barAnimate();
    }
}

.mf-bar, .mf-bar-collapser, .mf-bar-collapser-sticky {
    position: absolute !important;
}

<?php endif; ?>
