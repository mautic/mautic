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

.notificationTranslate(@percent) {
    -webkit-transform: translateX(@percent);
    -ms-transform: translateX(@percent);
    transform: translateX(@percent);
}

.notificationAnimate() {
    -webkit-animation-duration: 1s;
    animation-duration: 1s;
    -webkit-animation-timing-function: ease-in-out;
    animation-timing-function: ease-in-out;
}

.notificationName(@name) {
    -webkit-animation-name: @name;
    animation-name: @name;
}

.notificationSlideLeft() {
    0% {
        .notificationTranslate(150%);
    }
    50% {
        .notificationTranslate(-8%);
    }
    65% {
        .notificationTranslate(4%);
    }
    80% {
        .notificationTranslate(-4%);
    }
    95% {
        .notificationTranslate(2%);
    }
    100% {
        .notificationTranslate(0%);
    }
}

@-webkit-keyframes mf-notification-slide-left {
    .notificationSlideLeft;
}

@keyframes mf-notification-slide-left {
    .notificationSlideLeft;
}

.notificationSlideRight() {
    0% {
        .notificationTranslate(-150%);
    }
    50% {
        .notificationTranslate(8%);
    }
    65% {
        .notificationTranslate(-4%);
    }
    80% {
        .notificationTranslate(4%);
    }
    95% {
        .notificationTranslate(-2%);
    }
    100% {
        .notificationTranslate(0%);
    }
}

@-webkit-keyframes mf-notification-slide-right {
    .notificationSlideRight;
}

@keyframes mf-notification-slide-right {
    .notificationSlideRight;
}