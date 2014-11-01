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

$container->setDefinition('mautic.form.type.campaigntrigger_email', new Definition(
    'Mautic\EmailBundle\Form\Type\CampaignTriggerEmailOpenType'
))
    ->addTag('form.type', array(
        'alias' => 'campaigntrigger_email',
    ));

$container->setDefinition('mautic.form.type.campaignaction_email', new Definition(
    'Mautic\EmailBundle\Form\Type\CampaignActionEmailSendType'
))
    ->addTag('form.type', array(
        'alias' => 'campaignaction_email',
    ));

$container->setDefinition('mautic.validator.leadlistaccess', new Definition(
        'Mautic\EmailBundle\Form\Validator\Constraints\LeadListAccessValidator',
        array(new Reference('mautic.factory'))
    ))
    ->addTag('validator.constraint_validator', array('alias' => 'leadlist_access'));

$container->setDefinition('mautic.form.type.formsubmit_sendemail_admin', new Definition(
    'Mautic\EmailBundle\Form\Type\FormSubmitActionSendAdminEmailType'
))
    ->addTag('form.type', array(
        'alias' => 'email_submitaction_sendemail_admin',
    ));

$container->setDefinition('mautic.form.type.formsubmit_sendemail_lead', new Definition(
    'Mautic\EmailBundle\Form\Type\FormSubmitActionSendLeadEmailType'
))
    ->addTag('form.type', array(
        'alias' => 'email_submitaction_sendemail_lead',
    ));
