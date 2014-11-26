<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:slim.html.php');

$js = <<<JS
Mautic.handleCallback("$connector", "$csrfToken", "$code", "$callbackUrl", "{$view['translator']->trans('mautic.connector.oauth.popupblocked')}");
JS;
$view['assets']->addScriptDeclaration($js, 'bodyClose');