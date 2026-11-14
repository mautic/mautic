<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCrmBundle\Form\Type;

use MauticPlugin\MauticCrmBundle\Hubspot\Config;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @extends AbstractType<array<string, mixed>>
 */
final class HubspotKeysType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            Config::ACCESS_TOKEN,
            PasswordType::class,
            [
                'label'      => 'mautic.hubspot.form.accessKey',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'autocomplete' => 'off',
                    'placeholder'  => '**************',
                ],
                'required'   => false,
            ]
        );

        // A password field always renders empty, so a blank submission keeps the saved token
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            $saved = $event->getForm()->getData();
            $data  = $event->getData();

            if (is_array($data) && is_array($saved) && empty($data[Config::ACCESS_TOKEN]) && !empty($saved[Config::ACCESS_TOKEN])) {
                $data[Config::ACCESS_TOKEN] = $saved[Config::ACCESS_TOKEN];
                $event->setData($data);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined(['integration']);
        $resolver->setDefaults([
            // The token is only required while the integration is published
            'constraints' => [
                new Callback(
                    function (mixed $keys, ExecutionContextInterface $context): void {
                        $root = $context->getRoot();
                        if (!$root instanceof FormInterface || !$root->has('isPublished') || !$root->get('isPublished')->getData()) {
                            return;
                        }

                        if (empty($keys[Config::ACCESS_TOKEN])) {
                            $context->buildViolation('mautic.core.value.required')->atPath('['.Config::ACCESS_TOKEN.']')->addViolation();
                        }
                    }
                ),
            ],
        ]);
    }
}
