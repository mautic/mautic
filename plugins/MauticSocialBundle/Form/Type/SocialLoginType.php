<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticSocialBundle\Form\Type;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\FormBundle\Model\FormModel;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class FacebookLoginType.
 */
class SocialLoginType extends AbstractType
{
    /**
     * @var IntegrationHelper
     */
    private $helper;
    private $formModel;
    private $coreParametersHelper;

    /**
     * SocialLoginType constructor.
     */
    public function __construct(IntegrationHelper $helper, FormModel $form, CoreParametersHelper $coreParametersHelper)
    {
        $this->helper               = $helper;
        $this->formModel            = $form;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $integrations       = '';
        $integrationObjects = $this->helper->getIntegrationObjects(null, 'login_button');
        foreach ($integrationObjects as $integrationObject) {
            if ($integrationObject->getIntegrationSettings()->isPublished()) {
                $model = $this->formModel;
                $integrations .= $integrationObject->getName().',';
                $integration = [
                    'integration' => $integrationObject->getName(),
                ];

                $builder->add(
                    'authUrl_'.$integrationObject->getName(),
                    HiddenType::class,
                    [
                        'data' => $model->buildUrl('mautic_integration_auth_user', $integration, true, []),
                    ]
                );

                $builder->add(
                    'buttonImageUrl',
                    HiddenType::class,
                    [
                        'data' => $this->coreParametersHelper->get('site_url').'/'.$this->coreParametersHelper->get('image_path').'/',
                    ]
                );
            }
        }

        $builder->add(
            'integrations',
            HiddenType::class,
            [
                'data' => $integrations,
            ]
        );
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'sociallogin';
    }
}
