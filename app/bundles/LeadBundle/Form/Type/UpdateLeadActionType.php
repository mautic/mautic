<?php

namespace Mautic\LeadBundle\Form\Type;

use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<mixed>
 */
class UpdateLeadActionType extends AbstractType
{
    use EntityFieldsBuildFormTrait;

    public function __construct(
        private FieldModel $fieldModel
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $leadFields = $this->fieldModel->getEntities(
            [
                'force' => [
                    [
                        'column' => 'f.isPublished',
                        'expr'   => 'eq',
                        'value'  => true,
                    ],
                ],
                'hydration_mode' => 'HYDRATE_ARRAY',
            ]
        );

        $options['fields']                      = $leadFields;
        $options['ignore_required_constraints'] = true;
        $options['ignore_date_type']            = true;

        $this->getFormFields($builder, $options);
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'updatelead_action';
    }
}
