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
    /**
     * @param ModelFactory<object> $modelFactory
     */
    public function __construct(
        protected \Doctrine\Persistence\ManagerRegistry $doctrine,
        protected \Mautic\CoreBundle\Factory\ModelFactory $modelFactory,
        \Mautic\CoreBundle\Helper\UserHelper $userHelper,
        protected \Mautic\CoreBundle\Helper\CoreParametersHelper $coreParametersHelper,
        protected \Symfony\Component\EventDispatcher\EventDispatcherInterface $dispatcher,
        protected \Mautic\CoreBundle\Translation\Translator $translator,
        private \Mautic\CoreBundle\Service\FlashBag $flashBag,
        private \Symfony\Component\HttpFoundation\RequestStack $requestStack,
        protected \Mautic\CoreBundle\Security\Permissions\CorePermissions $security,
        private readonly LoggerInterface $mauticLogger,
        private readonly IntegrationHelper $integrationHelper,
    ) {
        parent::__construct($doctrine, $modelFactory, $userHelper, $coreParametersHelper, $dispatcher, $translator, $flashBag, $requestStack, $security);
    }

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
}
