<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

$container->setDefinition(
    'mautic.point.type.form',
    new Definition(
        'Mautic\PointBundle\Form\Type\PointType',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('form.type', array(
        'alias' => 'point'
    ));

$container->setDefinition(
    'mautic.point.type.action',
    new Definition(
        'Mautic\PointBundle\Form\Type\ActionType'
    )
)
    ->addTag('form.type', array(
        'alias' => 'pointaction'
    ));

$container->setDefinition(
    'mautic.pointrange.type.form',
    new Definition(
        'Mautic\PointBundle\Form\Type\RangeType',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('form.type', array(
        'alias' => 'pointrange'
    ));

$container->setDefinition(
    'mautic.pointrange.type.action',
    new Definition(
        'Mautic\PointBundle\Form\Type\RangeActionType'
    )
)
    ->addTag('form.type', array(
        'alias' => 'pointrangeaction'
    ));

$container->setDefinition(
    'mautic.point.type.genericpoint_settings',
    new Definition(
        'Mautic\PointBundle\Form\Type\GenericPointSettingsType'
    )
)
    ->addTag('form.type', array(
        'alias' => 'genericpoint_settings'
    ));

$container
    ->register('mautic.validator.point.rangesequence', 'Mautic\PointBundle\Form\Validator\RangeSequenceValidator')
    ->addTag('validator.constraint_validator', array('alias' => 'point_range_sequence'));