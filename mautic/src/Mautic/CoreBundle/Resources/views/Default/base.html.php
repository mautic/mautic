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
    <head>
        <meta charset="UTF-8" />
        <title>Mautic</title>
        <link rel="icon" type="image/x-icon" href="<?php echo $view['assets']->getUrl('media/images/favicon.ico') ?>" />
        <link rel="apple-touch-icon" href="<?php echo $view['assets']->getUrl('media/images/apple-touch-icon.png') ?>" />

        <?php
        foreach ($view['assetic']->stylesheets(array('@mautic_stylesheets'), array(), array('combine' => true, 'output' => 'media/css/mautic.css')) as $url): ?>
        <link rel="stylesheet" href="<?php echo $view->escape($url) ?>" />
        <?php endforeach; ?>
        <link rel="stylesheet" href="<?php echo $view['assets']->getUrl('media/font-awesome/css/font-awesome.min.css'); ?>" />
    </head>
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

            <header class="top-panel">
                <?php echo $view->render('MauticCoreBundle:Default:toppanel.html.php'); ?>
            </header>

            <div class="left-panel">
                <?php echo $view->render('MauticCoreBundle:Default:leftpanel.html.php'); ?>
            </div>

            <div class="main-panel-wrapper">
                <div class="main-panel-breadcrumbs">
                    <?php echo $view->render('MauticCoreBundle:Default:breadcrumbs.html.php'); ?>
                </div>
                <div class="main-panel">
                    <a href="#" id="main-panel-top"></a>
                    <div class="main-panel-flash-msgs">
                        <?php echo $view->render('MauticCoreBundle:Default:flashes.html.php'); ?>
                    </div>
                    <div class="main-panel-content container-fluid">
                        <?php $view['slots']->output('_content'); ?>
                    </div>
                </div>
            </div>

            <div class="right-panel">
                <?php echo $view->render('MauticCoreBundle:Default:rightpanel.html.php'); ?>
            </div>

        </div>

        <script>
            var mauticBaseUrl = '<?php echo $view['router']->generate("mautic_core_index"); ?>';
            var mauticContent = '<?php $view['slots']->output('mauticContent',''); ?>';
        </script>
        <?php foreach ($view['assetic']->javascripts(array("@mautic_javascripts"), array(), array('combine' => true, 'output' => 'media/js/mautic.js')) as $url): ?>
        <script src="<?php echo $view->escape($url) ?>"></script>
        <?php endforeach; ?>
        <script>
            Mautic.onPageLoad();
            <?php $view['slots']->output("jsDeclarations"); ?>
            <?php if ($app->getEnvironment() === "dev"): ?>
            $( document ).ajaxComplete(function(event, XMLHttpRequest, ajaxOption){
                if(XMLHttpRequest.getResponseHeader('x-debug-token')) {
                    MauticVars.showLoadingBar = false;
                    $('.sf-toolbarreset').remove();
                    $.get(mauticBaseUrl +'_wdt/'+XMLHttpRequest.getResponseHeader('x-debug-token'),function(data){
                        $('body').append(data);
                    });
                }
            });
            <?php endif; ?>
        </script>
    </body>
</html>