<?php

namespace Mautic\UserBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContactType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'msg_subject',
                TextType::class,
                [
                    'label'       => 'mautic.email.subject',
                    'label_attr'  => ['class' => 'control-label'],
                    'attr'        => ['class' => 'form-control'],
                    'constraints' => [
                        new NotBlank(['message' => 'Subject should not be blank.']),
                        new Length(['min' => 3]),
                    ],
                ]
            )
            ->add(
                'msg_body',
                TextareaType::class,
                [
                    'label'      => 'mautic.user.user.contact.message',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class' => 'form-control',
                        'rows'  => 10,
                    ],
                    'constraints' => [
                        new NotBlank(['message' => 'Message should not be blank.']),
                        new Length(['min' => 5]),
                    ],
                ]
            )
            ->add(
                'entity',
                HiddenType::class,
                [
                    'attr' => [
                        'autocomplete' => 'off',
                    ],
                ]
            )
            ->add(
                'id',
                HiddenType::class,
                [
                    'attr' => [
                        'autocomplete' => 'off',
                    ],
                ]
            )
            ->add(
                'returnUrl',
                HiddenType::class,
                [
                    'attr' => [
                        'autocomplete' => 'off',
                    ],
                ]
            )
            ->add('buttons', FormButtonsType::class, [
                'save_text'  => 'mautic.user.user.contact.send',
                'save_icon'  => 'fa fa-send',
                'apply_text' => false,
            ]);

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'contact';
    }
}
