<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$header = $view['slots']->get('pageHeader');
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
                        <img src="<?php echo $view['assets']->getUrl('media/images/mautic_logo_lb200.png') ?>" height="50px" />
                        <h5 class="semibold text-muted mt-5"><?php $view['slots']->output('header', ''); ?></h5>
                    </div>

                    <div class="mt-20 col-lg-6 col-lg-offset-3">
                        <div id="app-content" class="panel">
                            <div class="pt-120 pb-20 content-body" id="main-content">
                                <?php echo $view->render('MauticCoreBundle:Default:flashes.html.php'); ?>

                                <div class="container-fluid">
                                    <?php if (!empty($header)): ?>
                                    <h2 class="page-header">
                                        <?php echo $view['translator']->trans($header); ?>
                                    </h2>
                                    <?php endif; ?>
                                    <?php $view['slots']->output('_content'); ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!--/ end: app-content -->
    </body>
</html>
