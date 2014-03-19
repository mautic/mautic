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
        <title>Mautic<?php ($view['slots']->has('title')) ? $view['slots']->output('title') : ""; ?></title>
        <link rel="icon" type="image/x-icon" href="<?php echo $view['assets']->getUrl('media/images/favicon.ico') ?>" />
        <link rel="apple-touch-icon" href="<?php echo $view['assets']->getUrl('media/images/apple-touch-icon.png') ?>" />

        <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">

        <?php
        foreach ($view['assetic']->stylesheets(array('@mautic_stylesheets'), array(), array('output' => 'media/css/mautic.css')) as $url): ?>
        <link rel="stylesheet" href="<?php echo $view->escape($url) ?>" />
        <?php endforeach; ?>
    </head>
    <body>

    <header id="header">
        <h3>Header Bar</h3>
    </header>

    <div id="panel-wrapper">
            <div id="left-side-panel">
                <?php echo $view->render('MauticBaseBundle:Default:leftpanel.html.php'); ?>
            </div>

            <div id="main-panel">
                <div id="main-panel-breadcrumbs">
                    <?php echo $view->render('MauticBaseBundle:Default:breadcrumbs.html.php'); ?>
                </div>
                <?php if ($view['slots']->has('title')): ?>
                <div id="main-panel-header">
                    <h1><?php $view['slots']->output('title'); ?></h1>
                </div>
                <?php endif; ?>

                <div id="main-panel-content">
                    <?php $view['slots']->output('_content'); ?>
                </div>
            </div>
        </div>

        <?php foreach ($view['assetic']->javascripts(array("@mautic_javascripts"), array(), array('combine' => true, 'output' => 'media/js/mautic.js')) as $url): ?>
            <script type="text/javascript" src="<?php echo $view->escape($url) ?>"></script>
        <?php endforeach; ?>
        <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>
    </body>
</html>