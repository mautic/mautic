<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\UserBundle\Form\DataTransformer as Transformers;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class FilterType
 *
 * @package Mautic\LeadBundle\Form\Type
 */
class FilterType extends AbstractType
{
    private $operatorChoices;
    private $translator;

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory)
    {
        /** @var \Mautic\LeadBundle\Model\ListModel $listModel */
        $listModel       = $factory->getModel('lead.list');
        $operatorChoices = $listModel->getFilterExpressionFunctions();

        $this->operatorChoices = array();
        foreach ($operatorChoices as $key => $value) {
            $this->operatorChoices[$key] = $value['label'];
        }

        $this->translator = $factory->getTranslator();
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'glue',
            'choice',
            array(
                'label'   => false,
                'choices' => array(
                    'and' => 'mautic.lead.list.form.glue.and',
                    'or'  => 'mautic.lead.list.form.glue.or'
                ),
                'attr'    => array(
                    'class' => 'form-control not-chosen'
                )
            )
        );

        $builder->add(
            'operator',
            'choice',
            array(
                'label'   => false,
                'choices' => $this->operatorChoices,
                'attr'    => array(
                    'class' => 'form-control not-chosen'
                )
            )
        );

        $type        = 'text';
        $attr        = array(
            'class' => 'form-control'
        );
        $displayType = 'hidden';
        $displayAttr = array();

        if (!empty($options['data'])) {
            $fieldType = $options['data']['field'];
            switch ($fieldType) {
                case 'timezone':
                    $attr['choices'] = $options['timezones'];
                    $type            = 'choice';
                    break;
                case 'country':
                    $attr['choices'] = $options['countries'];
                    $type            = 'choice';
                    break;
                case 'region':
                    $attr['choices'] = $options['regions'];
                    $type            = 'choice';
                    break;
                case 'time':
                case 'date':
                case 'datetime':
                    $type                = $fieldType;
                    $attr['data-toggle'] = $fieldType;
                    break;
                case 'lookup_id':
                case 'boolean':
                    $type        = 'hidden';
                    $displayType = 'text';
                    $displayAttr = array_merge(
                        $displayAttr,
                        array(
                            'class'       => 'form-control',
                            'data-toggle' => 'field-lookup',
                            'data-target' => $options['data']['filter'],
                            'placeholder' => $this->translator->trans(
                                'mautic.lead.list.form.filtervalue'
                            )
                        )
                    );

                    if (isset($this->fieldChoices[$fieldType]['properties']['list'])) {
                        $displayAttr['data-options'] = $this->fieldChoices[$fieldType]['properties']['list'];
                    }

                    break;

                case 'lookup':
                case 'select':
                default:
                    $attr = array_merge(
                        $attr,
                        array(
                            'data-toggle' => 'field-lookup',
                            'data-target' => $options['data']['filter'],
                            'placeholder' => $this->translator->trans('mautic.lead.list.form.filtervalue')
                        )
                    );

                    if (isset($this->fieldChoices[$fieldType]['properties']['list'])) {
                        $attr['data-options'] = $this->fieldChoices[$fieldType]['properties']['list'];
                    }

                    break;
            }
        }

        $builder->add(
            'filter',
            $type,
            array(
                'label' => false,
                'attr'  => $attr
            )
        );

        $builder->add(
            'display',
            $displayType,
            array(
                'label' => false,
                'attr'  => $displayAttr
            )
        );

        $builder->add('field', 'hidden');

        $builder->add('type', 'hidden');
    }


    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(
            array(
                'timezones',
                'countries',
                'regions',
                'fields'
            )
        );
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['fields']    = $options['fields'];
        $view->vars['countries'] = $options['countries'];
        $view->vars['regions']   = $options['regions'];
        $view->vars['timezones'] = $options['timezones'];
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "leadlist_filters";
    }
}