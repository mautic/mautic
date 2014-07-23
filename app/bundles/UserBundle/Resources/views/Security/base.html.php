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
    <link rel="icon" type="image/x-icon" href="<?php echo $view['assets']->getUrl('assets/images/favicon.ico') ?>" />
    <link rel="apple-touch-icon" href="<?php echo $view['assets']->getUrl('assets/images/apple-touch-icon.png') ?>" />

    <?php
    foreach ($view['assetic']->stylesheets(array('@mautic_stylesheets'), array(), array('output' => 'assets/css/mautic.css')) as $url): ?>
        <link rel="stylesheet" href="<?php echo $view->escape($url) ?>" />
    <?php endforeach; ?>
</head>
<body style="background-color: #513B49;">
<div class="login-container">
    <div class="container-fluid">
        <img src="<?php echo $view['assets']->getUrl('assets/images/mautic_logo.png') ?>" />
        <h2 class="user-login-heading"><?php $view['blocks']->output('header', ''); ?></h2>
        <div id="main-panel-flash-msgs">
            <?php echo $view->render('MauticCoreBundle:Default:flashes.html.php'); ?>
        </div>

        <?php $view['blocks']->output('_content'); ?>
    </div>
</div>
<script>
    //clear typeahead caches
    window.localStorage.clear();
</script>
<?php foreach ($view['assetic']->javascripts(array("@mautic_javascripts"), array(), array('combine' => true, 'output' => 'assets/js/mautic.js')) as $url): ?>
<script src="<?php echo $view->escape($url) ?>"></script>
<?php endforeach; ?>
</body>
</html>