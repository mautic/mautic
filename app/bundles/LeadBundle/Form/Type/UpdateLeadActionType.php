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

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class UpdateLeadActionType.
 */
class UpdateLeadActionType extends AbstractType
{
    use EntityFieldsBuildFormTrait;

    private $factory;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var \Mautic\LeadBundle\Model\FieldModel $fieldModel */
        $fieldModel = $this->factory->getModel('lead.field');
        $leadFields = $fieldModel->getEntities(
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

        if (!empty($options['field_choices']) && is_array($options['field_choices'])) {
            $builder->add(
            $options['field_choices']['alias'],
            'leadfields_choices',
            [
                'label'       => $options['field_choices']['label'],
                'label_attr'  => ['class' => 'control-label'],
                'multiple'    => true,
                'with_tags'   => false,
                'with_utm'    => false,
                'empty_value' => 'mautic.core.select',
                'attr'        => [
                    'class'    => 'form-control',
                    'tooltip'  => isset($options['field_choices']['tooltip']) ? $options['field_choices']['tooltip'] : false,
                ],
                'required'    => true,
            ]
        );
        }
        $this->getFormFields($builder, $options);
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'field_choices' => [],
            ]
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'updatelead_action';
    }
}
