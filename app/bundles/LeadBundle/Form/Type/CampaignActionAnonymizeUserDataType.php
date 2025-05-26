<?php

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\CoreBundle\Translation\Translator;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\PluginBundle\Entity\Integration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CampaignActionAnonymizeUserDataType extends AbstractType
{
    public const FIELD_TYPE_ALLOWED = [
        'text',
        'email',
    ];

    public const DEFAULT_VALUES_TO_DELETE = [
        'First Name' => 2,
        'Last Name'  => 3,
    ];

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

        $choicesToDelete = $this->getFieldChoices();
        $builder->add(
            'fieldsToDelete',
            FieldListType::class,
            [
                'label'       => 'mautic.lead.lead.events.delete_user_data',
                'choices'     => $choicesToDelete,
                'constraints' => [$this->checkFieldsSimilarity()],
                'data'        => $options['data']['fieldsToDelete'] ?? self::DEFAULT_VALUES_TO_DELETE,
            ]
        );

        // Add a text at the end of the form
        $builder->add(
            'customText',
            TextType::class,
            [
                'label'      => $this->translator->trans('mautic.campaign.lead.action_anonymizeuserdata.alert.auditlog'), // No label for the text
                'label_attr' => ['class' => 'text-muted'], // Optional: Add a custom CSS class to the label
                'data'       => $this->translator->trans('mautic.campaign.lead.action_anonymizeuserdata.alert.auditlog'),
                'mapped'     => false, // Not mapped to any entity field
                'required'   => false, // Not required
                'attr'       => [
                    'readonly' => true, // Make it read-only
                    'class'    => 'custom-text-class', // Optional: Add a custom CSS class
                    'style'    => 'display: none;', // Optional: Add custom styles
                ],
            ]
        );
    }

    /**
     * @return array<string, int>
     */
    private function getFieldChoices(bool $checkIsUniqueField=true): array
    {
        $findBy['type'] = self::FIELD_TYPE_ALLOWED;
        if ($checkIsUniqueField) {
            $findBy['isUniqueIdentifer'] = false;
        }
        $leadFields    = $this->fieldModel->getRepository()->findBy($findBy);
        $choices       = [];

        foreach ($leadFields as $field) {
            $choices[$field->getLabel()] = $field->getId();
        }

        return $choices;
    }

    public function getBlockPrefix(): string
    {
        return 'lead_action_anonymizeuserdata';
    }

    private function checkFieldsSimilarity(): Callback
    {
        return new Callback(
            function ($validateMe, ExecutionContextInterface $context): void {
                /** @var Integration $data */
                $data = $context->getRoot()->getData();

                if (
                    !isset($data['properties']['fieldsToDelete'], $data['properties']['fieldsToAnonymize'])
                    || (empty($data['properties']['fieldsToDelete']) && empty($data['properties']['fieldsToAnonymize']))
                ) {
                    $context->buildViolation('mautic.lead.lead.events.error.empty_fields')
                        ->addViolation();
                    $data['properties']['fieldsToDelete']    = [];
                    $data['properties']['fieldsToAnonymize'] = [];
                }

                $fieldMatch = array_intersect($data['properties']['fieldsToDelete'], $data['properties']['fieldsToAnonymize']);

                if (!empty($fieldMatch)) {
                    $fields = $this->fieldModel->getRepository()->findBy(['id' => $fieldMatch]);
                    $context->buildViolation('mautic.lead.lead.events.error.fields_to_anonymize_deleted')
                        ->addViolation();
                    foreach ($fields as $field) {
                        $context->buildViolation($field->getLabel())
                            ->addViolation();
                    }
                }
            }
        );
    }
}
