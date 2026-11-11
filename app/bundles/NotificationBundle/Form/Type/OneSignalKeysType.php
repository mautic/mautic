<?php

declare(strict_types=1);

namespace Mautic\NotificationBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @extends AbstractType<array<mixed>>
 */
final class OneSignalKeysType extends AbstractType
{
    private const array KEYS = [
        'app_id'        => 'mautic.notification.config.form.notification.app_id',
        'safari_web_id' => 'mautic.notification.config.form.notification.safari_web_id',
        'rest_api_key'  => 'mautic.notification.config.form.notification.rest_api_key',
        'gcm_sender_id' => 'mautic.notification.config.form.notification.gcm_sender_id',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach (self::KEYS as $key => $label) {
            $builder->add(
                $key,
                TextType::class,
                [
                    'label'       => $label,
                    'label_attr'  => ['class' => 'form-label'],
                    'attr'        => [
                        'class'        => 'form-control',
                        'autocomplete' => 'off',
                    ],
                    'required'    => true,
                    // Like the legacy keys form, a key is only required while the integration is published.
                    'constraints' => [
                        new Callback(
                            function (mixed $value, ExecutionContextInterface $context): void {
                                $root = $context->getRoot();
                                if (empty($value) && $root instanceof FormInterface && $root->has('isPublished') && $root->get('isPublished')->getData()) {
                                    $context->buildViolation('mautic.core.value.required')->addViolation();
                                }
                            }
                        ),
                    ],
                ]
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined(['integration']);
    }
}
