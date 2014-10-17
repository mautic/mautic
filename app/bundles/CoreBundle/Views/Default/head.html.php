<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
?>
<head>
    <meta charset="UTF-8" />
    <title><?php echo $view['slots']->get('pageTitle', 'Mautic'); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <link rel="icon" type="image/x-icon" href="<?php echo $view['assets']->getUrl('assets/images/favicon.ico') ?>" />
    <link rel="apple-touch-icon" href="<?php echo $view['assets']->getUrl('assets/images/apple-touch-icon.png') ?>" />

    <?php echo $view['assets']->outputSystemStylesheets(); ?>
    <link rel="stylesheet" href="<?php echo $view['assets']->getUrl('assets/css/font-awesome.min.css'); ?>" />
    <script src="<?php echo $view['assets']->getUrl('assets/js/modernizr.min.js'); ?>"></script>
	<script src="<?php echo $view['assets']->getUrl('assets/js/mousetrap.min.js'); ?>"></script>

    <?php $view['assets']->outputHeadDeclarations(); ?>
</head>
