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

//API Client form
$container->setDefinition(
    'mautic.form.type.apiclients',
    new Definition(
        'Mautic\ApiBundle\Form\Type\ClientType',
        array(new Reference('mautic.factory'))
    )
)
    ->addTag('form.type', array(
        'alias' => 'client',
    ));

$container->setDefinition('mautic.form.type.apiconfig', new Definition(
    'Mautic\ApiBundle\Form\Type\ConfigType',
    array(new Reference('mautic.factory'))
))
    ->addTag('form.type', array(
        'alias' => 'apiconfig',
    ));
