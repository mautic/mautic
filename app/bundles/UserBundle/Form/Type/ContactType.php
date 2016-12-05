<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class ContactType.
 */
class ContactType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('msg_subject', 'text', [
                'label'       => 'mautic.email.subject',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => ['class' => 'form-control'],
                'constraints' => [
                    new NotBlank(['message' => 'Subject should not be blank.']),
                    new Length(['min' => 3]),
                ],
            ])
            ->add('msg_body', 'textarea', [
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
            ])
            ->add('entity', 'hidden', [
                'attr' => [
                    'autocomplete' => 'off',
                ],
            ])
            ->add('id', 'hidden', [
                'attr' => [
                    'autocomplete' => 'off',
                ],
            ])
            ->add('returnUrl', 'hidden', [
                'attr' => [
                    'autocomplete' => 'off',
                ],
            ])
            ->add('buttons', 'form_buttons', [
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
    public function getName()
    {
        return 'contact';
    }
}
