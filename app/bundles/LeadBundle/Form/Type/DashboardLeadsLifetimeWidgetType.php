<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Mautic\LeadBundle\Model\ListModel;
use Recurr\Transformer\TranslatorInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;

class DashboardLeadsLifetimeWidgetType extends AbstractType
{
    /**
     * @var ListModel
     */
    private $segmentModel;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(ListModel $segmentModel, TranslatorInterface $translator)
    {
        $this->segmentModel = $segmentModel;
        $this->translator   = $translator;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $lists                                                       = $this->segmentModel->getUserLists();
        $segments                                                    = [];
        $segments[$this->translator->trans('mautic.lead.all.leads')] = 0;
        foreach ($lists as $list) {
            $segments[$list['name']] = $list['id'];
        }

        $builder->add('flag', ChoiceType::class, [
                'label'             => 'mautic.lead.list.filter',
                'multiple'          => true,
                'choices'           => $segments,
                'label_attr'        => ['class' => 'control-label'],
                'attr'              => ['class' => 'form-control'],
                'required'          => false,
            ]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'lead_dashboard_leads_lifetime_widget';
    }
}
