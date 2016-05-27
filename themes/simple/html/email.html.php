<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<html>
    <head>
        <?php $view['assets']->outputHeadDeclarations(); ?>
        <style type="text/css">
        /* BUILDER CSS */
        div[data-slot-handle] {
            cursor: move!important;
            bottom: -5px;
            content: '';
            left: -15px;
            margin: 0;
            padding: 0;
            position: absolute;
            right: -15px;
            top: -5px;
            z-index: -1;
            border: 1px solid blue;
        }

        div[data-slot] {
            position: relative;
            z-index: 5;
        }

        .slot-placeholder {
            border: 1px solid red;
            margin: 20px 0;
        }
        </style>
    </head>
    <body>
        <table align="center" cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td class="header-lg" data-slot-container>
                    <div data-slot="text">
                        You've received an invitation!
                    </div>
                </td>
            </tr>
            <tr>
                <td class="mini-img" data-slot-container>
                    <div data-slot="image">
                        <a href=""><img src="http://s3.amazonaws.com/swu-filepicker/DXWZ4PzwQUGI0wQoABDt_jacket.jpg" alt="product" /></a>
                    </div>
                </td>
            </tr>
            <tr>
                <td style="padding: 25px 0 25px" data-slot-container>
                    <div data-slot="text">
                        <strong>Awesome Inc</strong><br />
                        1234 Awesome St <br />
                        Wonderland <br /><br />
                    </div>
                </td>
            </tr>
        </table>
    </body>
</html>
