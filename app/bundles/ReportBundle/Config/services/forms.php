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

//Report forms
$container->setDefinition(
    'mautic.form.type.report',
    new Definition(
        'Mautic\ReportBundle\Form\Type\ReportType',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('form.type', array(
        'alias' => 'report',
    ));

$container->setDefinition('mautic.form.type.filter_selector', new Definition(
    'Mautic\ReportBundle\Form\Type\FilterSelectorType'
))
    ->addTag('form.type', array(
        'alias' => 'filter_selector',
    ));

$container->setDefinition('mautic.form.type.table_order', new Definition(
    'Mautic\ReportBundle\Form\Type\TableOrderType',
    array(new Reference('mautic.factory'))
))
    ->addTag('form.type', array(
        'alias' => 'table_order',
    ));
