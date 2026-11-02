<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCompanyEnrichBundle\Form\Type;

use MauticPlugin\MauticCompanyEnrichBundle\Api\Client;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Length;

/** @extends AbstractType<array<string, mixed>> */
final class ResearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = [];
        foreach (Client::OPERATIONS as $name => $definition) {
            $choices[$definition[2]] = $name;
        }
        $builder->add('operation', ChoiceType::class, ['choices' => $choices, 'label' => 'mautic.companyenrich.operation', 'attr' => ['class' => 'form-control']]);
        foreach (['query', 'body'] as $field) {
            $builder->add($field, TextareaType::class, [
                'label' => 'mautic.companyenrich.'.$field,
                'required' => false,
                'attr' => ['class' => 'form-control', 'rows' => 4, 'spellcheck' => 'false'],
                'constraints' => [new Length(max: 65536)],
            ]);
        }
        $builder->add('run', SubmitType::class, ['label' => 'mautic.companyenrich.run', 'attr' => ['class' => 'btn btn-primary']]);
    }
}
