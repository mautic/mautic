<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Form\Type;

use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormFeatureSettingsInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormSyncInterface;
use Mautic\IntegrationsBundle\Integration\Interfaces\IntegrationInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class IntegrationFeatureSettingsType extends AbstractType
{
    /**
     * @throws IntegrationNotFoundException
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $integrationObject = $options['integrationObject'];
        if (!$integrationObject instanceof IntegrationInterface) {
            throw new IntegrationNotFoundException("{$options['integrationObject']} is not recognized");
        }

        if ($integrationObject instanceof ConfigFormFeatureSettingsInterface) {
            $builder->add(
                'integration',
                $integrationObject->getFeatureSettingsConfigFormName(),
                [
                    'label' => false,
                ]
            );
        }

        if ($integrationObject instanceof ConfigFormSyncInterface) {
            $builder->add(
                'sync',
                IntegrationSyncSettingsType::class,
                [
                    'label'             => false,
                    'integrationObject' => $integrationObject,
                ]
            );
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setRequired(
            [
                'integrationObject',
            ]
        );
    }
}
