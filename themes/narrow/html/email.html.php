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
<body style="background: #969696;">
    <table style="border-collapse: collapse; background: #FFFFFF; width: 600px; min-height: 760px; color: #555; display: block; font-family: Helvetica Neue, Helvetica, Arial, sans-serif; border: 1px solid #494949; box-shadow: 1px 1px 1px #5B5959; margin: 50px auto;">
        <tr>
            <td style="vertical-align: top; padding: 30px 50px 10px 50px; font-size: 28px; line-height: 1.5em;">
                <?php $view['slots']->output('header'); ?>
            </td>
        </tr>
        <tr>
            <td style="vertical-align: top; padding: 30px 50px; font-size: 17px; line-height: 1.7em;">
                <?php $view['slots']->output('body'); ?>
            </td>
        </tr>
        <tr>
            <td style="vertical-align: top; padding: 30px 50px; font-size: 12px; line-height: 1.5em;color: #ffffff; background-color: #888888;">
                <?php $view['slots']->output('footer'); ?>
            </td>
        </tr>
    </table>
    <?php $view['slots']->output('builder'); ?>
</body>
</html>