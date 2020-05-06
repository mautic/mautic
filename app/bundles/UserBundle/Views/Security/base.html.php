<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8" />
    <title><?php echo $view['slots']->get('pageTitle', 'Mautic'); ?></title>
    <meta name="robots" content="noindex, nofollow" />
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="icon" type="image/x-icon" href="<?php echo $view['assets']->getUrl('media/images/favicon.ico'); ?>" />
    <link rel="apple-touch-icon" href="<?php echo $view['assets']->getUrl('media/images/apple-touch-icon.png'); ?>" />
    <?php $view['assets']->outputSystemStylesheets(); ?>
    <?php echo $view->render('MauticCoreBundle:Default:script.html.php'); ?>
    <?php $view['assets']->outputHeadDeclarations(); ?>
</head>
<body>
<section id="main" role="main">
    <div class="container" style="margin-top:100px;">
        <div class="row">
            <div class="col-lg-4 col-lg-offset-4">
                <div class="panel" name="form-login">
                    <div class="panel-body">
                        <div class="mautic-logo img-circle mb-md text-center">
                            <svg class="mautic-logo-figure" id="Livello_1" data-name="Livello 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 513 300"><defs><style>.cls-1{fill:#003b4a;}.cls-2{fill:#c2d100;}</style></defs><path class="cls-1" d="M242.92,298.84l1.25-243h-.83l-7.92,34.56L179.21,298.84H116.33L59.7,91.6,51.79,55.79H51l.84,243.05H1V2.07h85l55.8,214.73,7.08,33.31h.83l7.08-33.31L210.44,2.07h84.11V298.84Z"/><path class="cls-1" d="M442,47.4V298.84H390.44V47.4L320.07,47V1H511.92V47.4Z"/><rect id="Rectangle_60" data-name="Rectangle 60" class="cls-2" x="322.04" y="74.63" width="47.63" height="47.63"/><rect id="Rectangle_60-2" data-name="Rectangle 60" class="cls-2" x="322.04" y="250.44" width="47.63" height="47.63"/><rect id="Rectangle_60-3" data-name="Rectangle 60" class="cls-2" x="464.37" y="162.53" width="47.63" height="47.63"/></svg>
                        </div>
                        <div id="main-panel-flash-msgs">
                            <?php echo $view->render('MauticCoreBundle:Notification:flashes.html.php'); ?>
                        </div>
                        <?php $view['slots']->output('_content'); ?>
                    </div>
                </div>
            </div>
        </div>
         <div class="row">
            <div class="col-lg-4 col-lg-offset-4 text-center text-muted">
                <?php echo $view['translator']->trans('mautic.core.copyright', ['%date%' => date('Y')]); ?>
            </div>
        </div>
    </div>
</section>
<?php echo $view['security']->getAuthenticationContent(); ?>
</body>
</html>
