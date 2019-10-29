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

use Mautic\CoreBundle\Helper\Serializer;
use Mautic\ReportBundle\Model\ReportModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ReportWidgetType.
 */
class ReportWidgetType extends AbstractType
{
    /**
     * @var ReportModel
     */
    protected $model;

    /**
     * ReportWidgetType constructor.
     *
     * @param ReportModel $reportModel
     */
    public function __construct(ReportModel $reportModel)
    {
        $this->model = $reportModel;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $choices = [];
        if ($reports = $this->model->getReportsWithGraphs()) {
            foreach ($reports as $report) {
                $choices[$report['name']] = [];

                $graphs = Serializer::decode($report['graphs']);

                foreach ($graphs as $graph) {
                    $graphValue                            = $report['id'].':'.$graph;
                    $choices[$report['name']][$graphValue] = $graph;
                }
            }
        }

        // Build a list of data sources
        $builder->add(
            'graph',
            'choice',
            [
                'choices'     => $choices,
                'expanded'    => false,
                'multiple'    => false,
                'label'       => 'mautic.report.report.form.choose_graphs',
                'label_attr'  => ['class' => 'control-label'],
                'empty_value' => false,
                'required'    => false,
                'attr'        => [
                    'class' => 'form-control',
                ],
            ]
        );

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'report_widget';
    }
}
