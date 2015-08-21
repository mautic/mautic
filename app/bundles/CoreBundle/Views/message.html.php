<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

// Used to show the message page from themes
if ($code = $view['analytics']->getCode()) {
    $view['assets']->addCustomDeclaration($code);
}

$view->extend(":$template:message.html.php");
