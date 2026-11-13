<?php

declare(strict_types=1);

namespace MauticPlugin\MauticEmailMarketingBundle\Form\Type;

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
 * @extends AbstractType<array<string, mixed>>
 */
abstract class AbstractKeysType extends AbstractType
{
    /**
     * @return array<string, string> label by key
     */
    abstract protected function getKeys(): array;

    /**
     * Keys shown as password fields, which keep their saved value when submitted blank.
     *
     * @return string[]
     */
    abstract protected function getSecretKeys(): array;

    /**
     * @param array<string, mixed> $keys the saved keys with the submitted ones applied
     *
     * @return string[] required keys that have no value
     */
    protected function getMissingKeys(array $keys): array
    {
        return array_values(array_filter(array_keys($this->getKeys()), static fn (string $key): bool => empty($keys[$key])));
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach ($this->getKeys() as $key => $label) {
            $isSecret = in_array($key, $this->getSecretKeys(), true);

            $builder->add(
                $key,
                $isSecret ? PasswordType::class : TextType::class,
                [
                    'label'      => $label,
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'        => 'form-control',
                        'autocomplete' => 'off',
                    ] + ($isSecret ? ['placeholder' => '**************'] : []),
                    'required'   => false,
                ]
            );
        }

        // A password field always renders empty, so a blank submission keeps the saved value.
        $builder->addEventListener(FormEvents::SUBMIT, function (FormEvent $event): void {
            $saved = $event->getForm()->getData();
            $data  = $event->getData();

            if (!is_array($data) || !is_array($saved)) {
                return;
            }

            foreach ($this->getSecretKeys() as $key) {
                if (empty($data[$key]) && !empty($saved[$key])) {
                    $data[$key] = $saved[$key];
                }
            }

            $event->setData($data);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined(['integration']);
        $resolver->setDefaults([
            // Like the former keys form, the keys are only required while the integration is published.
            'constraints' => [
                new Callback(
                    function (mixed $keys, ExecutionContextInterface $context): void {
                        $root = $context->getRoot();
                        if (!$root instanceof FormInterface || !$root->has('isPublished') || !$root->get('isPublished')->getData()) {
                            return;
                        }

                        foreach ($this->getMissingKeys(is_array($keys) ? $keys : []) as $key) {
                            $context->buildViolation('mautic.core.value.required')->atPath('['.$key.']')->addViolation();
                        }
                    }
                ),
            ],
        ]);
    }
}
