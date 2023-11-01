<?php

namespace MauticPlugin\MauticCrmBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Class class PublicController extends CommonController.
 */
class PublicController extends CommonController
{
    public function contactDataAction()
    {
        $content = $this->get('request')->getContent();
        if (!empty($content)) {
            $data = json_decode($content, true); // 2nd param to get as array
        } else {
            return new Response('ERROR');
        }

        $logger = $this->get('monolog.logger.mautic');

        $integration       = 'Hubspot';
        $integrationHelper = $this->get('mautic.helper.integration');

        $integrationObject = $integrationHelper->getIntegrationObject($integration);
        foreach ($data as $info) {
            $object = explode('.', $info['subscriptionType']);
            $id     = $info['objectId'];

            try {
                switch ($object[0]) {
                    case 'contact': $integrationObject->getContacts($id);
                        break;
                    case 'company':
                        $integrationObject->getCompanies($id);
                        break;
                }
            } catch (\Exception $ex) {
                $logger->log('error', 'ERROR on Hubspot webhook: '.$ex->getMessage());
            }
        }

        return new Response('OK');
    }
}
