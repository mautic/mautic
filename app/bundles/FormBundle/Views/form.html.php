<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//extend the template chosen

if (!empty($googleAnalytics)) {
    $view['assets']->addCustomDeclaration(htmlspecialchars_decode($googleAnalytics));
}

$view->extend(":$template:form.html.php");
