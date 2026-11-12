<?php

declare(strict_types=1);

namespace MauticPlugin\MauticEmailMarketingBundle\Form\Type;

use Mautic\LeadBundle\Field\FieldList;
use MauticPlugin\MauticEmailMarketingBundle\Helper\ContactFieldMapper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Maps each field of the email marketing service to a Mautic contact field.
 *
 * @extends AbstractType<array<string, string>>
 */
final class FieldMappingType extends AbstractType
{
    public function __construct(
        private readonly FieldList $fieldList,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = [];
        foreach ($this->fieldList->getFieldList() as $group => $fields) {
            $choices[$group] = array_flip($fields);
        }
        $choices['mautic.lead.report.contact_id']                        = ContactFieldMapper::CONTACT_ID;
        $choices['mautic.plugin.integration.contact.timeline.link']      = ContactFieldMapper::CONTACT_TIMELINE_LINK;
        $choices['mautic.plugin.integration.contact.donotcontact.email'] = ContactFieldMapper::CONTACT_IS_CONTACTABLE_BY_EMAIL;

        /** @var array<string, array{label: string, required: bool}> $integrationFields */
        $integrationFields = $options['integration_fields'];

        // Required fields first, then by label
        uksort($integrationFields, static fn (string $a, string $b): int => [!$integrationFields[$a]['required'], $integrationFields[$a]['label']]
            <=> [!$integrationFields[$b]['required'], $integrationFields[$b]['label']]);

        foreach ($integrationFields as $field => $details) {
            $builder->add(
                $field,
                ChoiceType::class,
                [
                    'label'              => $details['label'],
                    'translation_domain' => false,
                    'choices'            => $choices,
                    'choice_translation_domain' => true,
                    'required'           => $details['required'],
                    'placeholder'        => '',
                    'label_attr'         => ['class' => 'control-label'],
                    'attr'               => ['class' => 'form-control'],
                    'constraints'        => $details['required'] ? [new NotBlank(message: 'mautic.core.value.required')] : [],
                ]
            );
        }

        $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event): void {
            $data = $event->getData();
            $event->setData(ContactFieldMapper::normalize(is_array($data) ? $data : []));
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired('integration_fields');
        $resolver->setAllowedTypes('integration_fields', 'array');
        $resolver->setDefaults([
            'label' => 'mautic.integration.leadfield_matches',
        ]);
    }
}
