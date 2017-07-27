<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticMessengerBundle\Helper;

use Mautic\CoreBundle\Exception as MauticException;
use Joomla\Http\Http;
use Symfony\Component\HttpFoundation\RequestStack;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use  Mautic\CoreBundle\Helper\TemplatingHelper;

class MessengerHelper
{

    /**
     * @var array
     */
    protected static $cache = [];

    /**
     * @var Http $connector ;
     */
    protected $connector;

    /**
     * @var RequestStack $request ;
     */
    protected $request;

    /**
     * @var CoreParametersHelper $coreParameterHelper
     */
    protected $coreParameterHelper;

    /**
     * @var LeadModel $leadModel
     */
    protected $leadModel;

    /**
     * @var TemplatingHelper $templateHelper
     */
    protected $templateHelper;

    /**
     * @var IntegrationHelper $integrationHelper
     */
    protected $integrationHelper;


    public function __construct(
        Http $connector,
        RequestStack $request,
        CoreParametersHelper $coreParametersHelper,
        LeadModel $leadModel,
        IntegrationHelper $integrationHelper,
        TemplatingHelper $templatingHelper
    ) {
        $this->connector = $connector;
        $this->request = $request->getCurrentRequest();
        $this->coreParameterHelper = $coreParametersHelper;
        $this->leadModel = $leadModel;
        $this->integrationHelper = $integrationHelper;
        $this->templateHelper = $templatingHelper;
    }

    public function subscribeApp()
    {
        try {
            $data = $this->connector->get(
                'https://graph.facebook.com/v2.9/me/subscribed_apps?access_token='.$this->coreParameterHelper->getParameter(
                    'pageAccessTokken'
                ),
                [],
                10
            );
            if ($data->success == true) {
                return true;
            }
        } catch (\Exception $e) {
            //nothing
        }

        return false;
    }

    /**
     * @param string $template
     * @return string|void
     */
    public function getTemplateContent($template = 'MauticMessengerBundle:Plugin:checkbox_plugin.html.php')
    {
        $integration = $this->integrationHelper->getIntegrationObject('Messenger');

        if (!$integration || $integration->getIntegrationSettings()->getIsPublished() === false) {
            return;
        }
        $settings = $integration->getIntegrationSettings();
        $featureSettings = $settings->getFeatureSettings();

        return $this->templateHelper->getTemplating()->render(
            $template,
            [
                'userRef' => $this->getUserRef(),
                'featureSettings' => $featureSettings,
            ]
        );
    }

    private function getUserRef()
    {
        if (!isset(self::$cache['userRef'])) {
            $lead = $this->leadModel->getCurrentLead();
            $userRef = $lead->getId().time().mt_rand();
            self::$cache['userRef'] = $userRef;
        }

        return self::$cache['userRef'];
    }

}