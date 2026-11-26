<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Form\Type;

use Mautic\CoreBundle\Ai\ModelWorkload;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractType<array<string, mixed>>
 */
final class ConfigAiType extends AbstractType
{
    public const string ALIAS = 'aiconfig';

    public const string PROVIDERS = 'ai_providers';

    public const string ALLOW_CLOUD = 'ai_allow_cloud';

    public function __construct(
        private readonly TranslatorInterface $translator
    )
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(self::PROVIDERS, CollectionType::class, [
            'entry_type'     => AiProviderType::class,
            'allow_add'      => true,
            'allow_delete'   => true,
            'prototype_name' => '__ai_provider__',
            'constraints'    => [new Assert\Count(max: 20)],
        ])->add(self::ALLOW_CLOUD, CheckboxType::class, [
            'label'    => 'mautic.core.ai.allow_cloud',
            'required' => false,
        ]);

        foreach (ModelWorkload::cases() as $workload) {
            $builder->add('ai_'.$workload->value.'_model', TextType::class, [
                'label'       => 'mautic.core.ai.model',
                'required'    => false,
                'attr'        => ['class' => 'form-control', 'data-ai-model' => ''],
                'constraints' => [new Assert\Length(max: 200)],
            ]);
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, $this->addProviderChoices(...));
        $builder->addEventListener(FormEvents::PRE_SUBMIT, $this->addProviderChoices(...));
    }

    private function addProviderChoices(FormEvent $event): void
    {
        $data = $event->getData();
        $rows = $data[self::PROVIDERS] ?? [];
        $choices = [];
        if (is_array($rows)) {
            foreach ($rows as $id => $row) {
                if (!is_int($id) || $id < 0 || !is_array($row) || !is_string($row['name'] ?? null) || !is_string($row['location'] ?? null)) {
                    continue;
                }

                $choices[$id] = sprintf('%s (%s)', $row['name'], $this->translator->trans(AiProviderType::LOCATION_LABEL.$row['location']));
            }
        }

        foreach (ModelWorkload::cases() as $workload) {
            $event->getForm()->add('ai_'.$workload->value.'_provider', ChoiceType::class, [
                'label'                    => 'mautic.core.ai.provider',
                'required'                 => false,
                'placeholder'              => 'mautic.core.ai.no_provider',
                'choices'                  => array_map(strval(...), array_keys($choices)),
                'choice_label'             => static fn (string $choice): string => $choices[$choice],
                'choice_translation_domain' => false,
                'attr'                     => ['class' => 'form-control', 'data-ai-provider-select' => ''],
            ]);
        }
    }
}
