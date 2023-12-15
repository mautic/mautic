<?php

namespace Mautic\LeadBundle\Form\Type;

use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class UpdateCompanyActionType extends AbstractType
{
    use EntityFieldsBuildFormTrait;

    public function __construct(
        protected FieldModel $fieldModel
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

        $this->getFormFields($builder, $options, 'company');
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'updatecompany_action';
    }
}
