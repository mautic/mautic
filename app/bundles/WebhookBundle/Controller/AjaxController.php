<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebhookBundle\Controller;

use Joomla\Http\Http;
use Mautic\CoreBundle\Controller\AjaxController as CommonAjaxController;
use Mautic\CoreBundle\Helper\InputHelper;
use Symfony\Component\HttpFoundation\Request;
use Mautic\LeadBundle\Entity\Lead;


class AjaxController extends CommonAjaxController
{
    protected function sendHookTestAction(Request $request)
    {
        $url = InputHelper::clean($request->request->get('url'));

        if ($url == '' || ! $url) {
            // default to an error message
            $dataArray = array(
                'success' => 1,
                'html' =>
                    '<div class="has-error"><span class="help-block">'
                    . $this->factory->getTranslator()->trans('mautic.webhook.label.no.url')
                    . '</span></div>',
            );

            return $this->sendJsonResponse($dataArray);
        }

        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel = $this->factory->getModel('lead');

        // create a new lead
        $lead = new Lead();

        // add some sample data
        $leadModel->setFieldValues($lead, array('email' => 'example@email.com',
                                                'firstname' => 'Hello',
                                                'lastname' => 'World'));

        /** @var \Mautic\WebhookBundle\Model\WebhookModel $model */
        $model = $this->factory->getModel('webhook');

        $payload = array('lead' => $lead);
        $serializerGroups = array("leadDetails", "userList", "publishDetails", "ipAddress");

        // serialize the payload
        $serializedPayload = $model->serializeData($payload, $serializerGroups);

        $now = new \DateTime;

        $queuePayload = json_decode($serializedPayload, true);
        $queuePayload['timestamp'] = $now->format('c');

        // instantiate new http class
        $http = new Http();

        // set the response
        $response = $http->post($url, json_encode(array('mautic.lead_post_save' => $queuePayload)));

        // default to an error message
        $dataArray = array(
            'success' => 1,
            'html' =>
                '<div class="has-error"><span class="help-block">'
                . $this->factory->getTranslator()->trans('mautic.webhook.label.warning')
                . '</span></div>',
        );

        // if we get a 200 response convert to success message
        if ($response->code == 200) {
            $dataArray['html'] =
                '<div class="has-success"><span class="help-block">'
                . $this->factory->getTranslator()->trans('mautic.webhook.label.success')
                . '</span></div>';
        }

        return $this->sendJsonResponse($dataArray);
    }
}