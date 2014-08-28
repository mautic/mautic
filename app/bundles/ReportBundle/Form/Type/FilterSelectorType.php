<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class FilterSelectorType
 *
 * @package Mautic\ReportBundle\Form\Type
 */
class FilterSelectorType extends AbstractType
{
    /**
     * Array of filter conditions
     *
     * @var array
     */
    private $conditionArray = array(
        '='  => '=',
        '>'  => '>',
        '>=' => '>=',
        '<'  => '<',
        '<=' => '<=',
        '!=' => '!='
    );

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $path = $builder->getOption('property_path');

        if (is_null($path)) {
            $data = array('column' => '', 'condition' => '', 'value' => '');
        } else {
            $index = str_replace(array('[', ']'), '', $path);
            $data  = $options['data'][$index];
        }

        // Build a list of columns
        $builder->add('column', 'choice', array(
            'choices'     => $options['columnList'],
            'data'        => $data['column'],
            'expanded'    => false,
            'multiple'    => false,
            'label'       => 'mautic.report.report.label.filtercolumn',
            'label_attr'  => array('class' => 'control-label'),
            'empty_value' => false,
            'required'    => false,
            'attr'        => array(
                'class' => 'form-control',
            )
        ));

        // Build a list of condition values
        $builder->add('condition', 'choice', array(
            'choices'     => $this->conditionArray,
            'data'        => $data['condition'],
            'expanded'    => false,
            'multiple'    => false,
            'label'       => 'mautic.report.report.label.filtercondition',
            'label_attr'  => array('class' => 'control-label'),
            'empty_value' => false,
            'required'    => false,
            'attr'        => array(
                'class' => 'form-control',
            )
        ));

        $builder->add('value', 'text', array(
            'label'      => 'mautic.report.report.label.filtervalue',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => false,
            'data'       => $data['value']
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars = array_replace($view->vars, array(
            'columnList' => $options['columnList']
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'filter_selector';
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'columnList' => array()
        ));
    }
}
