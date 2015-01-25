<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class FilterSelectorType
 */
class TableOrderType extends AbstractType
{
    /**
     * @var \Symfony\Bundle\FrameworkBundle\Translation\Translator
     */
    private $translator;

    public function __construct(MauticFactory $factory)
    {
        $this->translator = $factory->getTranslator();
    }

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
            'label_attr'  => array('class' => 'control-label'),
            'empty_value' => false,
            'required'    => false,
            'attr'        => array(
                'class' => 'form-control filter-columns'
            )
        ));

        // Direction
        $builder->add('direction', 'choice', array(
            'choices'     => array(
                'ASC'  => $this->translator->trans('mautic.report.report.label.tableorder_dir.asc'),
                'DESC' => $this->translator->trans('mautic.report.report.label.tableorder_dir.desc')
            ),
            'expanded'    => false,
            'multiple'    => false,
            'label'       => 'mautic.core.order',
            'label_attr'  => array('class' => 'control-label'),
            'empty_value' => false,
            'required'    => false,
            'attr'        => array(
                'class' => 'form-control not-chosen'
            )
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
        return 'table_order';
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
