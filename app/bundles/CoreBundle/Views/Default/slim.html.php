<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<!DOCTYPE html>
<html>
    <?php echo $view->render('MauticCoreBundle:Default:head.html.php'); ?>
    <body>
        <?php $view['assets']->outputScripts("bodyOpen"); ?>
        <section id="main" role="main">
            <div class="container-fluid" id="main-content">
                <?php if ($view['slots']->has("headerTitle")): ?>
                    <div class="row">
                        <h2><?php $view['slots']->output("headerTitle"); ?></h2>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <?php echo $view->render('MauticCoreBundle:Default:flashes.html.php'); ?>
                    <?php $view['slots']->output('_content'); ?>
                </div>
            </div>
        </section>
    </body>
    <?php echo $view->render('MauticCoreBundle:Default:script.html.php'); ?>
</html>
