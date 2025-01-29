<?php

namespace Mautic\ConfigBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\ConfigBundle\Service\ConfigChangeLogger;
use Mautic\CoreBundle\Entity\AuditLogRepository;
use Mautic\CoreBundle\Entity\IpAddressRepository;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private ConfigChangeLogger $configChangeLogger,
        private IpAddressRepository $ipAddressRepository,
        private CoreParametersHelper $coreParametersHelper,
        private AuditLogRepository $auditLogRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ConfigEvents::CONFIG_POST_SAVE => ['onConfigPostSave', 0],
        ];
    }

    public function onConfigPostSave(ConfigEvent $event): void
    {
        if ($originalNormData = $event->getOriginalNormData()) {
            $normData = $event->getNormData();
            // We have something to log
            $this->configChangeLogger
                ->setOriginalNormData($originalNormData)
                ->log($normData);

            if (!isset($originalNormData['trackingconfig']) && !isset($normData['trackingconfig'])) {
                return;
            }

            $oldAnonymizeIp = $originalNormData['trackingconfig']['parameters']['anonymize_ip'];
            $newAnonymizeIp = $normData['trackingconfig']['anonymize_ip'];

            if ($oldAnonymizeIp !== $newAnonymizeIp && $newAnonymizeIp && !$this->coreParametersHelper->get('anonymize_ip_address_in_background', false)) {
                $this->ipAddressRepository->anonymizeAllIpAddress();
                $this->auditLogRepository->anonymizeAllIpAddress();
            }
        }
    }
}
