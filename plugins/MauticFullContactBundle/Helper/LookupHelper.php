<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFullContactBundle\Helper;

use Mautic\CoreBundle\Helper\EncryptionHelper;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\LeadBundle\Entity\Company;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticFullContactBundle\Integration\FullContactIntegration;
use MauticPlugin\MauticFullContactBundle\Services\FullContact_Company;
use MauticPlugin\MauticFullContactBundle\Services\FullContact_Person;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LookupHelper
{
    /**
     * @var UserHelper
     */
    protected $userHelper;

    /**
     * @var bool|FullContactIntegration
     */
    protected $integration;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Router
     */
    protected $router;

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var CompanyModel
     */
    protected $companyModel;

    public function __construct(
        IntegrationHelper $integrationHelper,
        UserHelper $userHelper,
        Logger $logger,
        Router $router,
        LeadModel $leadModel,
        CompanyModel $companyModel
    ) {
        $this->integration  = $integrationHelper->getIntegrationObject('FullContact');
        $this->userHelper   = $userHelper;
        $this->logger       = $logger;
        $this->router       = $router;
        $this->leadModel    = $leadModel;
        $this->companyModel = $companyModel;
    }

    /**
     * @param bool $notify
     * @param bool $checkAuto
     */
    public function lookupContact(Lead $lead, $notify = false, $checkAuto = false)
    {
        if (!$lead->getEmail()) {
            return;
        }

        /** @var FullContact_Person $fullcontact */
        if ($fullcontact = $this->getFullContact()) {
            if (!$checkAuto || ($checkAuto && $this->integration->shouldAutoUpdate())) {
                try {
                    list($cacheId, $webhookId, $cache) = $this->getCache($lead, $notify);

                    if (!array_key_exists($cacheId, $cache['fullcontact'])) {
                        $fullcontact->setWebhookUrl(
                            $this->router->generate(
                                'mautic_plugin_fullcontact_index',
                                [],
                                UrlGeneratorInterface::ABSOLUTE_URL
                            ),
                            $webhookId
                        );
                        $res = $fullcontact->lookupByEmail($lead->getEmail());
                        // Prevent from filling up the cache
                        $cache['fullcontact'] = [
                            $cacheId => serialize($res),
                            'nonce'  => $cache['fullcontact']['nonce'],
                        ];
                        $lead->setSocialCache($cache);

                        if ($checkAuto) {
                            $this->leadModel->getRepository()->saveEntity($lead);
                        } else {
                            $this->leadModel->saveEntity($lead);
                        }
                    }
                } catch (\Exception $ex) {
                    $this->logger->log('error', 'Error while using FullContact to lookup '.$lead->getEmail().': '.$ex->getMessage());
                }
            }
        }
    }

    /**
     * @param bool $notify
     * @param bool $checkAuto
     */
    public function lookupCompany(Company $company, $notify = false, $checkAuto = false)
    {
        if (!$website = $company->getFieldValue('companywebsite')) {
            return;
        }

        /** @var FullContact_Company $fullcontact */
        if ($fullcontact = $this->getFullContact(false)) {
            if (!$checkAuto || ($checkAuto && $this->integration->shouldAutoUpdate())) {
                try {
                    $parse                             = parse_url($website);
                    list($cacheId, $webhookId, $cache) = $this->getCache($company, $notify);

                    if (isset($parse['host']) && !array_key_exists($cacheId, $cache['fullcontact'])) {
                        $fullcontact->setWebhookUrl(
                            $this->router->generate(
                                'mautic_plugin_fullcontact_index',
                                [],
                                UrlGeneratorInterface::ABSOLUTE_URL
                            ),
                            $webhookId
                        );
                        $res = $fullcontact->lookupByDomain($parse['host']);
                        // Prevent from filling up the cache
                        $cache['fullcontact'] = [
                            $cacheId => serialize($res),
                            'nonce'  => $cache['fullcontact']['nonce'],
                        ];
                        $company->setSocialCache($cache);
                        if ($checkAuto) {
                            $this->companyModel->getRepository()->saveEntity($company);
                        } else {
                            $this->companyModel->saveEntity($company);
                        }
                    }
                } catch (\Exception $ex) {
                    $this->logger->log('error', 'Error while using FullContact to lookup '.$parse['host'].': '.$ex->getMessage());
                }
            }
        }
    }

    /**
     * @param $oid
     */
    public function validateRequest($oid)
    {
        // prefix#entityId#hour#userId#nonce
        list($w, $id, $hour, $uid, $nonce) = explode('#', $oid, 5);
        $notify                            = (false !== strpos($w, '_notify') && $uid) ? $uid : false;
        $type                              = (0 === strpos($w, 'fullcontactcomp')) ? 'company' : 'person';

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

            if (isset($socialCache['fullcontact'][$cacheId]) && !empty($socialCache['fullcontact']['nonce']) && !empty($nonce)
                && $socialCache['fullcontact']['nonce'] === $nonce
            ) {
                return [
                    'notify' => $notify,
                    'entity' => $entity,
                    'type'   => $type,
                ];
            }
        }

        return false;
    }

    /**
     * @param bool $person
     *
     * @return bool|FullContact_Company|FullContact_Person
     */
    protected function getFullContact($person = true)
    {
        if (!$this->integration || !$this->integration->getIntegrationSettings()->getIsPublished()) {
            return false;
        }

        // get api_key from plugin settings
        $keys = $this->integration->getDecryptedApiKeys();

        return ($person) ? new FullContact_Person($keys['apikey']) : new FullContact_Company($keys['apikey']);
    }

    /**
     * @param $entity
     * @param $notify
     *
     * @return array
     */
    protected function getCache($entity, $notify)
    {
        /** @var User $user */
        $user      = $this->userHelper->getUser();
        $nonce     = substr(EncryptionHelper::generateKey(), 0, 16);
        $cacheId   = sprintf('fullcontact%s%s#', $entity instanceof Company ? 'comp' : '', $notify ? '_notify' : '').$entity->getId().'#'.gmdate('YmdH');
        $webhookId = $cacheId.'#'.$user->getId().'#'.$nonce;

        $cache = $entity->getSocialCache();
        if (!isset($cache['fullcontact'])) {
            $cache['fullcontact'] = [];
        }

        $cache['fullcontact']['nonce'] = $nonce;

        return [$cacheId, $webhookId, $cache];
    }
}
