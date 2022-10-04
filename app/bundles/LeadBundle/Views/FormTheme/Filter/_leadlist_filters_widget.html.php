<?php

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
