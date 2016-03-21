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
    </head>
    <body style="background: #f7f7f7; margin:0">
        <div style="background-color: #F3F3F3; border-bottom: 2px solid #D3D2D2;">
            <table style="border-collapse: collapse; width: 600px; min-height: 50px; display: block; font-family: Helvetica Neue, Helvetica, Arial, sans-serif; margin: 0 auto;">
                <tr>
                    <td style="vertical-align: top; padding: 30px 50px; font-size: 28px; line-height: 1.5em; color: #999999;">
                        <?php $view['slots']->output('header'); ?>
                    </td>
                </tr>
            </table>
        </div>
        <div style="border-top: 2px solid #E2E2E2;">
            <table style="border-collapse: collapse; width: 600px; font-family: Helvetica, Arial, sans-serif;font-size: 14px;color: #333333; margin: 0 auto;">
                <tr>
                    <td style="vertical-align: top; padding: 30px 50px; font-size: 17px; line-height: 1.7em;">
                        <?php $view['slots']->output('body'); ?>
                    </td>
                </tr>
                <tr>
                    <td style="vertical-align: top; padding: 30px 50px; font-size: 12px; line-height: 1.5em;">
                        <?php $view['slots']->output('footer'); ?>
                    </td>
                </tr>
            </table>
        </div>
        <?php $view['slots']->output('builder'); ?>
    </body>
</html>
