<?php

/*
 * @copyright   2019 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
    'sessionVar' => 'lead',
    'orderBy'    => 'l.lastname, l.firstname, l.company, l.email',
    'text'       => 'mautic.core.name',
    'class'      => 'col-lead-name '.$class,
]);
