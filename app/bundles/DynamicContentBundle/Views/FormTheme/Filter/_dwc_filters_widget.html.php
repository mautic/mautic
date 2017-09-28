<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */
foreach ($form as $i => $filter) {
    $isPrototype = ($filter->vars['name'] === '__name__');
    $filterType  = $filter['field']->vars['value'];
    foreach ($form->parent->vars['fields'] as $object => $objectfields) {
        if ($isPrototype || isset($objectfields[$filter->vars['value']['field']])) {
            echo $view['form']->widget($filter, ['first' => ($i === 0)]);
        }
    }
}
