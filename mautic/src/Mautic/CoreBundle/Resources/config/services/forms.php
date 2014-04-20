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
use Symfony\Component\DependencyInjection\Parameter;

//Custom form widgets
$container->setDefinition('mautic.form.type.spacer', new Definition(
    'Mautic\CoreBundle\Form\Type\SpacerType'
))
    ->addTag('form.type', array(
        'alias' => 'spacer',
    ));

$container->setDefinition('mautic.form.type.panel_start', new Definition(
    'Mautic\CoreBundle\Form\Type\PanelStartType'
))
    ->addTag('form.type', array(
        'alias' => 'panel_start',
    ));

$container->setDefinition('mautic.form.type.panel_end', new Definition(
    'Mautic\CoreBundle\Form\Type\PanelEndType'
))
    ->addTag('form.type', array(
        'alias' => 'panel_end',
    ));

$container->setDefinition('mautic.form.type.panel_wrapper_start', new Definition(
    'Mautic\CoreBundle\Form\Type\PanelWrapperStartType'
))
    ->addTag('form.type', array(
        'alias' => 'panel_wrapper_start',
    ));

$container->setDefinition('mautic.form.type.panel_wrapper_end', new Definition(
    'Mautic\CoreBundle\Form\Type\PanelWrapperEndType'
))
    ->addTag('form.type', array(
        'alias' => 'panel_wrapper_end',
    ));