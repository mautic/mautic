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
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * @extends AbstractType<array<mixed>>
 */
final class AmazonS3KeysType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Fields are built here (rather than directly in buildForm) because client_secret's
        // "required" state depends on whether a value was already saved, mirroring the old
        // KeysType behaviour: required the first time, optional on subsequent saves so leaving
        // the always-blank password field untouched doesn't block re-saving other fields.
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event): void {
            $form = $event->getForm();
            $data = $event->getData() ?? [];

            if (empty($data['region'])) {
                $data['region'] = 'us-east-1';
                $event->setData($data);
            }

            $form->add(
                'client_id',
                TextType::class,
                [
                    'label'      => 'mautic.integration.keyfield.clientid',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => ['class' => 'form-control'],
                    'required'   => true,
                    'constraints' => [new NotBlank()],
                ]
            );

            $clientSecretRequired = empty($data['client_secret']);
            $form->add(
                'client_secret',
                PasswordType::class,
                [
                    'label'       => 'mautic.integration.keyfield.clientsecret',
                    'label_attr'  => ['class' => 'control-label'],
                    'attr'        => ['class' => 'form-control', 'placeholder' => '**************', 'autocomplete' => 'off'],
                    'required'    => $clientSecretRequired,
                    'constraints' => $clientSecretRequired ? [new NotBlank()] : [],
                ]
            );

            $form->add(
                'bucket',
                TextType::class,
                [
                    'label'       => 'mautic.integration.keyfield.amazons3.bucket',
                    'label_attr'  => ['class' => 'control-label'],
                    'attr'        => ['class' => 'form-control'],
                    'required'    => true,
                    'constraints' => [new NotBlank()],
                ]
            );

            $form->add(
                'region',
                TextType::class,
                [
                    'label'      => 'mautic.integration.Amazon.region',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => ['class' => 'form-control'],
                    'required'   => false,
                ]
            );

            $form->add(
                'endpoint',
                TextType::class,
                [
                    'label'      => 'mautic.integration.Amazon.endpoint',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => ['class' => 'form-control'],
                    'required'   => false,
                ]
            );
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined(['integration']);
    }
}
