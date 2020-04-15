<?php

/*
 * @copyright   2020 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if ($f->isCustom()):
    if (!isset($fieldSettings[$f->getType()])):
        continue;
    endif;
    $params = $fieldSettings[$f->getType()];
    $f->setCustomParameters($params);

    $template = $params['template'];
else:
    if (!$f->getShowWhenValueExists() && $f->getLeadField() && $f->getIsAutoFill() && $lead && !empty($lead->getFieldValue($f->getLeadField()))) {
        $f->setType('hidden');
    }
    $template = 'MauticFormBundle:Field:'.$f->getType().'.html.php';
endif;

echo $view->render(
    $theme.$template,
    [
        'field'         => $f->convertToArray(),
        'id'            => $f->getAlias(),
        'formName'      => $formName,
        'fieldPage'     => ($pageCount - 1), // current page,
        'contactFields' => $contactFields,
        'companyFields' => $companyFields,
        'inBuilder'     => $inBuilder,
    ]
);
endif;