<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view['assets']->addScript('app/bundles/InstallBundle/Assets/install/install.js');
?>
<!DOCTYPE html>
<html>
    <?php echo $view->render('MauticCoreBundle:Default:head.html.php'); ?>
    <body>
        <!-- start: app-wrapper -->
        <section id="app-wrapper">
            <div class="container">
                <div class="row mt-20">
                    <div class="text-center">
                        <img src="<?php echo $view['assets']->getUrl('app/assets/images/mautic_logo_lb200.png'); ?>" height="50px" />
                        <h5 class="semibold text-muted mt-5"><?php $view['slots']->output('header', ''); ?></h5>
                    </div>

                    <div class="mt-20 col-lg-6 col-lg-offset-3">
                        <div id="app-content" class="panel panel-default">
                            <?php $view['slots']->output('_content'); ?>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!--/ end: app-content -->
    </body>
</html>
