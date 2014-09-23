<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$activePanelClasses  = ($app->getSession()->get('left-panel', 'default') == 'unpinned') ? ' hide-left' : "";
?>
<!DOCTYPE html>
<html>
    <?php echo $view->render('MauticCoreBundle:Default:head.html.php'); ?>
    <body class="header-fixed">
        <!-- start: app-wrapper -->
        <section id="app-wrapper">
            <?php $view['assets']->outputScripts("bodyOpen"); ?>

            <!-- start: loading-message -->
            <div class="loading-message hidden">
                <div class="loading-message-inner-wrapper bg-success">
                    <?php echo $view['translator']->trans('mautic.core.loading'); ?>
                </div>
            </div>
            <!--/ end: loading-message -->

            <!-- start: app-header -->
            <header id="app-header" class="navbar">
               <?php echo $view->render('MauticInstallBundle:Install:navbar.html.php'); ?>
            </header>
            <!--/ end: app-header -->

            <!-- start: app-sidebar(left) -->
            <aside class="app-sidebar sidebar-left">
                <?php //echo $view->render('MauticCoreBundle:Default:leftpanel.html.php'); ?>
            </aside>
            <!--/ end: app-sidebar(left) -->

            <!-- start: app-content -->
            <section id="app-content">
                <?php $view['slots']->output('_content'); ?>
            </section>
            <!--/ end: app-content -->

            <?php /*
            <div class="page-wrapper<?php echo $activePanelClasses; ?>">
                <div class="loading-bar progress">
                    <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;"></div>
                </div>
                <div class="main-panel-wrapper">
                    </div>
                    <div class="main-panel">
                        <a href="#" id="main-panel-top"></a>
                        <div class="main-panel-flash-msgs">
                            <?php echo $view->render('MauticCoreBundle:Default:flashes.html.php'); ?>
                        </div>
                        <div id="page-content" class="main-panel-content container-fluid">
                            <?php $view['slots']->output('_content'); ?>
                        </div>
                    </div>
                </div>
            </div> */ ?>

            <!-- start: app-sidebar(right) -->
            <aside class="app-sidebar sidebar-right">
                <?php //echo $view->render('MauticCoreBundle:Default:rightpanel.html.php'); ?>
            </aside>
            <!--/ end: app-sidebar(right) -->

           <?php /*
            <div class="right-panel scrollable">
                <?php echo $view->render('MauticCoreBundle:Default:rightpanel.html.php'); ?>
            </div>
            */ ?>

            <!-- start: app-footer -->
            <footer id="app-footer">
                <div class="container-fluid">
                    <div class="row">
                        <div class="col-xs-6">Copyright Mautic 2014</div>
                    </div>
                </div>
            </footer>
            <!--/ end: app-content -->
        </section>
        <!--/ end: app-wrapper -->

        <?php echo $view->render('MauticCoreBundle:Default:script.html.php'); ?>
    </body>
</html>
