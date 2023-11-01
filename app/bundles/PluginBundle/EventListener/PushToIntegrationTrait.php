<?php

namespace Mautic\PluginBundle\EventListener;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\PluginBundle\Integration\AbstractIntegration;

/**
 * Static methods must be used due to the Point triggers not being converted to Events yet
 * Once that happens, this can be converted to a standard method classes.
 *
 * Trait PushToIntegrationTrait
 */
trait PushToIntegrationTrait
{
    /**
     * @var IntegrationHelper
     */
    protected static $integrationHelper;

    /**
     * Used by methodCalls to event subscribers.
     */
    public function setIntegrationHelper(IntegrationHelper $integrationHelper)
    {
        static::setStaticIntegrationHelper($integrationHelper);
    }

    /**
     * Used by callback methods such as point triggers.
     */
    public static function setStaticIntegrationHelper(IntegrationHelper $integrationHelper)
    {
        static::$integrationHelper = $integrationHelper;
    }

    /**
     * @param $lead
     */
    protected function pushToIntegration(array $config, Lead $lead, array &$errors = [])
    {
        return static::pushIt($config, $lead, $errors);
    }

    /**
     * Used because the the Point trigger actions have not be converted to Events yet and thus must leverage a callback.
     *
     * @param $config
     * @param $lead
     * @param $errors
     *
     * @return bool
     */
    protected static function pushIt($config, $lead, &$errors)
    {
        $integration             = (!empty($config['integration'])) ? $config['integration'] : null;
        $integrationCampaign     = (!empty($config['config']['campaigns'])) ? $config['config']['campaigns'] : null;
        $integrationMemberStatus = (!empty($config['campaign_member_status']['campaign_member_status']))
            ? $config['campaign_member_status']['campaign_member_status'] : null;
        $services = static::$integrationHelper->getIntegrationObjects($integration);
        $success  = true;

        /**
         * @var string
         * @var AbstractIntegration $s
         */
        foreach ($services as $s) {
            $settings = $s->getIntegrationSettings();
            if (!$settings->isPublished()) {
                continue;
            }

            $personIds = null;
            if (method_exists($s, 'pushLead')) {
                if (!$personIds = $s->resetLastIntegrationError()->pushLead($lead, $config)) {
                    $success = false;
                    if ($error = $s->getLastIntegrationError()) {
                        $errors[] = $error;
                    }
                }
            }

            if ($success && $integrationCampaign && method_exists($s, 'pushLeadToCampaign')) {
                if (!$s->resetLastIntegrationError()->pushLeadToCampaign($lead, $integrationCampaign, $integrationMemberStatus, $personIds)) {
                    $success = false;
                    if ($error = $s->getLastIntegrationError()) {
                        $errors[] = $error;
                    }
                }
            }
        }

        return $success;
    }
}
