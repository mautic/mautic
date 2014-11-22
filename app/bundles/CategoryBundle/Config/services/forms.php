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

$container->setDefinition('mautic.form.type.category', new Definition(
    'Mautic\CategoryBundle\Form\Type\CategoryListType',
    array(new Reference('mautic.factory'))
))
    ->addTag('form.type', array(
        'alias' => 'category',
    ));


$container->setDefinition('mautic.form.type.category_form', new Definition(
    'Mautic\CategoryBundle\Form\Type\CategoryType',
    array(new Reference('mautic.factory'))
))
    ->addTag('form.type', array(
        'alias' => 'category_form',
    ));