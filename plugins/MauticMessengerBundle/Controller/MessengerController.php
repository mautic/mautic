<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticMessengerBundle\Controller;

use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Exception as MauticException;
use Joomla\Http\Http;
use Symfony\Component\HttpFoundation\RequestStack;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class MessengerController extends FormController
{
    /**
     * @return Response
     */
    public function callbackAction()
    {
        $verify_token = "bot_app";
        $hub_verify_token = null;
        if (isset($_REQUEST['hub_challenge'])) {
            $challenge = $_REQUEST['hub_challenge'];
            $hub_verify_token = $_REQUEST['hub_verify_token'];
            if ($hub_verify_token === $verify_token) {
                return new Response($challenge);
            }
        }

    }


    public function checkboxAction()
    {
        $integration = $this->get('mautic.helper.integration')->getIntegrationObject('Messenger');

        if (!$integration || $integration->getIntegrationSettings()->getIsPublished() === false) {
            return;
        }

        $settings        = $integration->getIntegrationSettings();
        $featureSettings = $settings->getFeatureSettings();

        $content = '';
        $html = $this->get('mautic.helper.templating')->getTemplating()->render(
            'MauticMessengerBundle:Plugin:checkbox_plugin.html.php',
            [
                'lead' => $this->getModel('lead')->getCurrentLead(),
                'featureSettings'=>$featureSettings
            ]
        );
die($html);
        return empty($content) ? new Response('', Response::HTTP_NO_CONTENT) : new Response($html);
    }
}
