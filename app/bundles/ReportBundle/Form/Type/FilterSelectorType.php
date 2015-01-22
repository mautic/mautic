<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
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
 */
class FilterSelectorType extends AbstractType
{
    /**
     * Array of filter conditions
     *
     * @var array
     */
    private $conditionArray = array(
        'eq'       => '=',
        'gt'       => '>',
        'gte'      => '>=',
        'lt'       => '<',
        'lte'      => '<=',
        'neq'      => '!=',
        'like'     => 'LIKE',
        'notLike'  => 'NOT LIKE',
        'empty'    => 'EMPTY',
        'notEmpty' => 'NOT EMPTY'
    );

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // Build a list of columns
        $builder->add('column', 'choice', array(
            'choices'     => $options['columnList'],
            'expanded'    => false,
            'multiple'    => false,
            'label'       => 'mautic.report.report.label.filtercolumn',
            'label_attr'  => array('class' => 'control-label filter-column'),
            'empty_value' => false,
            'required'    => false,
            'attr'        => array(
                'class' => 'form-control filter-columns'
            )
        ));

        // Build a list of condition values
        $builder->add('condition', 'choice', array(
            'choices'     => $this->conditionArray,
            'expanded'    => false,
            'multiple'    => false,
            'label'       => 'mautic.report.report.label.filtercondition',
            'label_attr'  => array('class' => 'control-label filter-condition'),
            'empty_value' => false,
            'required'    => false,
            'attr'        => array(
                'class' => 'form-control not-chosen'
            )
        ));

        $builder->add('value', 'text', array(
            'label'      => 'mautic.report.report.label.filtervalue',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control filter-value'),
            'required'   => false
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
