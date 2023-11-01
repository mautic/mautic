<?php

$containerId    = 'leadFieldsContainer';
$numberOfFields = ($form->offsetExists('update_mautic1')) ? 5 : 4;
$object         = 'lead';

include __DIR__.'/fields_row.html.php';
