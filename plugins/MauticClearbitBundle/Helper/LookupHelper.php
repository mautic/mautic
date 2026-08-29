<?php

declare(strict_types=1);

namespace MauticPlugin\MauticClearbitBundle\Helper;

use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\CompanyRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\LeadModel;
use MauticPlugin\MauticClearbitBundle\Integration\ClearbitIntegration;
use MauticPlugin\MauticClearbitBundle\Services\Clearbit_Company;
use MauticPlugin\MauticClearbitBundle\Services\Clearbit_Person;
use Psr\Log\LoggerInterface;

final class LookupHelper
{
    private ?ClearbitIntegration $integration = null;

    public function __construct(
        IntegrationsHelper $integrationsHelper,
        private readonly UserHelper $userHelper,
        private readonly LoggerInterface $logger,
        private readonly LeadModel $leadModel,
        private readonly CompanyModel $companyModel,
        private readonly LeadRepository $leadRepository,
        private readonly CompanyRepository $companyRepository,
    ) {
        try {
            /** @var ClearbitIntegration $integration */
            $integration       = $integrationsHelper->getIntegration('Clearbit');
            $this->integration = $integration;
        } catch (IntegrationNotFoundException) {
            $this->integration = null;
        }
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

        if (($clearbit = $this->getClearbit()) && (!$checkAuto || $this->integration->shouldAutoUpdate())) {
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
                        $this->leadRepository->saveEntity($lead);
                    } else {
                        $this->leadModel->saveEntity($lead);
                    }
                }
            } catch (\Exception $ex) {
                $this->logger->log('error', 'Error while using Clearbit to lookup '.$lead->getEmail().': '.$ex->getMessage());
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

        if (($clearbit = $this->getClearbit(false)) && (!$checkAuto || $this->integration->shouldAutoUpdate())) {
            try {
                $parse                             = parse_url($company->getFieldValue('companywebsite'));
                [$cacheId, $webhookId, $cache]     = $this->getCache($company, $notify);

                if (isset($parse['host']) && !array_key_exists($cacheId, $cache['clearbit'])) {
                    $clearbit->setWebhookId($webhookId);
                    $res = $clearbit->lookupByDomain($parse['host']);
                    // Prevent from filling up the cache
                    $cache['clearbit'] = [
                        $cacheId => serialize($res),
                        'nonce'  => $cache['clearbit']['nonce'],
                    ];
                    $company->setSocialCache($cache);
                    if ($checkAuto) {
                        $this->companyRepository->saveEntity($company);
                    } else {
                        $this->companyModel->saveEntity($company);
                    }
                }
            } catch (\Exception $ex) {
                $this->logger->log('error', 'Error while using Clearbit to lookup '.$parse['host'].': '.$ex->getMessage());
            }
        }
    }

    /**
     * @return array{notify: mixed, entity: mixed}|false
     */
    public function validateRequest($oid, $type): array|false
    {
        // prefix#entityId#hour#userId#nonce
        [$w, $id, $hour, $uid, $nonce]     = explode('#', $oid, 5);
        $notify                            = (str_contains($w, '_notify') && $uid) ? $uid : false;

        $entity = null;

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

    private function getClearbit(bool $person = true): false|Clearbit_Person|Clearbit_Company
    {
        if (!$this->integration || !$this->integration->getIntegrationConfiguration()->getIsPublished()) {
            return false;
        }

        // get api_key from plugin settings
        $keys = $this->integration->getIntegrationConfiguration()->getApiKeys();

        return ($person) ? new Clearbit_Person($keys['apikey']) : new Clearbit_Company($keys['apikey']);
    }

    private function getCache(Lead|Company $entity, $notify): array
    {
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
