<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticFullContactBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\LeadBundle\Event\CompanyEvent;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\MauticFullContactBundle\Integration\FullContactIntegration;
use MauticPlugin\MauticFullContactBundle\Services\FullContact_Company;
use MauticPlugin\MauticFullContactBundle\Services\FullContact_Person;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class LeadSubscriber extends CommonSubscriber
{
    /**
     * @var IntegrationHelper
     */
    protected $integrationHelper;

    /**
     * @var UserHelper
     */
    protected $userHelper;

    /**
     * @var LeadModel
     */
    protected $leadModel;

    /**
     * @var CompanyModel
     */
    protected $companyModel;

    /**
     * LeadSubscriber constructor.
     *
     * @param IntegrationHelper $integrationHelper
     * @param UserHelper        $userHelper
     * @param LeadModel         $leadModel
     * @param CompanyModel      $companyModel
     */
    public function __construct(IntegrationHelper $integrationHelper, UserHelper $userHelper, LeadModel $leadModel, CompanyModel $companyModel)
    {
        parent::__construct();

        $this->integrationHelper = $integrationHelper;
        $this->userHelper        = $userHelper;
        $this->leadModel         = $leadModel;
        $this->companyModel      = $companyModel;
    }

    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::LEAD_POST_SAVE    => ['leadPostSave', 0],
            LeadEvents::COMPANY_POST_SAVE => ['companyPostSave', 0],
        ];
    }

    public function leadPostSave(LeadEvent $event)
    {
        /** @var FullContactIntegration $myIntegration */
        $myIntegration = $this->integrationHelper->getIntegrationObject('FullContact');

        if (!$myIntegration->getIntegrationSettings()->getIsPublished()) {
            return;
        }

        $lead = $event->getLead();

        // get api_key from plugin settings
        $keys = $myIntegration->getDecryptedApiKeys();

        if ($myIntegration->shouldAutoUpdate()) {
            $fullcontact = new FullContact_Person($keys['apikey']);
            try {
                /** @var User $user */
                $user      = $this->userHelper->getUser();
                $webhookId = 'fullcontact_notify#'.$lead->getId().'#'.$user->getId();
                $cache     = $lead->getSocialCache() ?: [];
                $cacheId   = sprintf('%s%s', $webhookId, date(DATE_ATOM));
                if (!array_key_exists($cacheId, $cache)) {
                    $fullcontact->setWebhookUrl(
                        $this->router->generate(
                            'mautic_plugin_fullcontact_index',
                            [],
                            UrlGeneratorInterface::ABSOLUTE_URL
                        ),
                        $webhookId
                    );
                    $res             = $fullcontact->lookupByEmailMD5(md5($lead->getEmail()));
                    $cache[$cacheId] = serialize($res);
                    $lead->setSocialCache($cache);
                    $this->leadModel->saveEntity($lead);
                }
            } catch (\Exception $ex) {
                $this->logger->log('error', 'Error while using FullContact: '.$ex->getMessage());
            }
        }
    }

    public function companyPostSave(CompanyEvent $event)
    {
        /** @var FullContactIntegration $myIntegration */
        $myIntegration = $this->integrationHelper->getIntegrationObject('FullContact');

        if (!$myIntegration->getIntegrationSettings()->getIsPublished()) {
            return;
        }

        $company = $event->getCompany();

        // get api_key from plugin settings
        $keys = $myIntegration->getDecryptedApiKeys();

        if ($myIntegration->shouldAutoUpdate()) {
            $fullcontact = new FullContact_Company($keys['apikey']);
            try {
                /** @var User $user */
                $user      = $this->userHelper->getUser();
                $webhookId = 'fullcontactcomp_notify#'.$company->getId().'#'.$user->getId();
                $parse     = parse_url($company->getFieldValue('companywebsite', 'core'));
                $cache     = $company->getSocialCache() ?: [];
                $cacheId   = sprintf('%s%s', $webhookId, date(DATE_ATOM));
                if (!array_key_exists($cacheId, $cache)) {
                    $fullcontact->setWebhookUrl(
                        $this->router->generate(
                            'mautic_plugin_fullcontact_index',
                            [],
                            UrlGeneratorInterface::ABSOLUTE_URL
                        ),
                        $webhookId
                    );
                    $res             = $fullcontact->lookupByDomain($parse['host']);
                    $cache[$cacheId] = serialize($res);
                    $company->setSocialCache($cache);
                    $this->companyModel->saveEntity($company);
                }
            } catch (\Exception $ex) {
                $this->logger->log('error', 'Error while using FullContact: '.$ex->getMessage());
            }
        }
    }
}
