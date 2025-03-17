<?php

namespace Mautic\ReportBundle\Form\Type;

use Mautic\CoreBundle\Helper\Serializer;
use Mautic\ReportBundle\Model\ReportModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<array<mixed>>
 */
class ReportWidgetType extends AbstractType
{
    public function __construct(
        protected ReportModel $model,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $choices = [];
        if ($reports = $this->model->getReportsWithGraphs()) {
            foreach ($reports as $report) {
                $choices[$report['name']] = [];

                $graphs = Serializer::decode($report['graphs']);

                foreach ($graphs as $graph) {
                    $graphValue                       = $report['id'].':'.$graph;
                    $choices[$report['name']][$graph] = $graphValue;
                }
            }
        }

        // Build a list of data sources
        $builder->add(
            'graph',
            ChoiceType::class,
            [
                'choices'           => $choices,
                'expanded'          => false,
                'multiple'          => false,
                'label'             => 'mautic.report.report.form.choose_graphs',
                'label_attr'        => ['class' => 'control-label'],
                'placeholder'       => false,
                'required'          => false,
                'attr'              => [
                    'class' => 'form-control',
                ],
            ]
        );

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }
}
