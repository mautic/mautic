<?php

$view->extend('MauticCoreBundle:Default:slim.html.php');
$js = <<<JS
Mautic.handleIntegrationCallback("$integration", "$csrfToken", "$code", "$callbackUrl", "$clientIdKey", "$clientSecretKey");
JS;
$view['assets']->addScriptDeclaration($js, 'bodyClose');
