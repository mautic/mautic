<?php
/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MauticClearbitBundle\EventListener;

use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\UserHelper;
use Mautic\LeadBundle\Event\CompanyEvent;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PluginBundle\Helper\IntegrationHelper;
use MauticPlugin\MauticClearbitBundle\Integration\ClearbitIntegration;
use MauticPlugin\MauticClearbitBundle\Services\Clearbit_Company;
use MauticPlugin\MauticClearbitBundle\Services\Clearbit_Person;
use Symfony\Bundle\FrameworkBundle\Routing\Router;

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
        /** @var ClearbitIntegration $myIntegration */
        $myIntegration = $this->integrationHelper->getIntegrationObject('Clearbit');

        if (!$myIntegration->getIntegrationSettings()->getIsPublished()) {
            return;
        }

        $lead = $event->getLead();

        // get api_key from plugin settings
        $keys = $myIntegration->getDecryptedApiKeys();

        if ($myIntegration->shouldAutoUpdate()) {
            $clearbit = new Clearbit_Person($keys['apikey']);
            try {
                /** @var User $user */
                $user      = $this->userHelper->getUser();
                $webhookId = 'clearbit_notify#'.$lead->getId().'#'.$user->getId();
                $cache     = $lead->getSocialCache();
                $cacheId   = sprintf('%s%s', $webhookId, date('YmdH'));
                if (!array_key_exists($cacheId, $cache)) {
                    /* @var Router $router */
                    $clearbit->setWebhookId($webhookId);
                    $res             = $clearbit->lookupByEmail($lead->getEmail());
                    $cache[$cacheId] = serialize($res);
                    $lead->setSocialCache($cache);
                    $this->leadModel->getRepository()->saveEntity($lead);
                }
            } catch (\Exception $ex) {
                $this->logger->log('error', 'Error while using Clearbit: '.$ex->getMessage());
            }
        }
    }

    public function companyPostSave(CompanyEvent $event)
    {
        /** @var ClearbitIntegration $myIntegration */
        $myIntegration = $this->integrationHelper->getIntegrationObject('Clearbit');

        if (!$myIntegration->getIntegrationSettings()->getIsPublished()) {
            return;
        }

        $company = $event->getCompany();

        // get api_key from plugin settings
        $keys = $myIntegration->getDecryptedApiKeys();

        if ($myIntegration->shouldAutoUpdate()) {
            $clearbit = new Clearbit_Company($keys['apikey']);
            try {
                /** @var User $user */
                $user      = $this->userHelper->getUser();
                $webhookId = 'clearbitcomp_notify#'.$company->getId().'#'.$user->getId();
                $parse     = parse_url($company->getFieldValue('companywebsite', 'core'));

                $cache   = $company->getSocialCache();
                $cacheId = sprintf('%s%s', $webhookId, date('YmdH'));
                if (!array_key_exists($cacheId, $cache)) {
                    /* @var Router $router */
                    $clearbit->setWebhookId($webhookId);
                    $res             = $clearbit->lookupByDomain($parse['host']);
                    $cache[$cacheId] = serialize($res);
                    $company->setSocialCache($cache);
                    $this->companyModel->getRepository()->saveEntity($company);
                }
            } catch (\Exception $ex) {
                $this->logger->log('error', 'Error while using Clearbit: '.$ex->getMessage());
            }
        }
    }
}
