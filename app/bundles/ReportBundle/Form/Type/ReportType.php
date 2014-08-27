<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ReportBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Mautic\CoreBundle\Form\EventListener\FormExitSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class ReportType
 *
 * @package Mautic\ReportBundle\Form\Type
 */
class ReportType extends AbstractType
{
    /**
     * Factory object
     *
     * @var \Mautic\CoreBundle\Factory\MauticFactory
     */
    private $factory;

    /**
     * Translator object
     *
     * @var \Symfony\Bundle\FrameworkBundle\Translation\Translator
     */
    private $translator;

    /**
     * Allowed tables to generate reports from
     *
     * @var array
     */
    private $tableOptions = array(
        'Page' => 'Pages',
        'Lead' => 'Leads'
    );

    /**
     * @param MauticFactory $factory
     */
    public function __construct(MauticFactory $factory) {
        $this->translator = $factory->getTranslator();
        $this->factory    = $factory;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventSubscriber(new CleanFormSubscriber(array('content' => 'html')));
        $builder->addEventSubscriber(new FormExitSubscriber('report.report', $options));

        // Only add these fields if we're in edit mode
        if (!$options['read_only']) {
            $builder->add('title', 'text', array(
                'label'      => 'mautic.report.report.form.title',
                'label_attr' => array('class' => 'control-label'),
                'attr'       => array('class' => 'form-control'),
                'required'   => true
            ));

            $builder->add('isPublished', 'button_group', array(
                'choice_list' => new ChoiceList(
                    array(false, true),
                    array('mautic.core.form.no', 'mautic.core.form.yes')
                ),
                'expanded'      => true,
                'multiple'      => false,
                'label'         => 'mautic.core.form.ispublished',
                'label_attr'    => array('class' => 'control-label'),
                'empty_value'   => false,
                'required'      => false
            ));

            $builder->add('system', 'button_group', array(
                'choice_list' => new ChoiceList(
                    array(false, true),
                    array('mautic.core.form.no', 'mautic.core.form.yes')
                ),
                'expanded'      => true,
                'multiple'      => false,
                'label'         => 'mautic.report.report.form.issystem',
                'label_attr'    => array('class' => 'control-label'),
                'empty_value'   => false,
                'required'      => false
            ));

            // Build a list of data sources
            $builder->add('source', 'choice', array(
                'choices'       => $this->tableOptions,
                'expanded'      => false,
                'multiple'      => false,
                'label'         => 'mautic.report.report.form.source',
                'label_attr'    => array('class' => 'control-label'),
                'empty_value'   => false,
                'required'      => true,
                'attr'       => array(
                    'class'   => 'form-control',
                    'tooltip' => 'mautic.report.report.form.source.help'
                )
            ));

            $source  = $options['data']->getSource();
            $columns = $this->factory->getEntityManager()->getClassMetadata('Mautic\\' . $source . 'Bundle\\Entity\\' . $source)->getFieldNames();

            // Build the column selector
            $builder->add('columns', 'column_selector', array(
                'columnList' => $columns,
                'label'      => 'mautic.report.report.form.columnselector',
                'label_attr' => array('class' => 'control-label'),
                'required'   => true
            ));

            // Build the filter selector
            $builder->add('filters', 'filter_selector', array(
                'columnList' => $columns,
                'label'      => 'mautic.report.report.form.filterselector',
                'label_attr' => array('class' => 'control-label'),
                'required'   => true
            ));

            $builder->add('buttons', 'form_buttons');
        }

        if (!empty($options["action"])) {
            $builder->setAction($options["action"]);
        }
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class'         => 'Mautic\ReportBundle\Entity\Report',
            'allow_extra_fields' => true
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "report";
    }
}
