<?php

namespace Mautic\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;

/**
 * @extends AbstractType<mixed>
 */
class SubmitActionRepostType extends AbstractType
{
    use FormFieldTrait;

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'post_url',
            UrlType::class,
            [
                'label'      => 'mautic.form.action.repost.post_url',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'    => 'form-control',
                    'preaddon' => 'ri-earth-line',
                ],
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'mautic.core.value.required',
                        ]
                    ),
                    new Url(
                        [
                            'message' => 'mautic.core.valid_url_required',
                        ]
                    ),
                ],
            ]
        );

        $builder->add(
            'authorization_header',
            TextType::class,
            [
                'label'      => 'mautic.form.action.repost.authorization_header',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.form.action.repost.authorization_header.tooltip',
                    'preaddon' => 'ri-lock-fill',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'failure_email',
            EmailType::class,
            [
                'label'      => 'mautic.form.action.repost.failure_email',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.form.action.repost.failure_email.tooltip',
                    'preaddon' => 'ri-mail-line',
                ],
                'required'    => false,
                'constraints' => new Email(
                    [
                        'message' => 'mautic.core.email.required',
                    ]
                ),
            ]
        );

        $fields = $this->getFormFields($options['attr']['data-formid'], false);

        foreach ($fields as $alias => $label) {
            $builder->add(
                $alias,
                TextType::class,
                [
                    'label'      => $label." ($alias)",
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class' => 'form-control',
                    ],
                    'required' => false,
                ]
            );
        }
    }
}
