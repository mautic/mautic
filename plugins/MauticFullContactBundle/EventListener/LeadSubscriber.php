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
        $lead = $event->getLead();
        $logger = $this->container->get('monolog.logger.mautic');

        // get api_key from plugin settings
        $integrationHelper = $this->container->get('mautic.helper.integration');
        /** @var FullContactIntegration $myIntegration */
        $myIntegration = $integrationHelper->getIntegrationObject('FullContact');
        $keys = $myIntegration->getDecryptedApiKeys();

        if ($myIntegration->shouldAutoUpdate()) {

            $fullcontact = new FullContact_Person($keys['apikey']);
            try {
                $webhookId = 'fullcontact#'.$lead->getId();
                if (FALSE === apc_fetch($webhookId.$lead->getEmail())) {
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
                    apc_add($webhookId.$lead->getEmail(), $res);
                }
            } catch (\Exception $ex) {
                $logger->log('error', 'Error while using FullContact: '.$ex->getMessage());
            }
        }
    }

    public function companyPostSave(CompanyEvent $event) {
        $company = $event->getCompany();
        $logger = $this->container->get('monolog.logger.mautic');

        // get api_key from plugin settings
        $integrationHelper = $this->container->get('mautic.helper.integration');
        /** @var FullContactIntegration $myIntegration */
        $myIntegration = $integrationHelper->getIntegrationObject('FullContact');
        $keys = $myIntegration->getDecryptedApiKeys();

        if ($myIntegration->shouldAutoUpdate()) {

            $fullcontact = new FullContact_Company($keys['apikey']);
            try {
                $webhookId = 'fullcontactcomp#'.$company->getId();
                $parse = parse_url($company->getFieldValue('companywebsite', 'core'));
                if (FALSE === apc_fetch($webhookId.$parse['host'])) {
                    /** @var Router $router */
                    $router = $this->container->get('router');
                    $fullcontact->setWebhookUrl(
                        $router->generate(
                            'mautic_plugin_fullcontact_compindex',
                            [],
                            UrlGeneratorInterface::ABSOLUTE_URL
                        ),
                        $webhookId
                    );
                    $res = $fullcontact->lookupByDomain($parse['host']);
                    apc_add($webhookId.$parse['host'], $res);
                }
            } catch (\Exception $ex) {
                $logger->log('error', 'Error while using FullContact: '.$ex->getMessage());
            }
        }
    }
}
