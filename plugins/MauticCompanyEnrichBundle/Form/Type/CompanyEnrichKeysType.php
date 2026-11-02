<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCompanyEnrichBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/** @extends AbstractType<array<string, mixed>> */
final class CompanyEnrichKeysType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('apikey', PasswordType::class, [
            'label' => 'mautic.companyenrich.apikey',
            'label_attr' => ['class' => 'form-label'],
            'attr' => ['class' => 'form-control', 'autocomplete' => 'new-password'],
            'required' => true,
            'always_empty' => false,
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefined(['integration']);
    }
}
