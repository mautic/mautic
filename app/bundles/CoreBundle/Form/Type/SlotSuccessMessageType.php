<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class SlotSuccessMessageType.
 */
class SlotSuccessMessageType extends SlotType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'successmessage',
            TextareaType::class,
            [
                'label'      => false,
                'label_attr' => ['class' => 'control-label'],
                'required'   => false,
                'attr'       => [
                    'class'           => 'form-control editor',
                    'data-slot-param' => 'content',
                ],
            ]
        );

        parent::buildForm($builder, $options);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'slot_successmessage';
    }
}
