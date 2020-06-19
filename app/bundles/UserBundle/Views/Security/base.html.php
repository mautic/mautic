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
<body class="login-page" style="background-image: url(<?php echo $view['assets']->getUrl('media/images/background-login.png'); ?>)">
<section id="main" role="main">
    <div class="container" style="margin-top:calc(50vh - 190px);">
        <div class="row">
            <div class="col-lg-4 col-lg-offset-4">
                <div class="panel" name="form-login">
                    <div class="panel-body">
                        <div class="mautic-logo img-circle mb-md text-center">
                            <svg class="mautic-logo-figure" id="Livello_1" data-name="Livello 1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1002 178"><defs><style>.cls-1{fill:#c2d100;}.cls-2{fill:#003b4a;}</style></defs><path class="cls-1" d="M795.62,165.49c-8.72,9.07-18.72,11.19-31,11.19a197,197,0,0,1-21.3-1.42l.58-7.77a127.31,127.31,0,0,0,20.61,1.89c10,0,19.89-2.36,25.66-9.3,3.77-4.48,6.71-12.13,6.71-19.66,0-15.43-5.41-30.49-23.54-30.49l-11.19.23v-7.65h10.24c16.37-.24,22.14-11.54,22.14-24.84,0-9.3-1.65-14.84-5.54-18.37-3.65-3.53-9.41-5.18-18.36-5.18a133,133,0,0,0-24.49,2.36l-.35-8.13a225,225,0,0,1,24.84-1.88c11.65,0,19.66,2.24,24.72,7.18,4.83,4.59,7.18,11.89,7.18,23.43,0,13.42-3.29,21.9-14.12,28.37,12.59,6.48,16.36,19.66,16.36,34.85-.35,10.36-4.47,20-9.18,25.19"/><path class="cls-1" d="M870.37,67.43c-12.83,0-24.84,8.71-31,15.07.24,17.3,4.12,47.91,29.2,47.91,12.24,0,26.37-7.06,26.37-30.61S882.5,67.43,870.37,67.43m-1.88,70.51c-27,0-37.91-21.78-37.91-58.86,0-21.31,3.77-48.62,14.36-58.86,9.42-9.07,20.37-12.95,34.14-12.95a135.14,135.14,0,0,1,13.78.7l-.71,8.72c-2.82-.36-9.3-1.06-12.59-1.06-10.36-.24-21.9,3.53-28.85,10.71-8.12,8.59-11.65,34.14-11.18,46.27,6.36-6.71,19.31-13.19,30.84-13.19,19.08,0,32.73,11.66,32.73,40.15,0,27.31-16.25,38.37-34.61,38.37"/><path class="cls-1" d="M965,53.53C940.54,53.53,938,70,938,92.38c0,22.14,2.82,39,27.31,39s27.08-16.95,27.08-39c0-22.36-2.71-38.85-27.31-38.85m.23,85.24c-24.13,0-36-11.54-36-46.39,0-35.08,11.65-46.26,35.79-46.26s35.9,11.18,35.9,46.26c0,34.85-11.53,46.39-35.67,46.39"/><path class="cls-2" d="M116.53,144l.59-116.13h-.39L113,44.33,86.09,144H56l-27-99L25.21,27.82h-.4L25.21,144H.93V2.15H41.52l26.66,102.6,3.38,15.92H72l3.38-15.92L101,2.15H141.2V144Z"/><path class="cls-2" d="M227.72,144l-4.49-17.24H196.87L192.08,144H166.32l29.05-98.33H225L254.23,144Zm-14.83-59.9L210.2,71.61h-.3l-2.55,12.58-5.54,23h16.63Z"/><path class="cls-2" d="M315,144l-7.63-19.08-5.85-16.1h-4.49V144H272.94l-.15-97.34h36a77.67,77.67,0,0,1,13.82,1.13,19.28,19.28,0,0,1,10.09,4.8,22.38,22.38,0,0,1,6.05,9.4,42.11,42.11,0,0,1,1.72,12.92q0,4.81-.45,9.82a38.6,38.6,0,0,1-2,9.47,23.92,23.92,0,0,1-4.64,7.91,18.43,18.43,0,0,1-8.16,5.16L341.08,144Zm1.2-66.26c0-4-.52-7-1.57-9.11s-3.17-3.18-6.37-3.18l-10.78-.14V90.12h10.93c3.2,0,5.29-1,6.29-3S316.22,81.93,316.22,77.69Z"/><path class="cls-2" d="M425.63,23.56V144H401V23.56l-33.62-.2v-22h91.66V23.56Z"/><path class="cls-2" d="M485,144V46.61h59.48V67h-36V83.62h23.73V104H508.5V123.6h36V144Z"/><path class="cls-2" d="M625.19,142.67c-2.36.38-4.57.74-6.65,1.06s-4.09.59-6.07.78-4,.35-5.93.5-4.15.21-6.5.21a66.07,66.07,0,0,1-17.87-2.05A27.78,27.78,0,0,1,570,136q-6.23-6.21-8.69-16.59a106.11,106.11,0,0,1-2.47-24.37,103.24,103.24,0,0,1,2.47-24.16q2.47-10.17,8.69-16.39a27.76,27.76,0,0,1,12.22-7.13,65.67,65.67,0,0,1,17.87-2q3.53,0,6.5.21c2,.14,4,.31,5.93.49s4,.45,6.07.78l6.65,1.06-1.84,20.91q-7.22-1.41-12-2.12A71.06,71.06,0,0,0,601,66a28.81,28.81,0,0,0-8.13,1,9.9,9.9,0,0,0-5.43,4.1,21.89,21.89,0,0,0-3,8.9,101.52,101.52,0,0,0-.92,15.4,100.39,100.39,0,0,0,.92,15.26,21.32,21.32,0,0,0,3,8.83,10,10,0,0,0,5.43,4,28.81,28.81,0,0,0,8.13,1,77.62,77.62,0,0,0,10.31-.64q4.8-.63,12-2Z"/><path class="cls-2" d="M692.71,144V104H666.86v40H643.41V46.61h23.45v37h25.85v-37H716V144Z"/><rect id="Rectangle_60" data-name="Rectangle 60" class="cls-1" x="166.84" y="0.82" width="22.76" height="22.76"/><rect id="Rectangle_60-2" data-name="Rectangle 60" class="cls-1" x="216.43" y="0.82" width="22.76" height="22.76"/><rect id="Rectangle_60-3" data-name="Rectangle 60" class="cls-1" x="266.02" y="0.82" width="22.76" height="22.76"/><rect id="Rectangle_60-4" data-name="Rectangle 60" class="cls-1" x="316.78" y="0.82" width="22.76" height="22.76"/><rect id="Rectangle_60-5" data-name="Rectangle 60" class="cls-1" x="484.71" y="0.82" width="22.76" height="22.76"/><rect id="Rectangle_60-6" data-name="Rectangle 60" class="cls-1" x="536.61" y="0.82" width="22.76" height="22.76"/><rect id="Rectangle_60-7" data-name="Rectangle 60" class="cls-1" x="588.52" y="0.82" width="22.76" height="22.76"/><rect id="Rectangle_60-8" data-name="Rectangle 60" class="cls-1" x="640.43" y="0.82" width="22.76" height="22.76"/><rect id="Rectangle_60-9" data-name="Rectangle 60" class="cls-1" x="692.33" y="0.82" width="22.76" height="22.76"/></svg>
                        </div>
                        <div id="main-panel-flash-msgs">
                            <?php echo $view->render('MauticCoreBundle:Notification:flashes.html.php'); ?>
                        </div>
                        <?php $view['slots']->output('_content'); ?>
                        <p class="text-center">Powered by Digital360 S.p.A.</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-4 col-lg-offset-4 text-center text-muted text-copyright">
                <?php echo $view['translator']->trans('mautic.core.copyright', ['%date%' => date('Y')]); ?>
            </div>
        </div>
    </div>
</section>
<?php echo $view['security']->getAuthenticationContent(); ?>
</body>
</html>
