<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

if (!isset($entity)) {
    $entity = $form->vars['data'];
}

$extendTemplate = (!empty($useSlim)) ? 'slim' : 'content';
$view->extend('MauticCoreBundle:Default:'.$extendTemplate.'.html.php');
if (isset($mauticContent)) {
    $view['slots']->set('mauticContent', $mauticContent);
}

if (!isset($headerTitle)) {
    if ($entity->getId()) {
        $headerTitle = $view['translator']->hasId($translationBase.'.header.edit')
            ?
            $view['translator']->trans($translationBase.'.header.edit', ['%name%' => $entity->getName()])
            :
            $view['translator']->trans('mautic.core.header.edit', ['%name%' => $entity->getName()]);
    } else {
        $headerTitle = $view['translator']->hasId($translationBase.'.header.new')
            ?
            $view['translator']->trans($translationBase.'.header.new')
            :
            $view['translator']->trans('mautic.core.header.new');
    }
}
$view['slots']->set('headerTitle', $headerTitle);

$attr = $form->vars['attr'];
if ($view['slots']->has('formAttr')) {
    $attr = array_merge($attr, $view['slots']->get('formAttr'));
}

echo $view['form']->start($form, ['attr' => $attr]);
$view['slots']->output('mainFormContent');
echo $view['form']->end($form);
$view['slots']->output('postFormContent');
?>


