<?php

echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
    'sessionVar' => 'lead',
    'orderBy'    => 'l.lastname, l.firstname, l.company, l.email',
    'text'       => 'mautic.core.name',
    'class'      => 'col-lead-name '.$class,
]);
