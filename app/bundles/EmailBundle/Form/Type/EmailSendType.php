<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class EmailSendType
 *
 * @package Mautic\EmailBundle\Form\Type
 */
class EmailSendType extends AbstractType
{
    protected $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'email',
            'email_list',
            [
                'label'       => 'mautic.email.send.selectemails',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => [
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.email.choose.emails_descr',
                    'onchange' => 'Mautic.disabledEmailAction()'
                ],
                'multiple'    => false,
                'required'    => true,
                'constraints' => [
                    new NotBlank(
                        ['message' => 'mautic.email.chooseemail.notblank']
                    )
                ]
            ]
        );

        if (!empty($options['with_email_types'])) {
            $builder->add(
                'email_type',
                'button_group',
                [
                    'choices'    => [
                        'transactional' => 'mautic.email.send.emailtype.transactional',
                        'marketing'     => 'mautic.email.send.emailtype.marketing',
                    ],
                    'label'      => 'mautic.email.send.emailtype',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'   => 'form-control',
                        'tooltip' => 'mautic.email.send.emailtype.tooltip',
                    ],
                    'data'       => (!isset($options['data']['email_type'])) ? 'transactional' : $options['data']['email_type']
                ]
            );
        }

        if (!empty($options['update_select'])) {
            $windowUrl = $this->factory->getRouter()->generate(
                'mautic_email_action',
                [
                    'objectAction' => 'new',
                    'contentOnly'  => 1,
                    'updateSelect' => $options['update_select']
                ]
            );

            $builder->add(
                'newEmailButton',
                'button',
                [
                    'attr'  => [
                        'class'   => 'btn btn-primary btn-nospin',
                        'onclick' => 'Mautic.loadNewEmailWindow({
                        "windowUrl": "'.$windowUrl.'"
                    })',
                        'icon'    => 'fa fa-plus'
                    ],
                    'label' => 'mautic.email.send.new.email'
                ]
            );

            $email = $options['data']['email'];

            // create button edit email
            $windowUrlEdit = $this->factory->getRouter()->generate(
                'mautic_email_action',
                [
                    'objectAction' => 'edit',
                    'objectId'     => 'emailId',
                    'contentOnly'  => 1,
                    'updateSelect' => $options['update_select']
                ]
            );

            $builder->add(
                'editEmailButton',
                'button',
                [
                    'attr'  => [
                        'class'    => 'btn btn-primary btn-nospin',
                        'onclick'  => 'Mautic.loadNewEmailWindow(Mautic.standardEmailUrl({"windowUrl": "'.$windowUrlEdit.'"}))',
                        'disabled' => !isset($email),
                        'icon'     => 'fa fa-edit'
                    ],
                    'label' => 'mautic.email.send.edit.email'
                ]
            );

            // create button preview email
            $windowUrlPreview = $this->factory->getRouter()->generate('mautic_email_preview', ['objectId' => 'emailId']);

            $builder->add(
                'previewEmailButton',
                'button',
                [
                    'attr'  => [
                        'class'    => 'btn btn-primary btn-nospin',
                        'onclick'  => 'Mautic.loadNewEmailWindow(Mautic.standardEmailUrl({"windowUrl": "'.$windowUrlPreview.'"}))',
                        'disabled' => !isset($email),
                        'icon'     => 'fa fa-external-link'
                    ],
                    'label' => 'mautic.email.send.preview.email'
                ]
            );
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'with_email_types' => false
            ]
        );

        $resolver->setDefined(['update_select', 'with_email_types']);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "emailsend_list";
    }
}
