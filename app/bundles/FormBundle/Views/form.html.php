<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//extend the template chosen

if ($template !== null):
    $view['slots']->set('pageTitle', $name);
    if ($code = $view['analytics']->getCode()) {
        $view['assets']->addCustomDeclaration($code);
    }

    if (!empty($stylesheets)) {
        foreach ($stylesheets as $css) {
            $view['assets']->addStylesheet($css);
        }
    }

    $view->extend(":$template:form.html.php");
else:
?>

<html>
    <head>
        <title><?php echo $name; ?></title>

        <?php echo $view['analytics']->getCode(); ?>

        <?php foreach ($stylesheets as $css): ?>
        <link rel="stylesheet" type="text/css" href="<?php echo $css; ?>" />
        <?php endforeach; ?>

    </head>
    <body>
        <?php echo $content; ?>
    </body>
</html>
<?php endif; ?>