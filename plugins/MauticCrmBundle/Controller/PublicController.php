<?php

/*
 * @copyright   2016 Mautic, Inc. All rights reserved
 * @author      Mautic, Inc
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

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
        $object  = $this->request->get('object');
        if (!empty($content)) {
            $data = json_decode($content, true); // 2nd param to get as array
        } else {
            return new Response('ERROR');
        }

        $logger            = $this->get('monolog.logger.mautic');
        $integration       = 'Hubspot';
        $integrationHelper = $this->factory->getHelper('integration');

        $integrationObject = $integrationHelper->getIntegrationObject($integration);

        try {
            foreach ($data['properties'] as $key => $field) {
                $fieldsValues[$key] = $field['value'];
            }
            switch ($object) {
                case 'contacts': $integrationObject->getMauticLead($fieldsValues);
                                  break;
                case 'company':

                    $integrationObject->getMauticCompany($fieldsValues);
                    break;
            }
        } catch (\Exception $ex) {
            $logger->log('error', 'ERROR on Hubspot webhook: '.$ex->getMessage());
        }

        return new Response('OK');
    }
}
