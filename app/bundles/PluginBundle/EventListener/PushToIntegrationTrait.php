<?php

namespace Mautic\PluginBundle\EventListener;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Integration\AbstractIntegration;
use Symfony\Contracts\Service\Attribute\Required;

/**
 * Static methods must be used due to the Point triggers not being converted to Events yet
 * Once that happens, this can be converted to a standard method classes.
 */
trait PushToIntegrationTrait
{
    protected IntegrationHelper $integrationHelper;

    /**
     * Used by methodCalls to event subscribers.
     */
    #[Required]
    public function autowirePushToIntegrationTrait(
        IntegrationHelper $integrationHelper,
    ): void {
        $this->integrationHelper = $integrationHelper;
    }

    protected function pushToIntegration(array $config, Lead $lead, array &$errors = []): bool
    {
        return $this->pushIt($config, $lead, $errors);
    }

    /**
     * Used because the the Point trigger actions have not be converted to Events yet and thus must leverage a callback.
     */
    protected function pushIt(array $config, $lead, &$errors): bool
    {
        $integration             = (!empty($config['integration'])) ? $config['integration'] : null;
        $integrationCampaign     = (!empty($config['config']['campaigns'])) ? $config['config']['campaigns'] : null;
        $integrationMemberStatus = (!empty($config['campaign_member_status']['campaign_member_status']))
            ? $config['campaign_member_status']['campaign_member_status'] : null;
        $services = $this->integrationHelper->getIntegrationObjects($integration);
        $success  = true;

        foreach ($services as $service) {
            /** @var AbstractIntegration $service */
            $settings = $service->getIntegrationSettings();
            if (!$settings->isPublished()) {
                continue;
            }

            $personIds = null;
            if (method_exists($service, 'pushLead') && !$personIds = $service->resetLastIntegrationError()->pushLead($lead, $config)) {
                $success = false;
                if ($error = $service->getLastIntegrationError()) {
                    $errors[] = $error;
                }
            }

            if ($success && $integrationCampaign && method_exists($service, 'pushLeadToCampaign') && !$service->resetLastIntegrationError()->pushLeadToCampaign($lead, $integrationCampaign, $integrationMemberStatus)) {
                $success = false;
                if ($error = $service->getLastIntegrationError()) {
                    $errors[] = $error;
                }
            }
        }

        return $success;
    }
}
