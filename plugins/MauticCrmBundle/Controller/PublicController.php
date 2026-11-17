<?php

namespace MauticPlugin\MauticCrmBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;
use Mautic\IntegrationsBundle\Sync\DAO\Sync\ObjectIdsDAO;
use Mautic\IntegrationsBundle\Sync\SyncService\SyncServiceInterface;
use MauticPlugin\MauticCrmBundle\Hubspot\Client\HubspotClient;
use MauticPlugin\MauticCrmBundle\Hubspot\Config;
use MauticPlugin\MauticCrmBundle\Integration\HubspotIntegration;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class PublicController extends CommonController
{
    /**
     * HubSpot webhook: pulls the contacts and companies it notifies about.
     */
    public function contactDataAction(Request $request, LoggerInterface $mauticLogger, SyncServiceInterface $syncService, Config $config): Response
    {
        $data = json_decode($request->getContent(), true);
        if (!is_array($data)) {
            return new Response('ERROR');
        }

        if (!$config->isPublished()) {
            return new Response('OK');
        }

        $objectIds = new ObjectIdsDAO();
        $hasIds    = false;
        foreach ($data as $info) {
            // e.g. "contact.propertyChange"
            $object = match (explode('.', (string) ($info['subscriptionType'] ?? ''))[0]) {
                'contact' => HubspotClient::CONTACTS,
                'company' => HubspotClient::COMPANIES,
                default   => null,
            };

            if (null !== $object && !empty($info['objectId'])) {
                $objectIds->addObjectId($object, (string) $info['objectId']);
                $hasIds = true;
            }
        }

        if (!$hasIds) {
            return new Response('OK');
        }

        try {
            $syncService->processIntegrationSync(new InputOptionsDAO([
                'integration'           => HubspotIntegration::NAME,
                'disable-push'          => true,
                'integration-object-id' => $objectIds,
            ]));
        } catch (\Exception $e) {
            $mauticLogger->error('ERROR on Hubspot webhook: '.$e->getMessage());
        }

        return new Response('OK');
    }
}
