<?php

namespace Mautic\LeadBundle\Form\Type;

use Mautic\LeadBundle\Model\ListModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @extends AbstractType<mixed>
 */
class DashboardSegmentContactsWidgetType extends AbstractType
{
    public function __construct(
        private ListModel $segmentModel,
        private TranslatorInterface $translator,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Get the user-accessible segments
        $lists = $this->segmentModel->getUserLists();

        // Build choices array
        $segments = [];
        foreach ($lists as $list) {
            $segments[$list['name']] = $list['id'];
        }

        $builder->add('segmentId', ChoiceType::class, [
            'label'       => $this->translator->trans('mautic.widget.segment.label'),
            'choices'     => $segments,
            'required'    => true,
            'placeholder' => $this->translator->trans('mautic.widget.select.placeholder'),
            'label_attr'  => ['class' => 'control-label'],
            'attr'        => ['class' => 'form-control'],
        ]);
    }

    public function getBlockPrefix(): string
    {
        return 'dashboard_segment_contacts_widget';
    }
}
