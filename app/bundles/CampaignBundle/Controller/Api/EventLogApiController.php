<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\Controller\Api;

use Mautic\ApiBundle\Controller\CommonApiController;
use Mautic\ApiBundle\Serializer\Exclusion\FieldInclusionStrategy;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\LeadBundle\Controller\LeadAccessTrait;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterControllerEvent;

/**
 * Class EventLogApiController.
 */
class EventLogApiController extends CommonApiController
{
    use LeadAccessTrait;

    /**
     * @var Campaign
     */
    protected $campaign;

    /**
     * @var Lead
     */
    protected $contact;

    public function initialize(FilterControllerEvent $event)
    {
        $this->model                    = $this->getModel('campaign.event_log');
        $this->entityClass              = 'Mautic\CampaignBundle\Entity\LeadEventLog';
        $this->entityNameOne            = 'event';
        $this->entityNameMulti          = 'events';
        $this->parentChildrenLevelDepth = 1;
        $this->serializerGroups         = [
            'campaignEventLogDetails',
            'campaignEventDetails',
            'campaignList',
        ];

        // Only include the id of the parent
        $this->addExclusionStrategy(new FieldInclusionStrategy(['id'], 1, 'parent'));

        parent::initialize($event);
    }

    /**
     * Get a list of events.
     *
     * @param      $contactId
     * @param null $campaignId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function getContactEventsAction($contactId, $campaignId = null)
    {
        // Ensure contact exists and user has access
        $contact = $this->checkLeadAccess($contactId, 'view');
        if ($contact instanceof Response) {
            return $contact;
        }

        // Ensure campaign exists and user has access
        $campaign = $this->getModel('campaign')->getEntity($campaignId);
        if (null == $campaign || !$campaign->getId()) {
            return $this->notFound();
        }
        if (!$this->checkEntityAccess($campaign, 'view')) {
            return $this->accessDenied();
        }

        if (!empty($contactId) && !empty($campaignId)) {
            $this->serializerGroups = [
                'campaignLeadList',
                'campaignEventWithLogsList',
                'campaignEventLogDetails',
                'campaignList',
                'ipAddressList',
            ];
        }
        $this->campaign                  = $campaign;
        $this->contact                   = $contact;
        $this->extraGetEntitiesArguments = [
            'contact_id'  => $contactId,
            'campaign_id' => $campaignId,
        ];

        return $this->getEntitiesAction();
    }

    /**
     * @param $id
     * @param $contactId
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function editContactEventAction($id, $contactId)
    {
    }

    /**
     * @param null  $data
     * @param null  $statusCode
     * @param array $headers
     *
     * @return \FOS\RestBundle\View\View
     */
    protected function view($data = null, $statusCode = null, array $headers = [])
    {
        if ($this->campaign) {
            $data['campaign'] = $this->campaign;

            if ($this->contact) {
                list($data['membership'], $ignore) = $this->prepareEntitiesForView($this->campaign->getContactMembership($this->contact));
            }
        }

        return parent::view($data, $statusCode, $headers);
    }
}
