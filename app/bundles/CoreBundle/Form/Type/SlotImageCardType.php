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

use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class SlotImageCardType.
 */
class SlotImageCardType extends SlotType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('imagecard_align', 'button_group', [
            'label'      => 'mautic.core.image.position',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'           => 'form-control',
                'data-slot-param' => 'imgalign',
            ],
            'choice_list' => new ChoiceList(
                ['left', 'center', 'right'],
                ['mautic.core.left', 'mautic.core.center', 'mautic.core.right']
            ),
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return 'slot_imagecard';
    }
}
