<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PageBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class AbTestPropertiesType.
 */
class AbTestPropertiesType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $options = ['label' => false];
        if (isset($options['formTypeOptions'])) {
            $options = array_merge($options, $options['formTypeOptions']);
        }
        $builder->add('properties', $options['formType'], $options);
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional([
            'formType',
            'formTypeOptions',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'page_abtest_settings';
    }
}
