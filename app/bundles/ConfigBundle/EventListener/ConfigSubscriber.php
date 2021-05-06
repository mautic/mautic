<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\ConfigBundle\EventListener;

use Mautic\ConfigBundle\ConfigEvents;
use Mautic\ConfigBundle\Event\ConfigEvent;
use Mautic\ConfigBundle\Service\ConfigChangeLogger;
use Mautic\CoreBundle\Entity\IpAddressRepository;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ConfigSubscriber implements EventSubscriberInterface
{
    /**
     * @var ConfigChangeLogger
     */
    private $configChangeLogger;

    /**
     * @var IpAddressRepository
     */
    private $ipAddressRepository;

    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    public function __construct(ConfigChangeLogger $configChangeLogger, IpAddressRepository $ipAddressRepository, CoreParametersHelper $coreParametersHelper)
    {
        $this->configChangeLogger   = $configChangeLogger;
        $this->ipAddressRepository  = $ipAddressRepository;
        $this->coreParametersHelper = $coreParametersHelper;
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

            $oldAnonymizeIp = $originalNormData['trackingconfig']['parameters']['anonymize_ip'];
            $newAnonymizeIp = $normData['trackingconfig']['anonymize_ip'];

            if ($oldAnonymizeIp !== $newAnonymizeIp && $newAnonymizeIp && !$this->coreParametersHelper->get('delete_ip_address_in_background', false)) {
                $this->ipAddressRepository->deleteAllIpAddress();
            }
        }
    }
}
