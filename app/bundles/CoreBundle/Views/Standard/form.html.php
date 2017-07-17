<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

$view->extend('MauticCoreBundle:FormTheme:form_simple.html.php');

// Parse standard fields
$standard          = ['category', 'language', 'isPublished', 'publishUp', 'publishDown'];
$rightColumnFields = [];
foreach ($standard as $field) {
    $rightColumnFields[$field] = (isset($form[$field])) ? $view['form']->row($form[$field]) : '';
}

// Put toggles on right side
foreach ($form as $field) {
    $fieldName = $field->vars['name'];
    if (!isset($rightColumnFields[$fieldName]) && in_array('yesno_button_group', $field->vars['block_prefixes'])) {
        $rightColumnFields[$fieldName] = $view['form']->row($field);
    }
}

$view['slots']->set('primaryFormContent', $view['form']->rest($form));
$view['slots']->start('rightFormContent');
foreach ($rightColumnFields as $field) {
    echo $field;
}
$view['slots']->stop();
