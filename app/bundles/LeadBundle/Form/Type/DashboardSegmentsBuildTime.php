<?php

namespace Mautic\LeadBundle\Form\Type;

use Mautic\LeadBundle\Model\ListModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * @extends AbstractType<mixed>
 */
class DashboardSegmentsBuildTime extends AbstractType
{
    public function __construct(
        private ListModel $segmentModel
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add(
            'order',
            ChoiceType::class,
            [
                'label'             => 'mautic.core.order',
                'choices'           => [
                    'mautic.widget.segments.build.time.shortest' => 'ASC',
                    'mautic.widget.segments.build.time.longest'  => 'DESC',
                ],
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'empty_data' => '',
                'required'   => false,
            ]
        );

        $lists    = $this->segmentModel->getUserLists();
        $segments = [];
        foreach ($lists as $list) {
            $segments[$list['name']] = $list['id'];
        }

        $builder->add('segments', ChoiceType::class, [
                'label'             => 'mautic.lead.list.filter',
                'multiple'          => true,
                'choices'           => $segments,
                'label_attr'        => ['class' => 'control-label'],
                'attr'              => ['class' => 'form-control'],
                'required'          => false,
            ]
        );
    }
}
