<?php

echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
    'sessionVar' => 'lead',
    'orderBy'    => 'l.city, l.state',
    'text'       => 'mautic.lead.lead.thead.location',
    'class'      => 'col-lead-location '.$class,
]);
