<?php

echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
    'sessionVar' => 'lead',
    'orderBy'    => 'l.stage_id',
    'text'       => 'mautic.lead.stage.label',
    'class'      => 'col-lead-stage '.$class,
]);
