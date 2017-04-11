<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!isset($idPrefix)) {
    // Used by JS to create new properties array for the form
    $idPrefix = '';
}

if (!isset($namePrefix)) {
    // Used by JS to create new properties array for the form
    $namePrefix = '';
}

if (!is_array($properties)) {
    $properties = [$properties];
}

if (!empty($appendAsPanel)) {
    $addCallback = 'updateSortablePanel';
    if (!empty($clearFormOnCancel)) {
        $cancelCallback = 'cancelSortablePanel';
    }
}

if (!isset($footerButtonClass)) {
    $footerButtonClass = null;
}

if (!isset($modalAttr)) {
    $modalAttr = '';
}

if (!isset($modalClass)) {
    $modalClass = '';
}

echo "<div id=\"{$idPrefix}entity_properties\" class=\"entity-properties no-chosen\">\n";
// Build prototype modals for entity properties
/** @var \Symfony\Component\Form\Form $property */
foreach ($properties as $property):
    $cancelAttr = 'data-embedded-form="cancel"';
    if (!empty($clearFormOnCancel)) {
        $cancelAttr .= ' data-embedded-form-clear="true"';
    }
    if (!empty($cancelCallback)) {
        $cancelAttr .= ' data-embedded-form-callback="'.$cancelCallback.'"';
    }

    $addAttr = 'data-embedded-form="add"';
    if (!empty($addCallback)) {
        $addAttr .= ' data-embedded-form-callback="'.$addCallback.'"';
    }

    echo $view->render(
        'MauticCoreBundle:Helper:modal.html.php',
        [
            'id'             => $idPrefix.$property->vars['name'],
            'dismissible'    => false,
            'containerClass' => $modalClass,
            'containerAttr'  => $modalAttr.' data-name="'.$property->vars['name'].'" data-id-prefix="'.$idPrefix.'" data-name-prefix="'.$namePrefix
                .'"'.$view['form']->block($property, 'widget_attributes'),
            'body'              => $view['form']->widget($property),
            'header'            => (isset($header)) ? $header : $property->vars['label'],
            'footerButtonClass' => $footerButtonClass,
            'footerButtons'     => [
                [
                    'class'    => 'btn-default btn-cancel btn-nospin',
                    'textIcon' => (isset($closeButtonIcon) ? $closeButtonIcon : 'fa fa-times text-danger'),
                    'label'    => $view['translator']->trans((isset($closeButtonText) ? $closeButtonText : 'mautic.core.form.cancel')),
                    'attr'     => $cancelAttr,
                ],
                [
                    'class'    => 'btn-default btn-add btn-nospin'.(!empty($update) ? ' hide' : ''),
                    'textIcon' => (isset($addButtonIcon) ? $addButtonIcon : 'fa fa-plus'),
                    'label'    => $view['translator']->trans((isset($addButtonText) ? $addButtonText : 'mautic.core.form.add')),
                    'attr'     => $addAttr,
                ],
                [
                    'class'    => 'btn-default btn-nospin btn-update'.(empty($update) ? ' hide' : ''),
                    'textIcon' => (isset($updateButtonIcon) ? $updateButtonIcon : 'fa fa-save'),
                    'label'    => $view['translator']->trans((isset($updateButtonText) ? $updateButtonText : 'mautic.core.form.update')),
                    'attr'     => $addAttr,
                ],

            ],
        ]
    );
endforeach;
echo "</div>\n";
