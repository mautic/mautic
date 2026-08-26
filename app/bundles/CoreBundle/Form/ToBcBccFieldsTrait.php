<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Form;

use Mautic\EmailBundle\Validator\MultipleEmailsValid;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

trait ToBcBccFieldsTrait
{
    protected function addToBcBccFields(FormBuilderInterface $builder, bool $toRequired = false): void
    {
        $multipleEmailConstraint = new MultipleEmailsValid();
        $toConstraints           = [$multipleEmailConstraint];
        $toAttr                  = [
            'class'   => 'form-control',
            'tooltip' => 'mautic.core.send.email.to.multiple.addresses',
        ];

        if ($toRequired) {
            $toConstraints[] = new NotBlank(message: 'mautic.core.value.required');
        } else {
            $toAttr['placeholder'] = 'mautic.core.optional';
        }

        $builder->add(
            'to',
            TextType::class,
            [
                'label'       => 'mautic.core.send.email.to',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => $toAttr,
                'required'    => $toRequired,
                'constraints' => $toConstraints,
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
                'constraints' => $multipleEmailConstraint,
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
                'constraints' => $multipleEmailConstraint,
            ]
        );
    }
}
