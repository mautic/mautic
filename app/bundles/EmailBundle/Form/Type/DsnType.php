<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<array>
 */
class DsnType extends AbstractType
{
    /**
     * @param array<string, mixed> $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'scheme',
            TextType::class,
            [
                'label' => 'mautic.email.config.mailer.dsn.scheme',
                'attr'  => [
                    'class' => 'form-control',
                ],
            ]
        );

        $builder->add(
            'host',
            TextType::class,
            [
                'label' => 'mautic.email.config.mailer.dsn.host',
                'attr'  => [
                    'class' => 'form-control',
                ],
            ]
        );

        $builder->add(
            'port',
            TextType::class,
            [
                'label'             => 'mautic.email.config.mailer.dsn.port',
                'required'          => false,
                'attr'              => [
                    'class'    => 'form-control',
                ],
            ]
        );

        $builder->add(
            'user',
            TextType::class,
            [
                'label'             => 'mautic.email.config.mailer.dsn.user',
                'required'          => false,
                'attr'              => [
                    'class'    => 'form-control',
                ],
            ]
        );

        $builder->add(
            'password',
            TextType::class,
            [
                'label'             => 'mautic.email.config.mailer.dsn.password',
                'required'          => false,
                'attr'              => [
                    'class'    => 'form-control',
                ],
            ]
        );

        $builder->add(
            'path',
            TextType::class,
            [
                'label'             => 'mautic.email.config.mailer.dsn.path',
                'required'          => false,
                'attr'              => [
                    'class'    => 'form-control',
                ],
            ]
        );
    }
}
