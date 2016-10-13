<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<!DOCTYPE html>
<html>
    <?php echo $view->render('MauticCoreBundle:Default:head.html.php'); ?>
    <body>
        <?php $view['assets']->outputScripts('bodyOpen'); ?>
        <section id="app-content" class="container content-only">
            <?php echo $view->render('MauticCoreBundle:Notification:flashes.html.php', ['alertType' => 'standard']); ?>
            <?php $view['slots']->output('_content'); ?>
        </section>
        <?php echo $view->render('MauticCoreBundle:Helper:modal.html.php', [
            'id'            => 'MauticSharedModal',
            'footerButtons' => true,
        ]); ?>
        <?php $view['assets']->outputScripts('bodyClose'); ?>
        <script>
            Mautic.onPageLoad('body');
        </script>
    </body>
</html>
