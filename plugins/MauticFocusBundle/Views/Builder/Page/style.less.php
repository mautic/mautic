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
.mf-page {
    position: fixed;
    opacity: 1;
    z-index: 20000;
    margin: auto;
    padding: 45px;
    background: #fff;
    border-radius: 2px;
    border-width: 6px 1px 1px 1px;
    border-style: solid;
    top: 1px;
    right: 1px;
    left: 1px;
    bottom: 1px;
    text-align: center;

    .mf-content {
        position: absolute;
        min-width: 75%;
        top: 50%;
        left: 50%;
        right: 0;
        transform: translate(-50%, -50%);
        -webkit-transform: translate(-50%, -50%);
        -ms-transform: translate(-50%, -50%);
        margin-bottom: 30px;

        .mf-headline {
            font-size: 2.5em;
            font-weight: 600;
        }

        .mf-tagline {
            font-size: 1.8em;
            font-weight: normal;
            margin-top: 4px;
        }

        a.mf-link {
            padding: 10px 15px;
            display: block;
            max-width: 50%;
            margin: auto;
            font-size: 1.8em;
        }
    }

    .mf-page-close {
        position: absolute;
        top: 0;
        right: 8px;
        a {
            font-size: 1.8em;
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
        width: 75%;
        height: 40px;
        font-size: 1.6em;
        margin-bottom: 8px;
    }
}

<?php if (!empty($preview)): ?>
.mf-page {
    position: absolute !important;
}

<?php endif; ?>
