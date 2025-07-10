<?php

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Cache\ResultCacheOptions;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<mixed>
 */
class UpdateCompanyActionType extends AbstractType
{
    use EntityFieldsBuildFormTrait;

    public function __construct(
        protected FieldModel $fieldModel,
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
                'result_cache'   => new ResultCacheOptions(LeadField::CACHE_NAMESPACE),
            ]
        );

        $options['fields']                      = $leadFields;
        $options['ignore_required_constraints'] = true;
        $options['use_nullable_yes_no_type']    = true;

        $this->getFormFields($builder, $options, 'company');
    }

    public function getBlockPrefix(): string
    {
        return 'updatecompany_action';
    }
}
