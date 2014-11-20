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

$container->setDefinition('mautic.form.type.email_list', new Definition(
    'Mautic\EmailBundle\Form\Type\EmailListType',
    array(new Reference('mautic.factory'))
))
    ->addTag('form.type', array(
        'alias' => 'email_list',
    ));

$container->setDefinition('mautic.form.type.emailopen_list', new Definition(
    'Mautic\EmailBundle\Form\Type\EmailOpenType'
))
    ->addTag('form.type', array(
        'alias' => 'emailopen_list',
    ));

$container->setDefinition('mautic.form.type.emailsend_list', new Definition(
    'Mautic\EmailBundle\Form\Type\EmailSendType'
))
    ->addTag('form.type', array(
        'alias' => 'emailsend_list',
    ));

$container->setDefinition('mautic.validator.leadlistaccess', new Definition(
        'Mautic\LeadBundle\Form\Validator\Constraints\LeadListAccessValidator',
        array(new Reference('mautic.factory'))
    ))
    ->addTag('validator.constraint_validator', array('alias' => 'leadlist_access'));

$container->setDefinition('mautic.form.type.formsubmit_sendemail_admin', new Definition(
    'Mautic\EmailBundle\Form\Type\FormSubmitActionSendAdminEmailType'
))
    ->addTag('form.type', array(
        'alias' => 'email_submitaction_sendemail_admin',
    ));