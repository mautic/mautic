<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Form\Type\Auth;

use Mautic\IntegrationsBundle\Form\Type\NotBlankIfPublishedConstraintTrait;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

trait Oauth1aTwoLeggedKeysTrait
{
    use NotBlankIfPublishedConstraintTrait;

    private function addKeyFields(FormBuilderInterface $builder): void
    {
        $builder->add(
            'consumerKey',
            TextType::class,
            [
                'label'      => 'mautic.integration.oauth1a.consumer.key',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'    => 'form-control',
                ],
                'required'    => true,
                'constraints' => [$this->getNotBlankConstraint()],
            ]
        );

        $builder->add(
            'consumerSecret',
            TextType::class,
            [
                'label'      => 'mautic.integration.oauth1a.consumer.secret',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'    => 'form-control',
                ],
                'required'    => true,
                'constraints' => [$this->getNotBlankConstraint()],
            ]
        );

        $builder->add(
            'token',
            TextType::class,
            [
                'label'      => 'mautic.integration.oauth1a.token',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'    => 'form-control',
                ],
                'required'    => true,
                'constraints' => [$this->getNotBlankConstraint()],
            ]
        );

        $builder->add(
            'tokenSecret',
            TextType::class,
            [
                'label'      => 'mautic.integration.oauth1a.token.secret',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'    => 'form-control',
                ],
                'required'    => true,
                'constraints' => [$this->getNotBlankConstraint()],
            ]
        );
    }
}
