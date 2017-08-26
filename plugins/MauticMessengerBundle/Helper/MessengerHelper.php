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

use Joomla\Http\Http;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Helper\TemplatingHelper;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Symfony\Component\HttpFoundation\RequestStack;

class MessengerHelper
{
    /**
     * @var array
     */
    protected static $cache = [];

    /**
     * @var Http ;
     */
    protected $connector;

    /**
     * @var RequestStack ;
     */
    protected $request;

    /**
     * @var CoreParametersHelper
     */
    protected $coreParameterHelper;

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var TemplatingHelper
     */
    protected $templateHelper;

    /**
     * @var IntegrationHelper
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
        $this->connector           = $connector;
        $this->request             = $request->getCurrentRequest();
        $this->coreParameterHelper = $coreParametersHelper;
        $this->leadModel           = $leadModel;
        $this->integrationHelper   = $integrationHelper;
        $this->templateHelper      = $templatingHelper;
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
     *
     * @return string|void
     */
    public function getTemplateContent($template = 'MauticMessengerBundle:Plugin:checkbox_plugin.html.php')
    {
        $integration = $this->integrationHelper->getIntegrationObject('Messenger');

        if (!$integration || $integration->getIntegrationSettings()->getIsPublished() === false) {
            return;
        }
        $settings        = $integration->getIntegrationSettings();
        $featureSettings = $settings->getFeatureSettings();
        $apiKeys         = $integration->getDecryptedApiKeys();
        $lead            = $this->leadModel->getCurrentLead();

        return $this->templateHelper->getTemplating()->render(
            $template,
            [
                'contactId'       => ($lead && $lead->getId()) ? $lead->getId() : 0,
                'userRef'         => $this->getUserRef(),
                'featureSettings' => $featureSettings,
                'apiKeys'         => $apiKeys,
                'formName'        => $_REQUEST['formname'],
            ]
        );
    }

    private function getUserRef()
    {
        if (!isset(self::$cache['userRef'])) {
            $userRef                = time().mt_rand();
            self::$cache['userRef'] = $userRef;
        }

        return self::$cache['userRef'];
    }
}
