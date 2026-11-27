<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Form\Type;

use Mautic\ConfigBundle\Form\Type\EscapeTransformer;
use Mautic\CoreBundle\Ai\ProviderLocation;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<array<string, mixed>>
 */
final class AiProviderType extends AbstractType
{
    public const string LOCATION_LABEL = 'mautic.core.ai.location.';

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('name', TextType::class, [
            'label'       => 'mautic.core.ai.provider_name',
            'attr'        => ['class' => 'form-control', 'data-ai-provider-name' => ''],
            'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 100)],
        ])->add('location', ChoiceType::class, [
            'label'        => 'mautic.core.ai.location',
            'choices'      => array_column(ProviderLocation::cases(), 'value'),
            'choice_label' => static fn (string $value): string => self::LOCATION_LABEL.$value,
            'attr'         => ['class' => 'form-control', 'data-ai-provider-location' => ''],
            'placeholder'  => 'mautic.core.ai.choose_location',
            'constraints'  => [new Assert\NotBlank()],
        ])->add('endpoint', TextType::class, [
            'label'       => 'mautic.core.ai.endpoint',
            'attr'        => ['class' => 'form-control', 'placeholder' => 'http://localhost:11434/v1'],
            'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 2048)],
        ])->add('api_key', PasswordType::class, [
            'label'       => 'mautic.core.ai.api_key',
            'help'        => 'mautic.core.ai.api_key.help',
            'required'    => false,
            'attr'        => ['class' => 'form-control', 'autocomplete' => 'new-password'],
            'constraints' => [new Assert\Length(max: 4096), new Assert\Regex(pattern: '/\A[\x21-\x7e]*\z/D', message: 'mautic.core.ai.error.key_format')],
        ])->add('remove_key', CheckboxType::class, [
            'label'    => 'mautic.core.ai.remove_key',
            'required' => false,
        ]);

        $escapeTransformer = new EscapeTransformer([]);
        $builder->get('name')->addModelTransformer($escapeTransformer);
        $builder->get('endpoint')->addModelTransformer($escapeTransformer);
    }
}
