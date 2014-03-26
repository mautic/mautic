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
    <head>
        <meta charset="UTF-8" />
        <title>Mautic</title>
        <link rel="icon" type="image/x-icon" href="<?php echo $view['assets']->getUrl('media/images/favicon.ico') ?>" />
        <link rel="apple-touch-icon" href="<?php echo $view['assets']->getUrl('media/images/apple-touch-icon.png') ?>" />

        <?php
        foreach ($view['assetic']->stylesheets(array('@mautic_stylesheets'), array(), array('combine' => true, 'output' => 'media/css/mautic.css')) as $url): ?>
        <link rel="stylesheet" href="<?php echo $view->escape($url) ?>" />
        <?php endforeach; ?>
        <?php foreach ($view['assetic']->stylesheets(array('media/font-awesome/css/font-awesome.min.css')) as $url): ?>
        <link rel="stylesheet" href="<?php echo $view->escape($url) ?>" />
        <?php endforeach; ?>
    </head>
    <body>
        <div class="container-fluid panel-container">

            <div class="left-panel">
                <?php echo $view->render('MauticCoreBundle:Default:leftpanel.html.php'); ?>
            </div>

            <header class="top-panel">
                <h3>Header Bar</h3>
            </header>

            <div class="main-panel-wrapper">
                <div class="main-panel-breadcrumbs">
                    <?php echo $view->render('MauticCoreBundle:Default:breadcrumbs.html.php'); ?>
                </div>
                <div class="main-panel">
                    <div class="main-panel-flash-msgs">
                        <?php echo $view->render('MauticCoreBundle:Default:flashes.html.php'); ?>
                    </div>
                    <div class="main-panel-content">
                        <?php $view['slots']->output('_content'); ?>
                    </div>
                </div>
            </div>
            <div class="clearfix"></div>
        </div>
        <?php foreach ($view['assetic']->javascripts(array("@mautic_javascripts"), array(), array('combine' => true, 'output' => 'media/js/mautic.js')) as $url): ?>
            <script type="text/javascript" src="<?php echo $view->escape($url) ?>"></script>
        <?php endforeach; ?>
    </body>
</html>