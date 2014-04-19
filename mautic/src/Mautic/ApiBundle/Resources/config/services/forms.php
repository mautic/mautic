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

//API Client form
$container->setDefinition(
    'mautic.form.type.apiclients',
    new Definition(
        'Mautic\ApiBundle\Form\Type\ClientType',
        array(new Reference('service_container'))
    )
)
    ->addTag('form.type', array(
        'alias' => 'client',
    ));