<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class UpdateLeadActionType extends AbstractType
{
    const FIELD_TYPE_TO_REMOVE_VALUES = ['multiselect'];
    use EntityFieldsBuildFormTrait;

    /**
     * @var FieldModel
     */
    private $fieldModel;

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
            ]
        );

        $builder->add(
            'fields_to_update',
            LeadFieldsType::class,
            [
                'label'       => '',
                'label_attr'  => ['class' => 'control-label'],
                'multiple'    => true,
                'object'      => 'lead',
                'placeholder' => 'mautic.core.select',
                'attr'        => [
                    'class'    => 'form-control',
                    'onchange' => 'Mautic.updateContactActionModifiers()',
                ],
            ]
        );
        $builder->add(
            'fields',
            UpdateFieldType::class,
            [
                'fields'  => $leadFields,
                'object'  => 'lead',
                'actions' => $options['data']['actions'],
                'data'    => $options['data']['fields'],
            ]
        );

        $builder->add(
            'actions',
            UpdateActionType::class,
            [
                'fields' => $leadFields,
                'data'   => $options['data']['actions'],
            ]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'updatelead_action';
    }
}
