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
$container->setDefinition('mautic.form.type.email', new Definition(
    'Mautic\EmailBundle\Form\Type\EmailType',
    array(
        new Reference('mautic.factory')
    )
))
    ->addTag('form.type', array(
        'alias' => 'emailform',
    ));

$container->setDefinition('mautic.form.type.emailvariant', new Definition(
    'Mautic\EmailBundle\Form\Type\VariantType',
    array(new Reference('mautic.factory'))
))
    ->addTag('form.type', array(
        'alias' => 'emailvariant',
    ));

$container
    ->setDefinition('mautic.validator.leadlistaccess', new Definition(
        'Mautic\EmailBundle\Form\Validator\Constraints\LeadListAccessValidator',
        array(new Reference('mautic.factory'))
    ))
    ->addTag('validator.constraint_validator', array('alias' => 'leadlist_access'));