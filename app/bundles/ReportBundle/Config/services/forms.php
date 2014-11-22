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

//Custom form widgets
// $container->setDefinition('mautic.form.type.column_selector', new Definition(
//     'Mautic\ReportBundle\Form\Type\ColumnSelectorType'
// ))
//     ->addTag('form.type', array(
//         'alias' => 'column_selector',
//     ));

$container->setDefinition('mautic.form.type.filter_selector', new Definition(
    'Mautic\ReportBundle\Form\Type\FilterSelectorType'
))
    ->addTag('form.type', array(
        'alias' => 'filter_selector',
    ));
