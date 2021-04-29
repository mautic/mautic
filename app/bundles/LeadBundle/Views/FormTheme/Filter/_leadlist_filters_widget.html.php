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
    $isPrototype = ('__name__' == $filter->vars['name']);
    $filterType  = $filter['field']->vars['value'];
    foreach ($form->parent->vars['fields'] as $object => $objectfields) {
        $isField    = isset($objectfields[$filter->vars['value']['field']]);
        $isBehavior = isset($form->parent->vars['fields']['behaviors'][$filter->vars['value']['field']]);
        if ($isPrototype || $isField || $isBehavior) {
            echo $view['form']->widget($filter, ['first' => (0 === $i)]);
        }
    }
}
