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
use Mautic\LeadBundle\Import\ImportDispatcher;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class LeadImportFieldType.
 */
class LeadImportFieldType extends AbstractType
{
    private $factory;

    /**
     * @var ImportDispatcher
     */
    private $importDispatcher;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory, ImportDispatcher $importDispatcher)
    {
        $this->factory          = $factory;
        $this->importDispatcher = $importDispatcher;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $dispatchBuilder    = $this->importDispatcher->dispatchBuilder();
        $importChoiceFields = $dispatchBuilder->getFields();

        foreach ($options['import_fields'] as $field => $label) {
            $builder->add(
                $field,
                'choice',
                [
                    'choices'    => $importChoiceFields,
                    'label'      => $label,
                    'required'   => false,
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => ['class' => 'form-control'],
                    'data'       => $this->getDefaultValue($field, $options['import_fields']),
                ]
            );
        }

        $transformer = new \Mautic\CoreBundle\Form\DataTransformer\IdToEntityModelTransformer(
            $this->factory->getEntityManager(),
            'MauticUserBundle:User'
        );

        $builder->add(
            $builder->create(
                'owner',
                'user_list',
                [
                    'label'      => 'mautic.lead.lead.field.owner',
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class' => 'form-control',
                    ],
                    'required' => false,
                    'multiple' => false,
                ]
            )
                ->addModelTransformer($transformer)
        );

        if ($options['object'] === 'lead') {
            $builder->add(
                $builder->create(
                    'list',
                    'leadlist_choices',
                    [
                        'label'      => 'mautic.lead.lead.field.list',
                        'label_attr' => ['class' => 'control-label'],
                        'attr'       => [
                            'class' => 'form-control',
                        ],
                        'required' => false,
                        'multiple' => false,
                    ]
                )
            );

            $builder->add(
                'tags',
                'lead_tag',
                [
                    'label'      => 'mautic.lead.tags',
                    'required'   => false,
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'                => 'form-control',
                        'data-placeholder'     => $this->factory->getTranslator()->trans('mautic.lead.tags.select_or_create'),
                        'data-no-results-text' => $this->factory->getTranslator()->trans('mautic.lead.tags.enter_to_create'),
                        'data-allow-add'       => 'true',
                        'onchange'             => 'Mautic.createLeadTag(this)',
                    ],
                ]
            );
        }

        $buttons = ['cancel_icon' => 'fa fa-times'];

        if (empty($options['line_count_limit'])) {
            $buttons = array_merge(
                $buttons,
                [
                    'apply_text'  => $dispatchBuilder->isImportInBackground() ? 'mautic.lead.import.in.background' : false,
                    'apply_class' => 'btn btn-success',
                    'apply_icon'  => 'fa fa-history',
                    'save_text'   => 'mautic.lead.import.start',
                    'save_class'  => 'btn btn-primary',
                    'save_icon'   => 'fa fa-upload',
                ]
            );
        } else {
            $buttons = array_merge(
                $buttons,
                [
                    'apply_text' => false,
                    'save_text'  => 'mautic.lead.import',
                    'save_class' => 'btn btn-primary',
                    'save_icon'  => 'fa fa-upload',
                ]
            );
        }

        $builder->add('buttons', 'form_buttons', $buttons);
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(['import_fields', 'object']);
        $resolver->setDefaults(['line_count_limit' => 0]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'lead_field_import';
    }

    /**
     * @param string $fieldName
     * @param array  $importFields
     *
     * @return string
     */
    public function getDefaultValue($fieldName, array $importFields)
    {
        if (isset($importFields[$fieldName])) {
            return $importFields[$fieldName];
        }

        return null;
    }
}
