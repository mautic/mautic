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
        <link rel="icon" type="image/x-icon" href="<?php echo $view['assets']->getUrl('favicon.ico') ?>" />

        <link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">

        <?php
        foreach ($view['assetic']->stylesheets(array('@mautic_stylesheets'), array(), array('output' => 'css/mautic.css')) as $url): ?>
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
                    <ol class="breadcrumb">
                        <li><a href="/">Home</a></li>
                    </ol>
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


            <script src="//ajax.googleapis.com/ajax/libs/jquery/1.11.0/jquery.min.js"></script>
            <script src="//netdna.bootstrapcdn.com/bootstrap/3.1.1/js/bootstrap.min.js"></script>

            <?php foreach ($view['assetic']->javascripts(array("@mautic_javascripts"), array(), array('output' => 'js/mautic.js')) as $url): ?>
            <script type="text/javascript" src="<?php echo $view->escape($url) ?>"></script>
            <?php endforeach; ?>
        </div>
    </body>
</html>