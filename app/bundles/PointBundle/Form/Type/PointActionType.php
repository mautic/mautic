<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PointBundle\Form\Type;

use Mautic\CoreBundle\Form\EventListener\CleanFormSubscriber;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class PointActionType
 */
class PointActionType extends AbstractType
{

    /**
     * {@inheritdoc}
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $formTypeOptions = array(
            'label' => false
        );
        if (!empty($options['formTypeOptions'])) {
            $formTypeOptions = array_merge($formTypeOptions, $options['formTypeOptions']);
        }
        $builder->add('properties', $options['formType'], $formTypeOptions);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'formType'        => 'genericpoint_settings',
            'formTypeOptions' => array()
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return "pointaction";
    }
}
