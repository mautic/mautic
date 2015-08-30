<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DashboardBundle\Controller;

use Mautic\CoreBundle\Controller\CommonController;
use Symfony\Component\Intl\Intl;
use Mautic\CoreBundle\Event\IconEvent;
use Mautic\CoreBundle\CoreEvents;

/**
 * Class DefaultController
 */
class DefaultController extends CommonController
{

    /**
     * Generates the default view
     *
     * @return \Symfony\Component\HttpFoundation\JsonResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        /** @var \Mautic\PageBundle\Entity\HitRepository $hitRepo */
        $hitRepo        = $this->factory->getEntityManager()->getRepository('MauticPageBundle:Hit');
        /** @var \Mautic\EmailBundle\Entity\StatRepository $emailStatRepo */
        $emailStatRepo  = $this->factory->getEntityManager()->getRepository('MauticEmailBundle:Stat');
        /** @var \Mautic\CoreBundle\Entity\IpAddressRepository $ipAddressRepo */
        $ipAddressRepo  = $this->factory->getEntityManager()->getRepository('MauticCoreBundle:IpAddress');
        /** @var \Mautic\EmailBundle\Entity\EmailRepository $emailRepo */
        $emailRepo      = $this->factory->getModel('email')->getRepository();

        $sentReadCount        = $emailRepo->getSentReadCount();
        $clickthroughCount    = $hitRepo->countEmailClickthrough();
        $newReturningVisitors = array(
            'returning' => $hitRepo->countReturningIp(),
            'unique'    => $ipAddressRepo->countIpAddresses()
        );
        $weekVisitors         = $hitRepo->countVisitors(604800);
        $allTimeVisitors      = $hitRepo->countVisitors(0);
        $allSentEmails        = $emailStatRepo->getSentCount();

        /** @var \Mautic\PageBundle\Model\PageModel $pageModel */
        $pageModel            = $this->factory->getModel('page');
        $popularPageEntites   = $pageModel->getRepository()->getPopularPages();

        $popularPages         = array();
        foreach ($popularPageEntites as $page) {
            $popularPages[] = array(
                'id'      => $page->getId(),
                'title'   => $page->getTitle(),
                'hits'    => $page->getHits(),
                'url'     => $pageModel->generateUrl($page)
            );
        }

        $popularAssetEntities = $this->factory->getModel('asset')->getRepository()->getPopularAssets();
        $popularAssets        = array();
        foreach ($popularAssetEntities as $asset) {
            $popularAssets[] = array(
                'id'            => $asset->getId(),
                'title'         => $asset->getTitle(),
                'downloadCount' => $asset->getDownloadCount()
            );
        }

        $popularCampaigns = $this->factory->getModel('campaign')->getRepository()->getPopularCampaigns();

        $returnRate = 0;
        $totalVisits = array_sum($newReturningVisitors);
        if ($totalVisits > 0) {
            $returnRate = round($newReturningVisitors['returning'] / $totalVisits * 100);
        }

        $clickRate = 0;

        if ($sentReadCount['sent_count'] > 0) {
            $clickRate = round($clickthroughCount / $sentReadCount['sent_count'] * 100);
        }

        $countries = array_flip(Intl::getRegionBundle()->getCountryNames());
        $mapData = array();

        /** @var \Mautic\LeadBundle\Entity\LeadRepository $leadRepository */
        $leadRepository = $this->factory->getEntityManager()->getRepository('MauticLeadBundle:Lead');
        $leadCountries  = $leadRepository->getLeadsCountPerCountries();

        // Convert country names to 2-char code
        foreach ($leadCountries as $leadCountry) {
            if (isset($countries[$leadCountry['country']])) {
                $mapData[$countries[$leadCountry['country']]] = $leadCountry['quantity'];
            }
        }

        // Audit Log
        $logs = $this->factory->getModel('core.auditLog')->getLogForObject(null, null);

        // Get names of log's items
        $router = $this->factory->getRouter();
        foreach ($logs as $key => &$log) {
            if (!empty($log['bundle']) && !empty($log['object']) && !empty($log['objectId'])) {
                try {
                    $model = $this->factory->getModel($log['bundle'].'.'.$log['object']);
                    $item  = $model->getEntity($log['objectId']);
                    if (method_exists($item, $model->getNameGetter())) {
                        $log['objectName'] = $item->{$model->getNameGetter()}();

                        if ($log['bundle'] == 'lead' && $log['objectName'] == 'mautic.lead.lead.anonymous') {
                            $log['objectName'] = $this->factory->getTranslator()->trans('mautic.lead.lead.anonymous');
                        }
                    } else {
                        $log['objectName'] = '';
                    }

                    $routeName = 'mautic_'.$log['bundle'].'_action';
                    if ($router->getRouteCollection()->get($routeName) !== null) {
                        $log['route'] = $router->generate(
                            'mautic_'.$log['bundle'].'_action',
                            array('objectAction' => 'view', 'objectId' => $log['objectId'])
                        );
                    } else {
                        $log['route'] = false;
                    }
                } catch (\Exception $e) {
                    unset($logs[$key]);
                }
            }
        }

        $event = new IconEvent($this->factory->getSecurity());
        $this->factory->getDispatcher()->dispatch(CoreEvents::FETCH_ICONS, $event);
        $icons = $event->getIcons();

        // Upcoming emails from Campaign Bundle
        /** @var \Mautic\CampaignBundle\Entity\LeadEventLogRepository $leadEventLogRepository */
        $leadEventLogRepository = $this->factory->getEntityManager()->getRepository('MauticCampaignBundle:LeadEventLog');
        $upcomingEmails = $leadEventLogRepository->getUpcomingEvents(array('type' => 'email.send', 'scheduled' => 1, 'eventType' => 'action'));

        $leadModel = $this->factory->getModel('lead.lead');

        foreach ($upcomingEmails as &$email) {
            $email['lead'] = $leadModel->getEntity($email['lead_id']);
        }

        return $this->delegateView(array(
            'viewParameters'  =>  array(
                'sentReadCount'     => $sentReadCount,
                'clickthroughCount' => $clickthroughCount,
                'returnRate'        => $returnRate,
                'clickRate'         => $clickRate,
                'newReturningVisitors' => $newReturningVisitors,
                'weekVisitors'      => $weekVisitors,
                'allTimeVisitors'   => $allTimeVisitors,
                'popularPages'      => $popularPages,
                'popularAssets'     => $popularAssets,
                'popularCampaigns'  => $popularCampaigns,
                'allSentEmails'     => $allSentEmails,
                'mapData'           => $mapData,
                'logs'              => $logs,
                'icons'             => $icons,
                'upcomingEmails'    => $upcomingEmails,
                'security'          => $this->factory->getSecurity()
            ),
            'contentTemplate' => 'MauticDashboardBundle:Default:index.html.php',
            'passthroughVars' => array(
                'activeLink'     => '#mautic_dashboard_index',
                'mauticContent'  => 'dashboard',
                'route'          => $this->generateUrl('mautic_dashboard_index')
            )
        ));
    }
}
