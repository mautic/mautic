<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\CoreBundle\Form\Type\SlotType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class SlotButtonType
 *
 * @package Mautic\CoreBundle\Form\Type
 */
class SlotButtonType extends SlotType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder->add('href', 'text', array(
            'label'      => 'URL',
            'label_attr' => array('class' => 'control-label'),
            'required'   => false,
            'attr'       => array(
                'class'           => 'form-control',
                'data-slot-param' => 'href',
            ),
        ));
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "slot_button";
    }
}
