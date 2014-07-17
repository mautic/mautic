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
        <div class="loading-message">
            <div class="loading-message-inner-wrapper bg-success">
                <?php echo $view['translator']->trans('mautic.core.loading'); ?>
            </div>
        </div>
        <div class="page-wrapper<?php echo $activePanelClasses; ?>">
            <div class="loading-bar progress">
                <div class="progress-bar" role="progressbar" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100" style="width: 0%;"></div>
            </div>

            <div class="left-panel scrollable">
                <?php echo $view->render('MauticCoreBundle:Default:leftpanel.html.php'); ?>
            </div>

            <div class="main-panel-wrapper">
            <header class="top-panel">
                <?php echo $view->render('MauticCoreBundle:Default:toppanel.html.php'); ?>
            </header>
                <div class="main-panel-breadcrumbs">
                    <?php echo $view->render('MauticCoreBundle:Default:breadcrumbs.html.php'); ?>
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

            <div class="right-panel scrollable">
                <?php echo $view->render('MauticCoreBundle:Default:rightpanel.html.php'); ?>
            </div>

        </div>

        <?php echo $view->render('MauticCoreBundle:Default:script.html.php'); ?>
    </body>
</html>