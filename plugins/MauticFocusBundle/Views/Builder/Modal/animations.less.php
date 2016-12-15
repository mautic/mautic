<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
.modalTranslate(@x; @y) {
    -webkit-transform: translate(@x, @y);
    -ms-transform: translate(@x, @y);
    transform: translate(@x, @y);
}

.modalAnimate() {
    -webkit-animation-fill-mode: forwards;
    animation-fill-mode: forwards;
    -webkit-animation-duration: 0.3s;
    animation-duration: 0.3s;
    -webkit-animation-timing-function: ease-in-out;
    animation-timing-function: ease-in-out;
}

.modalAnimateName(@name) {
    -webkit-animation-name: @name;
    animation-name: @name;
}

.modalSlideDownTop() {
    0% {
        margin-top: -100%;
        .modalTranslate(-50%, -150%);
    }
    100% {
        margin-top: 0;
        .modalTranslate(-50%, 0);
    }
}

@-webkit-keyframes mf-modal-slide-down-top {
    .modalSlideDownTop;
}

@keyframes mf-modal-slide-down-top {
    .modalSlideDownTop;
}

.modalSlideDownMiddle() {
    0% {
        margin-top: -100%;
        .modalTranslate(-50%, -150%);
    }
    100% {
        margin-top: 0;
        .modalTranslate(-50%, -50%);
    }
}

@-webkit-keyframes mf-modal-slide-down-middle {
    .modalSlideDownMiddle;
}

@keyframes mf-modal-slide-down-middle {
    .modalSlideDownMiddle;
}

.modalSlideUpBottom() {
    0% {
        margin-bottom: -100%;
        .modalTranslate(-50%, 150%);
    }
    100% {
        margin-bottom: 0;
        .modalTranslate(-50%, 0);
    }
}

@-webkit-keyframes mf-modal-slide-up-bottom {
    .modalSlideUpBottom;
}

@keyframes mf-modal-slide-up-bottom {
    .modalSlideUpBottom;
}