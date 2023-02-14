<?php

if (!$app->getRequest()->isXmlHttpRequest()) {
    $view->extend('MauticUserBundle:Security:base.html.php');
    $view['slots']->set('header', $view['translator']->trans('mautic.open_id.login_required.header', ['%provider%' => 'OpenID Connect']));
} else {
    $view->extend('MauticUserBundle:Security:ajax.html.php');
}

echo $content;
