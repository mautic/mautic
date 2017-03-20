<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class IntegrationCampaignsType.
 */
class IntegrationCampaignsType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!empty($options['campaigns'])) {
            $builder->add(
                'campaigns',
                'choice',
                [
                    'choices' => $options['campaigns'],
                    'attr'    => [
                        'class' => 'form-control integration-campaigns', ],
                    'label'       => 'mautic.plugin.integration.campaigns',
                    'empty_value' => false,
                ]

            );
        }
    }
    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setRequired(['integration']);
        $resolver->setDefaults(
            ['campaigns' => []]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'integration_campaigns';
    }
}
