<?php

namespace MauticPlugin\MauticCrmBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticCrmBundle\Integration\HubspotIntegration;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class PublicController extends CommonController
{
    private LoggerInterface $mauticLogger;
    private IntegrationHelper $integrationHelper;

    public function contactDataAction(Request $request): Response
    {
        $content = $request->getContent();
        if (!empty($content)) {
            $data = json_decode($content, true); // 2nd param to get as array
        } else {
            return new Response('ERROR');
        }

        $integration = 'Hubspot';

        $integrationObject = $this->integrationHelper->getIntegrationObject($integration);
        \assert($integrationObject instanceof HubspotIntegration);

        foreach ($data as $info) {
            $object = explode('.', $info['subscriptionType']);
            $id     = $info['objectId'];

            try {
                switch ($object[0]) {
                    case 'contact':
                        $executed = [];
                        $integrationObject->getLeads($id, null, $executed);
                        break;
                    case 'company':
                        $integrationObject->getCompanies($id);
                        break;
                }
            } catch (\Exception $ex) {
                $this->mauticLogger->log('error', 'ERROR on Hubspot webhook: '.$ex->getMessage());
            }
        }

        return new Response('OK');
    }

    #[\Symfony\Contracts\Service\Attribute\Required]
    public function autowirePublicController(
        LoggerInterface $mauticLogger,
        IntegrationHelper $integrationHelper,
    ): void {
        $this->mauticLogger = $mauticLogger;
        $this->integrationHelper = $integrationHelper;
    }
}
