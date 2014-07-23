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
    <body style="overflow: auto;">
        <div class="container">
            <?php if ($view['blocks']->has("headerTitle")): ?>
                <div  class="row">
                    <h2><?php $view['blocks']->output("headerTitle"); ?></h2>
                </div>
            <?php endif; ?>

            <div class="row">
                <?php $view['blocks']->output('_content'); ?>
            </div>
        </div>
    </body>
</html>