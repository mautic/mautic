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
use Mautic\LeadBundle\Event\CompanyEvent;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\CompanyModel;
use MauticPlugin\MauticClearbitBundle\Integration\ClearbitIntegration;
use MauticPlugin\MauticClearbitBundle\Services\Clearbit_Company;
use MauticPlugin\MauticClearbitBundle\Services\Clearbit_Person;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LeadSubscriber extends CommonSubscriber
{

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * LeadSubscriber constructor.
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container) {
        parent::__construct();
        $this->container = $container;
    }

    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::LEAD_POST_SAVE => ['leadPostSave', 0],
            LeadEvents::COMPANY_POST_SAVE => ['companyPostSave', 0],
        ];
    }

    public function leadPostSave(LeadEvent $event) {
        $integrationHelper = $this->container->get('mautic.helper.integration');
        /** @var ClearbitIntegration $myIntegration */
        $myIntegration = $integrationHelper->getIntegrationObject('Clearbit');

        if (!$myIntegration->getIntegrationSettings()->getIsPublished()) {
            return;
        }

        $lead = $event->getLead();
        $logger = $this->container->get('monolog.logger.mautic');

        // get api_key from plugin settings
        $keys = $myIntegration->getDecryptedApiKeys();

        if ($myIntegration->shouldAutoUpdate()) {

            $clearbit = new Clearbit_Person($keys['apikey']);
            try {
                /** @var User $user */
                $user = $this->container->get('security.token_storage')->getToken()->getUser();
                $webhookId = 'clearbit_notify#'.$lead->getId().'#'.$user->getId();
                $cache = $lead->getSocialCache();
                $cacheId = sprintf('%s%s', $webhookId, date(DATE_ATOM));
                if (!array_key_exists($cacheId, $cache)) {
                    /** @var Router $router */
                    $clearbit->setWebhookId($webhookId);
                    $res = $clearbit->lookupByEmail($lead->getEmail());
                    $cache[$cacheId] = serialize($res);
                    $lead->setSocialCache($cache);
                    /** @var \Mautic\LeadBundle\Model\CompanyModel $model */
                    $model =  $this->container->get('mautic.factory')->getModel('lead');
                    $model->saveEntity($lead);
                }
            } catch (\Exception $ex) {
                $logger->log('error', 'Error while using Clearbit: '.$ex->getMessage());
            }
        }
    }

    public function companyPostSave(CompanyEvent $event) {
        $integrationHelper = $this->container->get('mautic.helper.integration');
        /** @var ClearbitIntegration $myIntegration */
        $myIntegration = $integrationHelper->getIntegrationObject('Clearbit');

        if (!$myIntegration->getIntegrationSettings()->getIsPublished()) {
            return;
        }

        $company = $event->getCompany();
        $logger = $this->container->get('monolog.logger.mautic');

        // get api_key from plugin settings
        $keys = $myIntegration->getDecryptedApiKeys();

        if ($myIntegration->shouldAutoUpdate()) {

            $clearbit = new Clearbit_Company($keys['apikey']);
            try {
                /** @var User $user */
                $user = $this->container->get('security.token_storage')->getToken()->getUser();
                $webhookId = 'clearbitcomp_notify#'.$company->getId().'#'.$user->getId();
                $parse = parse_url($company->getFieldValue('companywebsite', 'core'));

                $cache = $company->getSocialCache();
                $cacheId = sprintf('%s%s', $webhookId, date(DATE_ATOM));
                if (!array_key_exists($cacheId, $cache)) {
                    /** @var Router $router */
                    $clearbit->setWebhookId($webhookId);
                    $res = $clearbit->lookupByDomain($parse['host']);
                    $cache[$cacheId] = serialize($res);
                    $company->setSocialCache($cache);
                    /** @var CompanyModel $model */
                    $model =  $this->container->get('mautic.factory')->getModel('lead.company');
                    $model->saveEntity($company);
                }
            } catch (\Exception $ex) {
                $logger->log('error', 'Error while using Clearbit: '.$ex->getMessage());
            }
        }
    }
}
