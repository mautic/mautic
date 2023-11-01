<?php

echo $view->render('MauticCoreBundle:Helper:tableheader.html.php', [
    'sessionVar' => 'lead',
    'orderBy'    => 'l.'.$column,
    'text'       => $label,
    'class'      => 'col-lead-'.$column.' '.$class,
]);
