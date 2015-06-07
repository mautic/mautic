<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

//extend the template chosen
$view->extend(":$template:email.html.php");

//add no index header for when viewing via web
if (!empty($inBrowser)) {
    $view['assets']->addCustomDeclaration('<meta name="robots" content="noindex">');
}

//Set the slots
foreach ($slots as $slot) {
    $value = isset($content[$slot]) ? $content[$slot] : "";
    $view['slots']->set($slot, $value);
}