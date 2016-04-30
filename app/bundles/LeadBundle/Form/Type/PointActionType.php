<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class PointsActionType
 *
 * @package Mautic\LeadBundle\Form\Type
 */
class PointActionType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('points', 'number', array(
            'label'      => 'mautic.lead.lead.event.points',
            'attr'       => array('class' => 'form-control'),
            'label_attr' => array('class' => 'control-label'),
            'precision'  => 0,
            'data'       => (isset($options['data']['points'])) ? $options['data']['points'] : 0
        ));
    }

    /**
     * @return string
     */
    public function getName() {
        return "leadpoints_action";
    }
}