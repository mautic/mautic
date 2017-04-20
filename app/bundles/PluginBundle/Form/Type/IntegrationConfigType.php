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
 * Class IntegrationConfigType.
 */
class IntegrationConfigType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['integration'] != null) {
            $options['integration']->appendToForm($builder, $options['data'], 'integration');
        }

        if (!empty($options['campaigns'])) {
            $builder->add(
                'campaigns',
                'choice',
                [
                    'choices' => $options['campaigns'],
                    'attr'    => [
                        'class' => 'form-control', 'onchange' => 'Mautic.getIntegrationCampaignStatus(this);', ],
                    'label'       => 'mautic.plugin.integration.campaigns',
                    'empty_value' => 'mautic.plugin.config.campaign.member.chooseone',
                    'required'    => false,
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
        $resolver->setDefaults([
            'label'     => false,
            'campaigns' => [],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'integration_config';
    }
}
