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
$container->setDefinition('mautic.form.type.page', new Definition(
    'Mautic\PageBundle\Form\Type\PageType',
    array(
        new Reference('mautic.factory')
    )
))
    ->addTag('form.type', array(
        'alias' => 'page',
    ));

$container->setDefinition('mautic.form.type.pagecategory', new Definition(
    'Mautic\PageBundle\Form\Type\CategoryType',
    array(new Reference('mautic.factory'))
))
    ->addTag('form.type', array(
        'alias' => 'pagecategory',
    ));

$container->setDefinition('mautic.form.type.pagevariant', new Definition(
    'Mautic\PageBundle\Form\Type\VariantType',
    array(new Reference('mautic.factory'))
))
    ->addTag('form.type', array(
        'alias' => 'pagevariant',
    ));