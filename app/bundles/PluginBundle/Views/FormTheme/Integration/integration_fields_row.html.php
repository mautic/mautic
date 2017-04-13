<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$containerId    = 'leadFieldsContainer';
$numberOfFields = ($form->offsetExists('update_mautic1')) ? 5 : 4;
$object         = 'contact';

include __DIR__.'/fields_row.html.php';
