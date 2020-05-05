<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Class CampaignEventJumpToEventType.
 */
class CampaignEventJumpToEventType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $jumpProps = $builder->getData();
        $selected  = isset($jumpProps['jumpToEvent']) ? $jumpProps['jumpToEvent'] : null;

        $builder->add(
            'jumpToEvent',
            'choice',
            [
                'choices'    => [],
                'multiple'   => false,
                'label'      => 'mautic.campaign.form.jump_to_event',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => [
                    'class'                => 'form-control',
                    'data-onload-callback' => 'updateJumpToEventOptions',
                    'data-selected'        => $selected,
                ],
                'constraints' => [
                    new NotBlank(
                        [
                            'message' => 'mautic.core.value.required',
                        ]
                    ),
                ],
            ]
        );

        // Allows additional values (new events) to be selected before persisting
        $builder->get('jumpToEvent')->resetViewTransformers();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'campaignevent_jump_to_event';
    }
}
