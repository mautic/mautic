<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
$view['slots']->set('message', 'mautic.core.error.403');
$view->extend('MauticCoreBundle:Exception:base.html.php');
