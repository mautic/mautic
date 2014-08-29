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