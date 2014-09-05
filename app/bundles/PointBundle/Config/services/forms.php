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
        'Mautic\PointBundle\Form\Type\PointActionType'
    )
)
    ->addTag('form.type', array(
        'alias' => 'pointaction'
    ));

$container->setDefinition(
    'mautic.pointtrigger.type.form',
    new Definition(
        'Mautic\PointBundle\Form\Type\TriggerType',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('form.type', array(
        'alias' => 'pointtrigger'
    ));

$container->setDefinition(
    'mautic.pointrange.type.action',
    new Definition(
        'Mautic\PointBundle\Form\Type\TriggerEventType'
    )
)
    ->addTag('form.type', array(
        'alias' => 'pointtriggerevent'
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