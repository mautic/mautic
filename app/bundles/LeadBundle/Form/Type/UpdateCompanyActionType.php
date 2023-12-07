<?php

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Cache\ResultCacheOptions;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class UpdateCompanyActionType extends AbstractType
{
    use EntityFieldsBuildFormTrait;

    /**
     * @var FieldModel
     */
    protected $fieldModel;

    public function __construct(FieldModel $fieldModel)
    {
        $this->fieldModel = $fieldModel;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
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
