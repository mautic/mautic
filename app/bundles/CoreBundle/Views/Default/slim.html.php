<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<!DOCTYPE html>
<html>
    <?php echo $view->render('MauticCoreBundle:Default:head.html.php'); ?>
    <body>
        <?php $view['assets']->outputScripts("bodyOpen"); ?>
        <section id="app-content" class="container">
            <div class="row">
                <?php echo $view->render('MauticCoreBundle:Notification:flashes.html.php', array('noGrowl' => true)); ?>
                <?php $view['slots']->output('_content'); ?>
            </div>
        </section>
        <?php $view['assets']->outputScripts("bodyClose"); ?>
    </body>
</html>
