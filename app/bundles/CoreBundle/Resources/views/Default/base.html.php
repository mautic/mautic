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
    <body>
        <div class="loading-message hidden">
            <div class="loading-message-inner-wrapper bg-success">
                <?php echo $view['translator']->trans('mautic.core.loading'); ?>
            </div>
        </div>
        <header id="header" class="navbar navbar-fixed-top">
           <?php echo $view->render('MauticCoreBundle:Default:navbar.html.php'); ?>
        </header>
        <aside class="sidebar sidebar-left sidebar-menu">
            <div class="viewport">
                <?php echo $view->render('MauticCoreBundle:Default:leftpanel.html.php'); ?>
            </div>
        </aside>
        <section id="main" role="main">
            <div class="container-fluid">
                <div class="page-header page-header-block">
                    <?php echo $view->render('MauticCoreBundle:Default:toppanel.html.php'); ?>
                </div>

                <?php $view['slots']->output('_content'); ?>
            </div>
        </section>

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
            */ ?>
           
           <aside class="sidebar sidebar-right">
                <?php echo $view->render('MauticCoreBundle:Default:rightpanel.html.php'); ?>
           </aside>

           <?php /*
            <div class="right-panel scrollable">
                <?php echo $view->render('MauticCoreBundle:Default:rightpanel.html.php'); ?>
            </div>
            */ ?>
        </div>

        <?php echo $view->render('MauticCoreBundle:Default:script.html.php'); ?>
    </body>
</html>