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

.mf-bar-spacer {
    display: block;
    overflow: hidden;
    position: relative;

    &.mf-bar-spacer-regular {
        height: 30px;
    }

    &.mf-bar-spacer-large {
        height: 50px;
    }
}

.mf-bar-collapser-icon {
    opacity: 0.3;
    text-decoration: none;
    transition-property: all;
    transition-duration: .5s;
    transition-timing-function: cubic-bezier(0, 1, 0.5, 1);

    &:hover {
        opacity: 0.7;
        text-decoration: none;
    }
}