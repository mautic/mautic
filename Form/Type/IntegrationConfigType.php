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


use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\IntegrationsBundle\Helper\IntegrationsHelper;
use MauticPlugin\IntegrationsBundle\Integration\Interfaces\ConfigFormAuthInterface;
use MauticPlugin\IntegrationsBundle\Integration\Interfaces\ConfigFormFeaturesInterface;
use MauticPlugin\IntegrationsBundle\Integration\Interfaces\ConfigFormFeatureSettingsInterface;
use MauticPlugin\IntegrationsBundle\Integration\Interfaces\ConfigFormSyncInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use MauticPlugin\IntegrationsBundle\Exception\IntegrationNotFoundException;

class IntegrationConfigType extends AbstractType
{
    /**
     * @var IntegrationsHelper
     */
    private $integrationsHelper;

    /**
     * IntegrationConfigType constructor.
     *
     * @param IntegrationsHelper $integrationsHelper
     */
    public function __construct(IntegrationsHelper $integrationsHelper)
    {
        $this->integrationsHelper = $integrationsHelper;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @throws IntegrationNotFoundException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $integrationObject = $this->integrationsHelper->getIntegration($options['integration']);

        // isPublished
        $builder->add('isPublished', YesNoButtonGroupType::class);

        // apiKeys
        if ($integrationObject instanceof ConfigFormAuthInterface) {
            // @todo decrypt/encrypt
            $builder->add('apiKeys', $integrationObject->getAuthConfigFormName());
        }

        // supportedFeatures
        if ($integrationObject instanceof ConfigFormFeaturesInterface) {
            $customFeaturesFormField = $integrationObject->getFeaturesConfigFormName();

            if ($customFeaturesFormField) {
                $builder->add('supportedFeatures', $customFeaturesFormField);
            } else {
                // @todo add tooltip support
                $builder->add(
                    'supportedFeatures',
                    ChoiceType::class,
                    [
                        'choices'     => $integrationObject->getSupportedFeatures(),
                        'expanded'    => true,
                        'label_attr'  => ['class' => 'control-label'],
                        'multiple'    => true,
                        'label'       => 'mautic.integration.features',
                        'required'    => false,
                    ]
                );
            }
        }

        // featureSettings
        $builder->add('featureSettings', IntegrationFeatureSettingsType::class, ['integrationObject' => $integrationObject]);

        $builder->setAction($options['action']);
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired(
            [
                'integration'
            ]
        );

        $resolver->setDefined(
            [
                'data_class'  => Integration::class,
            ]
        );
    }
}