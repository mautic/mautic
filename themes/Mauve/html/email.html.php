<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<html>
<head>
    <?php $view['assets']->outputHeadDeclarations(); ?>
</head>
<body style="background: #f2f2f2;">
    <table style="background: #FFFFFF; width: 600px; height: 760px; color: #555; display: block; font-family: Helvetica Neue, Helvetica, Arial, sans-serif; padding: 50px; border: 1px solid #d5d4d4; box-shadow: 1px 1px 1px #d5d4d4; margin: 50px auto;">
        <tr>
            <td style="vertical-align: top;">
                <?php $view['slots']->output('body'); ?>
            </td>
        </tr>
        <tr>
            <td style="vertical-align: top;">
                <?php $view['slots']->output('footer'); ?>
            </td>
        </tr>
    </table>
    <?php $view['slots']->output('builder'); ?>
</body>
</html>