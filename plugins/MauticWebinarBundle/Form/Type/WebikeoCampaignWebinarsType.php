<?php
/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      WebMecanik
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticWebinarBundle\Form\Type;

use Mautic\PluginBundle\Helper\IntegrationHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

/**
 * Class WebikeoCampaignWebinarsType.
 */
class WebikeoCampaignWebinarsType extends AbstractType
{
    /**
     * @var MauticFactory
     */
    private $integrationHelper;

    public function __construct(IntegrationHelper $integrationHelper)
    {
        $this->integrationHelper = $integrationHelper;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $integrationObject = $this->integrationHelper->getIntegrationObject($options['integration_object_name']);
        $webinars          = $integrationObject->getWebinars();

        $builder->add(
            'webinar',
            ChoiceType::class,
            [
                'choices' => $webinars,
                'attr'    => [
                    'class' => 'form-control', ],
                'label'    => 'mautic.plugin.integration.webinar',
                'required' => true,
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setOptional(['integration_object_name']);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'Webikeo_campaignevent_webinars';
    }
}
