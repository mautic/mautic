<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Url;

/**
 * Class SubmitActionEmailType.
 */
class SubmitActionRepostType extends AbstractType
{
    use FormFieldTrait;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'post_url',
            'url',
            [
                'label'      => 'mautic.form.action.repost.post_url',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'    => 'form-control',
                    'preaddon' => 'fa fa-globe',
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
            'text',
            [
                'label'      => 'mautic.form.action.repost.authorization_header',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.form.action.repost.authorization_header.tooltip',
                    'preaddon' => 'fa fa-lock',
                ],
                'required' => false,
            ]
        );

        $builder->add(
            'failure_email',
            'email',
            [
                'label'      => 'mautic.form.action.repost.failure_email',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'    => 'form-control',
                    'tooltip'  => 'mautic.form.action.repost.failure_email.tooltip',
                    'preaddon' => 'fa fa-envelope',
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
                'text',
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
