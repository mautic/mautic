<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$containerId    = 'companyFieldsContainer';
$numberOfFields = ($form->offsetExists('update_mautic_company1')) ? 4 : 3;
$object         = 'company';

include __DIR__.'/fields_row.html.php';
