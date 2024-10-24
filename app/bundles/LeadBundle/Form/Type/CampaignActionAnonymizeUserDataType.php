<?php

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
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

    public function __construct(private FieldModel $fieldModel)
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
            ]
        );
        $choices = $this->getFieldChoices();
        $builder->add(
            'fieldsToAnonymize',
            FieldListType::class,
            [
                'label'   => 'mautic.lead.lead.events.fields_to_anonymize',
                'choices' => $choices,
            ]
        );
        $builder->add(
            'fieldsToDelete',
            FieldListType::class,
            [
                'label'       => 'mautic.lead.lead.events.delete_user_data',
                'choices'     => $choices,
                'constraints' => [$this->checkFieldsSimilarity()],
            ]
        );
    }

    /**
     * @return array<string, int>
     */
    private function getFieldChoices(): array
    {
        $leadFields = $this->fieldModel->getRepository()->findBy(
            [
                'type'              => self::FIELD_TYPE_ALLOWED,
                'isUniqueIdentifer' => false,
            ]
        );
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
