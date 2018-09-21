<?php

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\IntegrationsBundle\Form\Type;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IntegrationSyncSettingsObjectFieldMappingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        // @todo pagination
        foreach ($options['requiredIntegrationFields'] as $field => $label) {
            $builder->add(
                $field,
                ChoiceType::class,
                [
                    'label'    => $label,
                    'choices'  => $options['mauticFields'],
                    'required' => true,
                ]
            );
        }

        foreach ($options['optionalIntegrationFields'] as $field => $label) {
            $builder->add(
                $field,
                ChoiceType::class,
                [
                    'label'    => $label,
                    'choices'  => $options['mauticFields'],
                    'required' => false,
                ]
            );
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'requiredIntegrationFields',
                'optionalIntegrationFields',
                'mauticFields',
            ]
        );
    }
}