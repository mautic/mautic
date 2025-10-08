<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

/**
 * Form type for configuring campaign action to anonymize or delete user data.
 *
 * This form allows selecting which contact fields should be:
 * - Anonymized (replaced with hash or random value)
 * - Deleted (set to null)
 */
class CampaignActionAnonymizeUserDataType extends AbstractType
{
    /**
     * Field types that can be anonymized or deleted.
     */
    public const FIELD_TYPE_ALLOWED = [
        'text',
        'email',
    ];

    /**
     * Default fields to delete (set to null).
     * Format: ['Field Label' => Field ID].
     */
    public const DEFAULT_VALUES_TO_DELETE = [
        'First Name' => 2,
        'Last Name'  => 3,
    ];

    /**
     * Default fields to anonymize (hash/pseudonymize).
     * Format: ['Field Label' => Field ID].
     */
    public const DEFAULT_VALUES_TO_ANONYMIZE = [
        'Email' => 6,
    ];

    public function __construct(
        private FieldModel $fieldModel,
        private Translator $translator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Pseudonymize toggle - determines if hashing should be reversible
        $builder->add(
            'pseudonymize',
            YesNoButtonGroupType::class,
            [
                'label' => 'mautic.lead.lead.events.anonymize_user_data',
                'data'  => $options['data']['pseudonymize'] ?? false,
                'attr'  => [
                    'tooltip' => 'mautic.campaign.lead.action_anonymizeuserdata.tooltip',
                ],
            ]
        );

        // Fields to anonymize - these will be hashed/pseudonymized
        $choicesAnonymize = $this->getFieldChoices(false);
        $builder->add(
            'fieldsToAnonymize',
            FieldListType::class,
            [
                'label'   => 'mautic.lead.lead.events.fields_to_anonymize',
                'choices' => $choicesAnonymize,
                'data'    => $options['data']['fieldsToAnonymize'] ?? self::DEFAULT_VALUES_TO_ANONYMIZE,
            ]
        );

        // Fields to delete - these will be set to null
        $choicesToDelete = $this->getFieldChoices(excludeUniqueFields: true);
        $builder->add(
            'fieldsToDelete',
            FieldListType::class,
            [
                'label'       => 'mautic.lead.lead.events.delete_user_data',
                'choices'     => $choicesToDelete,
                'constraints' => [$this->validateFieldSelection()],
                'data'        => $options['data']['fieldsToDelete'] ?? self::DEFAULT_VALUES_TO_DELETE,
            ]
        );

        // Informational text about audit log deletion (hidden field for display purposes)
        $builder->add(
            'customText',
            TextType::class,
            [
                'label'      => $this->translator->trans('mautic.campaign.lead.action_anonymizeuserdata.alert.auditlog'),
                'label_attr' => ['class' => 'text-muted'],
                'data'       => $this->translator->trans('mautic.campaign.lead.action_anonymizeuserdata.alert.auditlog'),
                'mapped'     => false,
                'required'   => false,
                'attr'       => [
                    'readonly' => true,
                    'class'    => 'custom-text-class',
                    'style'    => 'display: none;',
                ],
            ]
        );
    }

    /**
     * Retrieves available contact fields for anonymization/deletion.
     *
     * @param bool $excludeUniqueFields If true, excludes unique identifier fields (like email for contacts)
     *
     * @return array<string, int> Array of field labels as keys and field IDs as values
     */
    private function getFieldChoices(bool $excludeUniqueFields = true): array
    {
        $findBy = ['type' => self::FIELD_TYPE_ALLOWED];

        if ($excludeUniqueFields) {
            $findBy['isUniqueIdentifer'] = false;
        }

        $leadFields = $this->fieldModel->getRepository()->findBy($findBy);
        $choices    = [];

        foreach ($leadFields as $field) {
            $choices[$field->getLabel()] = $field->getId();
        }

        return $choices;
    }

    public function getBlockPrefix(): string
    {
        return 'lead_action_anonymizeuserdata';
    }

    /**
     * Creates validation callback to ensure field selection is valid.
     *
     * Validates that:
     * 1. At least one field is selected (either to anonymize or delete)
     * 2. No field is selected for both anonymization AND deletion
     */
    private function validateFieldSelection(): Callback
    {
        return new Callback(
            function ($validateMe, ExecutionContextInterface $context): void {
                $data = $context->getRoot()->getData();

                // Ensure at least one field is selected
                if (
                    !isset($data['properties']['fieldsToDelete'], $data['properties']['fieldsToAnonymize'])
                    || (empty($data['properties']['fieldsToDelete']) && empty($data['properties']['fieldsToAnonymize']))
                ) {
                    $context->buildViolation('mautic.lead.lead.events.error.empty_fields')
                        ->addViolation();

                    return;
                }

                // Check if any field is selected for both anonymization and deletion
                $duplicateFields = array_intersect(
                    $data['properties']['fieldsToDelete'],
                    $data['properties']['fieldsToAnonymize']
                );

                if (!empty($duplicateFields)) {
                    // Add general error message
                    $context->buildViolation('mautic.lead.lead.events.error.fields_to_anonymize_deleted')
                        ->addViolation();

                    // Add specific field names that are duplicated
                    $fields = $this->fieldModel->getRepository()->findBy(['id' => $duplicateFields]);
                    foreach ($fields as $field) {
                        $context->buildViolation($field->getLabel())
                            ->addViolation();
                    }
                }
            }
        );
    }
}
