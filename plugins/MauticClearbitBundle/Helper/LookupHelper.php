<?php

namespace MauticPlugin\MauticClearbitBundle\Helper;

use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticClearbitBundle\Integration\ClearbitIntegration;
use MauticPlugin\MauticClearbitBundle\Services\Clearbit_Company;
use MauticPlugin\MauticClearbitBundle\Services\Clearbit_Person;
use Monolog\Logger;

class LookupHelper
{
    /**
     * @var bool|ClearbitIntegration
     */
    protected $integration;

    public function __construct(
        IntegrationHelper $integrationHelper,
        protected UserHelper $userHelper,
        protected Logger $logger,
        protected LeadModel $leadModel,
        protected CompanyModel $companyModel,
    ) {
        $this->integration  = $integrationHelper->getIntegrationObject('Clearbit');
    }

    /**
     * @param bool $notify
     * @param bool $checkAuto
     */
    public function lookupContact(Lead $lead, $notify = false, $checkAuto = false): void
    {
        if (!$lead->getEmail()) {
            return;
        }

        /* @var Clearbit_Person $clearbit */
        if ($clearbit = $this->getClearbit()) {
            if (!$checkAuto || ($checkAuto && $this->integration->shouldAutoUpdate())) {
                try {
                    [$cacheId, $webhookId, $cache] = $this->getCache($lead, $notify);

                    if (!array_key_exists($cacheId, $cache['clearbit'])) {
                        $clearbit->setWebhookId($webhookId);
                        $res = $clearbit->lookupByEmail($lead->getEmail());
                        // Prevent from filling up the cache
                        $cache['clearbit'] = [
                            $cacheId => serialize($res),
                            'nonce'  => $cache['clearbit']['nonce'],
                        ];
                        $lead->setSocialCache($cache);

                        if ($checkAuto) {
                            $this->leadModel->getRepository()->saveEntity($lead);
                        } else {
                            $this->leadModel->saveEntity($lead);
                        }
                    }
                } catch (\Exception $ex) {
                    $this->logger->log('error', 'Error while using Clearbit to lookup '.$lead->getEmail().': '.$ex->getMessage());
                }
            }
        }
    }

    /**
     * @param bool $notify
     * @param bool $checkAuto
     */
    public function lookupCompany(Company $company, $notify = false, $checkAuto = false): void
    {
        if (!$website = $company->getFieldValue('companywebsite')) {
            return;
        }

        /* @var Clearbit_Company $clearbit */
        if ($clearbit = $this->getClearbit(false)) {
            if (!$checkAuto || ($checkAuto && $this->integration->shouldAutoUpdate())) {
                try {
                    $parse                             = parse_url($company->getFieldValue('companywebsite'));
                    [$cacheId, $webhookId, $cache]     = $this->getCache($company, $notify);

                    if (isset($parse['host']) && !array_key_exists($cacheId, $cache['clearbit'])) {
                        /* @var Router $router */
                        $clearbit->setWebhookId($webhookId);
                        $res = $clearbit->lookupByDomain($parse['host']);
                        // Prevent from filling up the cache
                        $cache['clearbit'] = [
                            $cacheId => serialize($res),
                            'nonce'  => $cache['clearbit']['nonce'],
                        ];
                        $company->setSocialCache($cache);
                        if ($checkAuto) {
                            $this->companyModel->getRepository()->saveEntity($company);
                        } else {
                            $this->companyModel->saveEntity($company);
                        }
                    }
                } catch (\Exception $ex) {
                    $this->logger->log('error', 'Error while using Clearbit to lookup '.$parse['host'].': '.$ex->getMessage());
                }
            }
        }
    }

    public function validateRequest($oid, $type)
    {
        // prefix#entityId#hour#userId#nonce
        [$w, $id, $hour, $uid, $nonce]     = explode('#', $oid, 5);
        $notify                            = (str_contains($w, '_notify') && $uid) ? $uid : false;

        switch ($type) {
            case 'person':
                $entity = $this->leadModel->getEntity($id);
                break;
            case 'company':
                $entity = $this->companyModel->getEntity($id);
                break;
        }

        if ($entity) {
            $socialCache = $entity->getSocialCache();
            $cacheId     = $w.'#'.$id.'#'.$hour;

            if (isset($socialCache['clearbit'][$cacheId]) && !empty($socialCache['clearbit']['nonce']) && !empty($nonce)
                && $socialCache['clearbit']['nonce'] === $nonce
            ) {
                return [
                    'notify' => $notify,
                    'entity' => $entity,
                ];
            }
        }

        return false;
    }

    /**
     * @param bool $person
     *
     * @return bool|Clearbit_Company|Clearbit_Person
     */
    protected function getClearbit($person = true)
    {
        if (!$this->integration || !$this->integration->getIntegrationSettings()->getIsPublished()) {
            return false;
        }

        // get api_key from plugin settings
        $keys = $this->integration->getDecryptedApiKeys();

        return ($person) ? new Clearbit_Person($keys['apikey']) : new Clearbit_Company($keys['apikey']);
    }

    protected function getCache($entity, $notify): array
    {
        /** @var User $user */
        $user      = $this->userHelper->getUser();
        $nonce     = substr(EncryptionHelper::generateKey(), 0, 16);
        $cacheId   = sprintf('clearbit%s#', $notify ? '_notify' : '').$entity->getId().'#'.gmdate('YmdH');
        $webhookId = $cacheId.'#'.$user->getId().'#'.$nonce;

        $cache = $entity->getSocialCache();
        if (!isset($cache['clearbit'])) {
            $cache['clearbit'] = [];
        }

        $cache['clearbit']['nonce'] = $nonce;

        return [$cacheId, $webhookId, $cache];
    }
}
