<?php

namespace Mautic\LeadBundle\Form\Type;

use Doctrine\ORM\EntityManager;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\PluginBundle\Entity\Integration;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class CampaignActionAnonymizeUserDataType extends AbstractType
{
    public const FIELD_TYPE_ALLOWED = [
        'text',
        'email',
    ];

    public function __construct(private FieldModel $fieldModel, private EntityManager $entityManager)
    {
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
        $choicesAnonymize = $this->getFieldChoices(false, true);
        $builder->add(
            'fieldsToAnonymize',
            FieldListType::class,
            [
                'label'   => 'mautic.lead.lead.events.fields_to_anonymize',
                'choices' => $choicesAnonymize,
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
            ]
        );
    }

    /**
     * @return array<string, int>
     */
    private function getFieldChoices(bool $checkIsUniqueField=true, bool $validLessThan64Char = false): array
    {
        $findBy['type'] = self::FIELD_TYPE_ALLOWED;
        if ($checkIsUniqueField) {
            $findBy['isUniqueIdentifer'] = false;
        }
        $leadFields    = $this->fieldModel->getRepository()->findBy($findBy);
        $choices       = [];
        $columnsLength = $this->getLeadCompanyColumnsLenght();

        foreach ($leadFields as $field) {
            if ($validLessThan64Char && $this->getCharLengthLimit($field, $columnsLength) < 64) {
                continue;
            }
            $choices[$field->getLabel()] = $field->getId();
        }

        return $choices;
    }

    private function getCharLengthLimit(LeadField $leadField, array $leadsCompanyColumnsLength): int
    {
        $alias = $leadField->getAlias();
        $key   = 'companies';
        if ('lead' === $leadField->getObject()) {
            $key = 'leads';
        }
        if (isset($leadsCompanyColumnsLength[$key][$alias])) {
            return $leadsCompanyColumnsLength[$key][$alias];
        }

        return $leadField->getCharLengthLimit();
    }

    private function getLeadCompanyColumnsLenght(): array
    {
        $leadMetadata    = $this->entityManager->getClassMetadata(Lead::class);
        $companyMetadata = $this->entityManager->getClassMetadata(Company::class);
        $columnsLength   = [
            'leads'     => [],
            'companies' => [],
        ];
        foreach ($leadMetadata->fieldMappings as $fieldName => $fieldMapping) {
            if (isset($fieldMapping['length'])) {
                $columnsLength['leads'][$fieldName] = $fieldMapping['length'];
            }
        }

        foreach ($companyMetadata->fieldMappings as $fieldName => $fieldMapping) {
            if (isset($fieldMapping['length'])) {
                $columnsLength['companies'][$fieldName] = $fieldMapping['length'];
            }
        }

        return $columnsLength;
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
