<?php

namespace Mautic\EmailBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\EmailBundle\Validator\EmailOrEmailTokenList;
use Mautic\UserBundle\Form\Type\UserListType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EmailToUserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('useremail',
            EmailSendType::class, [
                'label' => 'mautic.email.emails',
                'attr'  => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.email.choose.emails_descr',
                ],
                'update_select' => empty($options['update_select']) ? 'formaction_properties_useremail_email' : $options['update_select'],
            ]
        );

        $builder->add(
            'user_id',
            UserListType::class,
            [
                'label'      => 'mautic.email.form.users',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.core.help.autocomplete',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'to_owner',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.form.action.send.email.to.owner',
                'data'  => isset($options['data']['to_owner']) ? $options['data']['to_owner'] : false,
            ]
        );

        $builder->add(
            'to',
            TextType::class,
            [
                'label'      => 'mautic.core.send.email.to',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'placeholder' => 'mautic.core.optional',
                    'tooltip'     => 'mautic.core.send.email.to.multiple.addresses',
                ],
                'required'    => false,
                'constraints' => new EmailOrEmailTokenList(),
            ]
        );

        $builder->add(
            'cc',
            TextType::class,
            [
                'label'      => 'mautic.core.send.email.cc',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'placeholder' => 'mautic.core.optional',
                    'tooltip'     => 'mautic.core.send.email.to.multiple.addresses',
                ],
                'required'    => false,
                'constraints' => new EmailOrEmailTokenList(),
            ]
        );

        $builder->add(
            'bcc',
            TextType::class,
            [
                'label'      => 'mautic.core.send.email.bcc',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'       => 'form-control',
                    'placeholder' => 'mautic.core.optional',
                    'tooltip'     => 'mautic.core.send.email.to.multiple.addresses',
                ],
                'required'    => false,
                'constraints' => new EmailOrEmailTokenList(),
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'label' => false,
        ]);

        $resolver->setDefined(['update_select']);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'email_to_user';
    }
}
