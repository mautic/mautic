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
    foreach ($view['assetic']->stylesheets(array('@mautic_stylesheets'), array(), array('output' => 'media/css/mautic.css')) as $url): ?>
        <link rel="stylesheet" href="<?php echo $view->escape($url) ?>" />
    <?php endforeach; ?>
</head>
<body>
<div class="login-container">
    <div class="container-fluid">
        <form class="form-login" action="<?php echo $view['router']->generate('login_check') ?>" method="post">
            <img src="<?php echo $view['assets']->getUrl('media/images/mautic_logo.png') ?>" />
            <h1 class="user-login-heading"><?php echo $view['translator']->trans('mautic.user.login.heading'); ?></h1>
            <div id="main-panel-flash-msgs">
                <?php echo $view->render('MauticCoreBundle:Default:flashes.html.php'); ?>
            </div>
            <div class="margin-10">
                <label for="username" class="sr-only"><?php echo $view['translator']->trans('mautic.user.form.username'); ?></label>
                <input type="text" id="username" name="_username"
                       class="form-control input-lg" value="<?php echo $last_username ?>" required autofocus
                       placeholder='<?php echo $view['translator']->trans('mautic.user.form.username'); ?>' />
            </div>
            <div class="margin-10">
                <label for="password" class="sr-only"><?php echo $view['translator']->trans('mautic.user.form.password'); ?>:</label>
                <input type="password" id="password" name="_password"
                       class="form-control input-lg" required
                       placeholder='<?php echo $view['translator']->trans('mautic.user.form.password'); ?>' />
            </div>
            <div class="margin-10">
                <input type="checkbox" id="remember_me" name="_remember_me" checked />
                <label for="remember_me"><?php echo $view['translator']->trans('mautic.user.form.rememberme'); ?></label>
            </div>

            <input type="hidden" name="_csrf_token" value="<?php echo $view['form']->csrfToken('authenticate') ?>" />
            <button class="btn btn-lg btn-primary btn-block" type="submit"><?php echo $view['translator']->trans('mautic.user.form.loginbtn'); ?></button>
        </form>
    </div>
</div>
<?php foreach ($view['assetic']->javascripts(array("@mautic_javascripts"), array(), array('combine' => true, 'output' => 'media/js/mautic.js')) as $url): ?>
    <script type="text/javascript" src="<?php echo $view->escape($url) ?>"></script>
<?php endforeach; ?>
<script>
    $(document).ready( function () {
        $('body').css("background-color", "#513B49");
    });
</script>
</body>
</html>