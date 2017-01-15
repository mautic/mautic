<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view->extend('MauticCoreBundle:Default:slim.html.php');
$js = <<<JS
Mautic.handleIntegrationCallback("$integration", "$csrfToken", "$code", "$callbackUrl", "$clientIdKey", "$clientSecretKey");
JS;
$view['assets']->addScriptDeclaration($js, 'bodyClose');
