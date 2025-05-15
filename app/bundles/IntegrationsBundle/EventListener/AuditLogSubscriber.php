<?php

namespace Mautic\IntegrationsBundle\EventListener;

use Mautic\CoreBundle\Helper\IpLookupHelper;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\IntegrationsBundle\Event\ConfigSaveEvent;
use Mautic\IntegrationsBundle\IntegrationEvents;
use Mautic\PluginBundle\Entity\Integration;
use Mautic\PluginBundle\Event\PluginIntegrationEvent;
use Mautic\PluginBundle\PluginEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AuditLogSubscriber implements EventSubscriberInterface
{
    private AuditLogModel $auditLogModel;
    private IpLookupHelper $ipLookupHelper;
    
    // Common sensitive keys that should be sanitized
    private const SENSITIVE_KEYS = [
        'password',
        'secret',
        'token',
        'key',
        'api_key',
        'apikey',
        'client_secret',
        'clientsecret',
        'access_token',
        'accesstoken',
        'refresh_token',
        'refreshtoken',
        'private_key',
        'privatekey',
    ];

    public function __construct(AuditLogModel $auditLogModel, IpLookupHelper $ipLookupHelper)
    {
        $this->auditLogModel = $auditLogModel;
        $this->ipLookupHelper = $ipLookupHelper;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PluginEvents::PLUGIN_ON_INTEGRATION_CONFIG_SAVE => ['onConfigSave', 0],
            IntegrationEvents::INTEGRATION_CONFIG_AFTER_SAVE => ['onIntegrationConfigSave', -1],
        ];
    }

    public function onConfigSave(PluginIntegrationEvent $event): void
    {
        $this->writeToLog($event->getEntity());
    }

    public function onIntegrationConfigSave(ConfigSaveEvent $event): void
    {
        $integrationConfiguration = $event->getIntegrationConfiguration();
        $this->writeToLog($integrationConfiguration);
    }

    private function writeToLog(Integration $integration): void
    {
        $changes = $integration->getChanges();
        if (!empty($changes)) {
            $sanitizedChanges = $this->sanitizeSensitiveData($changes);
            
            $log = [
                'bundle'    => 'integration',
                'object'    => $integration->getName(),
                'objectId'  => $integration->getId(),
                'action'    => 'update',
                'details'   => $sanitizedChanges,
                'ipAddress' => $this->ipLookupHelper->getIpAddressFromRequest(),
            ];
            
            $this->auditLogModel->writeToLog($log);
        }
    }
    
    /**
     * Sanitize sensitive information from the changes array
     */
    private function sanitizeSensitiveData(array $changes): array
    {
        if (isset($changes['featureSettings']) && is_array($changes['featureSettings'])) {
            $changes['featureSettings'] = $this->processSensitiveArray($changes['featureSettings']);
        }
        
        if (isset($changes['apiKeys']) && is_array($changes['apiKeys'])) {
            $changes['apiKeys'] = $this->processSensitiveArray($changes['apiKeys']);
        }
        
        // Handle other parts of the config that might contain sensitive data
        return $this->processSensitiveArray($changes);
    }
    
    /**
     * Recursively process arrays to sanitize sensitive data
     */
    private function processSensitiveArray(array $data): array
    {
        foreach ($data as $key => $value) {
            // Recursively process nested arrays
            if (is_array($value)) {
                $data[$key] = $this->processSensitiveArray($value);
                continue;
            }
            
            // Check if the key contains any sensitive keywords
            if (is_string($key) && $this->isSensitiveKey($key)) {
                $data[$key] = '******';
            }
        }
        
        return $data;
    }
    
    /**
     * Check if a key appears to contain sensitive information
     */
    private function isSensitiveKey(string $key): bool
    {
        $key = strtolower($key);
        
        foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
            if (str_contains($key, $sensitiveKey)) {
                return true;
            }
        }
        
        return false;
    }
}