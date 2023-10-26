<?php

$view['slots']->set('mautibot', 'openMouth');
$view['slots']->set('message', 'mautic.core.error.404');
$view->extend('MauticCoreBundle:Exception:base.html.php');
