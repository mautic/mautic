<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\EventListener;

use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\IntegrationsBundle\Event\ConfigSaveEvent;
use Mautic\IntegrationsBundle\IntegrationEvents;
use Mautic\PluginBundle\Entity\Integration;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AuditLogSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AuditLogModel $auditLogModel,
        private IpLookupHelper $ipLookupHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            IntegrationEvents::INTEGRATION_CONFIG_AFTER_SAVE => 'onIntegrationConfigSave',
        ];
    }

    public function onIntegrationConfigSave(ConfigSaveEvent $event): void
    {
        $integration = $event->getIntegrationConfiguration();

        // Sanitize sensitive data
        $details = $this->sanitizeDetails($integration);

        $this->auditLogModel->writeToLog([
            'bundle'    => 'integrations',
            'object'    => 'integration',
            'objectId'  => $integration->getId(),
            'action'    => 'update',
            'details'   => $details,
            'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
        ]);
    }

    /**
     * Sanitizes integration details to remove sensitive information before logging.
     *
     * @return array<string, mixed>
     */
    private function sanitizeDetails(Integration $integration): array
    {
        $details = [
            'name'            => $integration->getName(),
            'isPublished'     => $integration->getIsPublished(),
            'apiKeys'         => [],
            'featureSettings' => [],
        ];

        // Get API keys but mask sensitive values
        $apiKeys = $integration->getApiKeys();
        if (is_array($apiKeys)) {
            foreach ($apiKeys as $key => $value) {
                // Don't log actual sensitive values, just note that they were changed
                if (in_array(strtolower($key), ['token', 'secret', 'password', 'key', 'client_secret', 'client_id', 'refresh_token'])) {
                    $details['apiKeys'][$key] = '[MASKED]';
                } else {
                    $details['apiKeys'][$key] = $value;
                }
            }
        }

        // Get feature settings but mask sensitive values
        $featureSettings = $integration->getFeatureSettings();
        if (is_array($featureSettings)) {
            // Recursive function to mask sensitive data in nested arrays
            $maskSensitiveData = function ($data) use (&$maskSensitiveData) {
                if (!is_array($data)) {
                    return $data;
                }

                $result = [];
                foreach ($data as $key => $value) {
                    if (is_array($value)) {
                        $result[$key] = $maskSensitiveData($value);
                    } else {
                        // Mask values in keys that suggest they contain sensitive information
                        if (preg_match('/(password|token|secret|key|credential|auth)/i', $key)) {
                            $result[$key] = '[MASKED]';
                        } else {
                            $result[$key] = $value;
                        }
                    }
                }

                return $result;
            };

            $details['featureSettings'] = $maskSensitiveData($featureSettings);
        }

        return $details;
    }
}
