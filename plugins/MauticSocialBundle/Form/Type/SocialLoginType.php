<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Form\Type;

use Mautic\CoreBundle\Factory\MauticFactory;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


/**
 * Class FacebookLoginType
 *
 * @package Mautic\FormBundle\Form\Type
 */
class SocialLoginType extends AbstractType
{
    /**
     * @var MauticFactory
     */
    private $factory;

    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        /** @var \Mautic\PluginBundle\Helper\IntegrationHelper $integrationHelper */
        $integrationHelper = $this->factory->getHelper('integration');
        $integrations      = '';

        $integrationObjects = $integrationHelper->getIntegrationObjects(null, 'login_button');

        foreach ($integrationObjects as $integrationObject) {
            if ($integrationObject->getIntegrationSettings()->isPublished()) {
                /** @var \Mautic\AssetBundle\Model\AssetModel $model */
                $model = $this->factory->getModel('form');
                $integrations.= $integrationObject->getName().",";
                $integration = array(
                    'integration' => $integrationObject->getName(),
                );

                $builder->add(
                    'authUrl_'.$integrationObject->getName(),
                    'hidden',
                    array(
                        'data' => $model->buildUrl('mautic_integration_auth_user', $integration, true, array()),
                    )
                );

            }
        }

        $builder->add(
            'integrations',
            'hidden',
            array(
                'data' => $integrations,
            )
        );
    }

    /**
     * @return string
     */
    public function getName()
    {
        return "sociallogin";
    }
}