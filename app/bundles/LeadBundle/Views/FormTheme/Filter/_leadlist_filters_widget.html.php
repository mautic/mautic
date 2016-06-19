<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

foreach ($form as $filter) {
    $isPrototype = ($filter->vars['name'] == '__name__');
    $filterType  = $filter['field']->vars['value'];
    if ($isPrototype || isset($form->parent->vars['fields'][$filter->vars['value']['field']])) {
        echo $view['form']->widget($filter);
    }
}