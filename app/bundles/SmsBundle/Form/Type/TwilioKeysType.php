<?php

declare(strict_types=1);

namespace Mautic\SmsBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * @extends AbstractType<array<mixed>>
 */
final class TwilioKeysType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'username',
            TextType::class,
            [
                'label'      => 'mautic.sms.config.form.sms.username',
                'label_attr' => ['class' => 'form-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'autocomplete' => 'off',
                ],
                'required'   => true,
            ]
        );

        $builder->add(
            'password',
            PasswordType::class,
            [
                'label'      => 'mautic.sms.config.form.sms.password',
                'label_attr' => ['class' => 'form-label'],
                'attr'       => [
                    'class'        => 'form-control',
                    'placeholder'  => '**************',
                    'autocomplete' => 'off',
                ],
                'required'   => false,
            ]
        );

        // A password field always renders empty, so a blank submission keeps the saved auth token.
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            $saved = $event->getForm()->getData();
            $data  = $event->getData();

            if (is_array($data) && empty($data['password']) && is_array($saved) && !empty($saved['password'])) {
                $data['password'] = $saved['password'];
                $event->setData($data);
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined(['integration']);
        $resolver->setDefaults([
            // Like the legacy keys form, the keys are only required while the integration is published.
            'constraints' => [
                new Callback(
                    function (mixed $keys, ExecutionContextInterface $context): void {
                        $root = $context->getRoot();
                        if (!$root instanceof FormInterface || !$root->has('isPublished') || !$root->get('isPublished')->getData()) {
                            return;
                        }

                        foreach (['username', 'password'] as $key) {
                            if (!is_array($keys) || empty($keys[$key])) {
                                $context->buildViolation('mautic.core.value.required')->atPath('['.$key.']')->addViolation();
                            }
                        }
                    }
                ),
            ],
        ]);
    }
}
