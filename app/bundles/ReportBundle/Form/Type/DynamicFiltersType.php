<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Form\Type;

use Mautic\ReportBundle\Entity\Report;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class DynamicFiltersType.
 */
class DynamicFiltersType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        foreach ($options['report']->getFilters() as $filter) {
            if (isset($filter['dynamic'])) {
                $column     = $filter['column'];
                $definition = $options['filterDefinitions']->definitions[$column];

                $args = [
                    'label'      => $definition['label'],
                    'label_attr' => ['class' => 'control-label'],
                    'attr'       => [
                        'class'    => 'form-control',
                        'onchange' => "Mautic.filterTableData('report.".$options['report']->getId()."','".$column."',this.value,'list','.report-content');",
                    ],
                    'required' => false,
                ];

                switch ($definition['type']) {
                    case 'bool':
                    case 'boolean':
                        $type                      = 'button_group';
                        $args['choices_as_values'] = true;
                        $args['choices']           = [
                            [
                                'mautic.core.form.no'      => false,
                                'mautic.core.form.yes'     => true,
                                'mautic.core.filter.clear' => '',
                            ],
                        ];

                        if (isset($options['data'][$definition['alias']])) {
                            $args['data'] = ((int) $options['data'][$definition['alias']] == 1);
                        }
                        break;
                    case 'datetime':
                        $type = 'datetime';
                        break;
                    case 'multiselect':
                    case 'select':
                        $type            = 'choice';
                        $args['choices'] = $definition['list'];
                        break;
                    default:
                        $type = 'text';
                        break;
                }

                $builder->add($definition['alias'], $type, $args);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'report_dynamicfilters';
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'filterDefinitions' => [],
                'report'            => new Report(),
            ]
        );
    }
}
