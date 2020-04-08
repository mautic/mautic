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
<html>
    <head>
        <title><?php echo $name; ?></title>

        <?php echo $metaRobots; ?>

        <?php echo $view['analytics']->getCode(); ?>

        <?php if (isset($stylesheets) && is_array($stylesheets)) : ?>
        <?php foreach ($stylesheets as $css): ?>
        <link rel="stylesheet" type="text/css" href="<?php echo $css; ?>" />
        <?php endforeach; ?>
        <?php endif; ?>

    </head>
    <body>
        <?php echo $content; ?>
    </body>
</html>
