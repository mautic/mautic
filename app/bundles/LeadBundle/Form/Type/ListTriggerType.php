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

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ListTriggerType.
 */
class ListTriggerType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('addedTo', 'leadlist_choices', [
            'label'      => 'mautic.lead.lead.events.listtrigger.added',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class' => 'form-control',
            ],
            'multiple' => true,
            'expanded' => false,
        ]);

        $builder->add('removedFrom', 'leadlist_choices', [
            'label'      => 'mautic.lead.lead.events.listtrigger.removed',
            'label_attr' => ['class' => 'control-label'],
            'attr'       => [
                'class' => 'form-control',
            ],
            'multiple' => true,
            'expanded' => false,
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'leadlist_trigger';
    }
}
