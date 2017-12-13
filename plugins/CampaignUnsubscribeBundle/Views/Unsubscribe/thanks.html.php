<?php

/*
 * @copyright   2017 Partout D.N.A. All rights reserved
 * @author      Partout D.N.A.
 *
 * @link        https://partout.nl
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8"/>
    <title><?php if (!empty($view['slots']->get('headerTitle', ''))): ?>
            <?php echo strip_tags(str_replace('<', ' <', $view['slots']->get('headerTitle', ''))); ?> |
        <?php endif; ?>
        <?php echo $view['slots']->get('pageTitle', 'Mautic'); ?>
    </title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="icon" type="image/x-icon" href="<?php echo $view['assets']->getUrl('media/images/favicon.ico') ?>"/>
    <link rel="icon" sizes="192x192" href="<?php echo $view['assets']->getUrl('media/images/favicon.ico') ?>">
    <link rel="apple-touch-icon" href="<?php echo $view['assets']->getUrl('media/images/apple-touch-icon.png') ?>"/>

    <?php echo $view['assets']->outputSystemStylesheets(); ?>
    <link rel="stylesheet"
          href="<?php echo $view['assets']->getUrl('plugins/CampaignUnsubscribeBundle/Assets/css/unsubscribe_form.css'); ?>">

    <?php echo $view->render('MauticCoreBundle:Default:script.html.php'); ?>
    <?php $view['assets']->outputHeadDeclarations(); ?>
</head>

<body class="header-fixed">
<!-- start: app-wrapper -->
<section id="app-wrapper">
    <header>
        <? if($logoUrl):?>
            <img class="logo" src="<?= $logoUrl?>" alt="">
        <? endif;?>
    </header>

    <div class="container">
        <h1><?= $confirmationTitle ?></h1>

        <p>
            <?= $confirmationBody ?>
        </p>
    </div>
</section>

</body>
</html>
