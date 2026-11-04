<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\Type;

use Mautic\LeadBundle\Helper\FormFieldHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @extends AbstractType<array{locale: string, publicName: string}>
 */
final class LeadListTranslationType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('locale', ChoiceType::class, [
            'label'       => 'mautic.lead.list.translation.locale',
            'choices'     => FormFieldHelper::getLocaleChoices(),
            'placeholder' => 'mautic.lead.list.translation.choose_locale',
            'attr'        => ['class' => 'form-control'],
            'constraints' => [new Assert\NotBlank()],
        ]);

        $builder->add('publicName', TextType::class, [
            'label'       => 'mautic.lead.list.form.publicname',
            'attr'        => ['class' => 'form-control'],
            'constraints' => [new Assert\NotBlank(), new Assert\Length(max: 255)],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => null]);
    }
}
