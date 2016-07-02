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
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;

/**
 * Class BuilderSectionType
 *
 * @package Mautic\CoreBundle\Form\Type
 */
class BuilderSectionType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('content-background-color', 'text', [
            'label'      => 'mautic.core.content.background.color',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'           => 'form-control',
                'data-slot-param' => 'background-color',
                'data-toggle'     => 'color'
            ],
        ]);

        $builder->add('wrapper-background-color', 'text', [
            'label'      => 'mautic.core.wrapper.background.color',
            'label_attr' => ['class' => 'control-label'],
            'required'   => false,
            'attr'       => [
                'class'           => 'form-control',
                'data-slot-param' => 'background-color',
                'data-toggle'     => 'color'
            ],
        ]);
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "builder_section";
    }
}
