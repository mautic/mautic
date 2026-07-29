<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCloudStorageBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<array<mixed>>
 */
final class AmazonS3KeysType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $data = $event->getData() ?? [];
            if (empty($data['region'])) {
                $data['region'] = 'us-east-1';
                $event->setData($data);
            }
        });

        $builder->add(
            'client_id',
            TextType::class,
            [
                'label'      => 'mautic.integration.keyfield.clientid',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
            ]
        );

        $builder->add(
            'client_secret',
            PasswordType::class,
            [
                'label'      => 'mautic.integration.keyfield.clientsecret',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
            ]
        );

        $builder->add(
            'bucket',
            TextType::class,
            [
                'label'      => 'mautic.integration.keyfield.amazons3.bucket',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
            ]
        );

        $builder->add(
            'region',
            TextType::class,
            [
                'label'      => 'mautic.integration.Amazon.region',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
            ]
        );

        $builder->add(
            'endpoint',
            TextType::class,
            [
                'label'      => 'mautic.integration.Amazon.endpoint',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
            ]
        );
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined(['integration']);
    }
}
