<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Parameter;

//Custom form widgets
$container->setDefinition('mautic.form.type.spacer', new Definition(
    'Mautic\CoreBundle\Form\Type\SpacerType'
))
    ->addTag('form.type', array(
        'alias' => 'spacer',
    ));

$container->setDefinition('mautic.form.type.tel', new Definition(
    'Mautic\CoreBundle\Form\Type\TelType'
))
    ->addTag('form.type', array(
        'alias' => 'tel',
    ));

$container->setDefinition('mautic.form.type.button_group', new Definition(
    'Mautic\CoreBundle\Form\Type\ButtonGroupType'
))
    ->addTag('form.type', array(
        'alias' => 'button_group',
    ));

$container->setDefinition('mautic.form.type.yesno_button_group', new Definition(
    'Mautic\CoreBundle\Form\Type\YesNoButtonGroupType'
))
    ->addTag('form.type', array(
        'alias' => 'yesno_button_group',
    ));

$container->setDefinition('mautic.form.type.standalone_button', new Definition(
    'Mautic\CoreBundle\Form\Type\StandAloneButtonType'
))
    ->addTag('form.type', array(
        'alias' => 'standalone_button',
    ));


$container->setDefinition('mautic.form.type.form_buttons', new Definition(
    'Mautic\CoreBundle\Form\Type\FormButtonsType'
))
    ->addTag('form.type', array(
        'alias' => 'form_buttons',
    ));

$container->setDefinition('mautic.form.type.hidden_entity', new Definition(
    'Mautic\CoreBundle\Form\Type\HiddenEntityType',
    array(
        new Reference('doctrine.orm.entity_manager')
    )
))
    ->addTag('form.type', array(
        'alias' => 'hidden_entity',
    ));

$container->setDefinition(
    'mautic.form.type.list',
    new Definition(
        'Mautic\CoreBundle\Form\Type\SortableListType'
    )
)
    ->addTag('form.type', array(
        'alias' => 'sortablelist',
    ));

$container->setDefinition('mautic.form.type.coreconfig', new Definition(
    'Mautic\CoreBundle\Form\Type\ConfigType',
    array(new Reference('mautic.factory'))
))
    ->addTag('form.type', array(
        'alias' => 'coreconfig',
    ));
