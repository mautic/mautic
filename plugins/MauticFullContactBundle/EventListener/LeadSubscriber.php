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
use Mautic\LeadBundle\Event\CompanyEvent;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\UserBundle\Entity\User;
use MauticPlugin\MauticFullContactBundle\Integration\FullContactIntegration;
use MauticPlugin\MauticFullContactBundle\Services\FullContact_Company;
use MauticPlugin\MauticFullContactBundle\Services\FullContact_Person;
use Symfony\Bundle\FrameworkBundle\Routing\Router;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
        /** @var FullContactIntegration $myIntegration */
        $myIntegration = $integrationHelper->getIntegrationObject('FullContact');

        if (!$myIntegration->getIntegrationSettings()->getIsPublished()) {
            return;
        }

        $lead = $event->getLead();
        $logger = $this->container->get('monolog.logger.mautic');

        // get api_key from plugin settings
        $keys = $myIntegration->getDecryptedApiKeys();

        if ($myIntegration->shouldAutoUpdate()) {

            $fullcontact = new FullContact_Person($keys['apikey']);
            try {
                /** @var User $user */
                $user = $this->container->get('security.token_storage')->getToken()->getUser();
                $webhookId = 'fullcontact_notify#'.$lead->getId().'#'.$user->getId();
                $cache = $lead->getSocialCache()?:[];
                $cacheId = sprintf('%s%s', $webhookId, date(DATE_ATOM));
                if (!array_key_exists($cacheId, $cache)) {
                    /** @var Router $router */
                    $router = $this->container->get('router');
                    $fullcontact->setWebhookUrl(
                        $router->generate(
                            'mautic_plugin_fullcontact_index',
                            [],
                            UrlGeneratorInterface::ABSOLUTE_URL
                        ),
                        $webhookId
                    );
                    $res = $fullcontact->lookupByEmailMD5(md5($lead->getEmail()));
                    $cache[$cacheId] = serialize($res);
                    $lead->setSocialCache($cache);
                    /** @var \Mautic\LeadBundle\Model\CompanyModel $model */
                    $model = $this->container->get('mautic.factory')->getModel('lead');
                    $model->saveEntity($lead);
                }
            } catch (\Exception $ex) {
                $logger->log('error', 'Error while using FullContact: '.$ex->getMessage());
            }
        }
    }

    public function companyPostSave(CompanyEvent $event) {
        $integrationHelper = $this->container->get('mautic.helper.integration');
        /** @var FullContactIntegration $myIntegration */
        $myIntegration = $integrationHelper->getIntegrationObject('FullContact');

        if (!$myIntegration->getIntegrationSettings()->getIsPublished()) {
            return;
        }

        $company = $event->getCompany();
        $logger = $this->container->get('monolog.logger.mautic');

        // get api_key from plugin settings
        $keys = $myIntegration->getDecryptedApiKeys();

        if ($myIntegration->shouldAutoUpdate()) {

            $fullcontact = new FullContact_Company($keys['apikey']);
            try {
                /** @var User $user */
                $user = $this->container->get('security.token_storage')->getToken()->getUser();
                $webhookId = 'fullcontactcomp_notify#'.$company->getId().'#'.$user->getId();
                $parse = parse_url($company->getFieldValue('companywebsite', 'core'));
                $cache = $company->getSocialCache()?:[];
                $cacheId = sprintf('%s%s', $webhookId, date(DATE_ATOM));
                if (!array_key_exists($cacheId, $cache)) {
                    /** @var Router $router */
                    $router = $this->container->get('router');
                    $fullcontact->setWebhookUrl(
                        $router->generate(
                            'mautic_plugin_fullcontact_index',
                            [],
                            UrlGeneratorInterface::ABSOLUTE_URL
                        ),
                        $webhookId
                    );
                    $res = $fullcontact->lookupByDomain($parse['host']);
                    $cache[$cacheId] = serialize($res);
                    $company->setSocialCache($cache);
                    /** @var \Mautic\LeadBundle\Model\CompanyModel $model */
                    $model = $this->container->get('mautic.factory')->getModel('lead.company');
                    $model->saveEntity($company);
                }
            } catch (\Exception $ex) {
                $logger->log('error', 'Error while using FullContact: '.$ex->getMessage());
            }
        }
    }
}
