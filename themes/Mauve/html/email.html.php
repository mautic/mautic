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
<body>
    <table style="width: 600px; height: 760px; color: #555; display: block; font-family: Helvetica Neue, Helvetica, Arial, sans-serif; padding: 135px 90px 50px 90px;">
        <tr>
            <td>
                <?php $view['slots']->output('body'); ?>
            </td>
        </tr>
        <tr>
            <td>
                <?php $view['slots']->output('footer'); ?>
            </td>
        </tr>
    </table>
    <?php $view['slots']->output('builder'); ?>
</body>
</html>