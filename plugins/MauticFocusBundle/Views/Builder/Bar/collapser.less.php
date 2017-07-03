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

.mf-bar-collapser {
    position: absolute;
    right: 3px;
    width: 24px;
    height: 24px;
    text-align: center;
    z-index: 21000;

    &.mf-bar-collapser-top {
        top: 0;
        border-bottom-right-radius: 4px;
        border-bottom-left-radius: 4px;

        .mf-bar-collapser-icon svg {
            margin: 2px 0 0 0;
        }
    }

    &.mf-bar-collapser-bottom {
        bottom: 0;
        border-top-right-radius: 4px;
        border-top-left-radius: 4px;

        .mf-bar-collapser-icon svg {
            margin: -2px 0 0 0;
        }
    }

    &.mf-bar-collapser-large {
        width: 40px;
        height: 40px;

        &.mf-bar-collapser-top .mf-bar-collapser-icon svg {
            margin: 4px 0 0 0;
        }

        &.mf-bar-collapser-bottom .mf-bar-collapser-icon svg {
            margin: -4px 0 0 0;
        }
    }

    &.mf-bar-collapser-sticky {
        position: fixed;
    }

    &.mf-bar-collapser-top, &.mf-bar-collapser-bottom {
        &.mf-bar-collapsed .mf-bar-collapser-icon svg {
            margin: 0;
        }
    }

    a.mf-bar-collapser-icon {
        position: relative;
        display: inline-block;

        &:after {
            content: "";
            position: absolute;
            top: 0;
            right: 0;
            bottom: 0;
            left: 0;
        }
    }
}

