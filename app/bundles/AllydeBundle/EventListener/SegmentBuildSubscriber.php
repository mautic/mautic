<?php

namespace Mautic\AllydeBundle\EventListener;

use Mautic\AllydeBundle\Decorator\AffectedListsTrait;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailOpenEvent;
use Mautic\LeadBundle\Entity\DoNotContact;
use Mautic\LeadBundle\Event\CategoryChangeEvent;
use Mautic\LeadBundle\Event\CompanyEvent;
use Mautic\LeadBundle\Event\LeadChangeCompanyEvent;
use Mautic\LeadBundle\Event\LeadDeviceEvent;
use Mautic\LeadBundle\Event\LeadEvent;
use Mautic\LeadBundle\Event\ListChangeEvent;
use Mautic\LeadBundle\LeadEvents;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Event\PageHitEvent;
use Mautic\PageBundle\PageEvents;
use MauticPlugin\MauticCitrixBundle\CitrixEvents;
use MauticPlugin\MauticCitrixBundle\Event\CitrixEventUpdateEvent;
use MauticPlugin\MauticSocialBundle\Event\SocialMonitorEvent;
use MauticPlugin\MauticSocialBundle\SocialEvents;

/**
 * Class SegmentBuildSubscriber.
 */
class SegmentBuildSubscriber extends CommonSubscriber
{
    use AffectedListsTrait;
    use JobSubscriberTrait;

    /**
     * @var LeadModel
     */
    protected $model;

    /**
     * @var CoreParametersHelper
     */
    protected $coreParametersHelper;

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            LeadEvents::LEAD_POST_SAVE           => ['onLeadSave', 0],
            LeadEvents::COMPANY_POST_SAVE        => ['onCompanySave', 0],
            LeadEvents::LEAD_COMPANY_CHANGE      => ['onCompanyLeadChange', 0],
            LeadEvents::LEAD_LIST_CHANGE         => ['onListChange', 0],
            LeadEvents::LEAD_CATEGORY_CHANGE     => ['onCategoryChange', 0],
            SocialEvents::MONITOR_POST_PROCESS   => ['onMonitorProcess', 0],
            EmailEvents::EMAIL_ON_OPEN           => ['onEmailOpened', 0],
            PageEvents::PAGE_ON_HIT              => ['onPageHit', 0],
            CitrixEvents::ON_CITRIX_EVENT_UPDATE => ['onCitrixEvent', 0],
            LeadEvents::DEVICE_POST_SAVE         => ['onDeviceSave', 0],
        ];
    }

    /**
     * SegmentBuildSubscriber constructor.
     *
     * @param LeadModel            $leadModel
     * @param CoreParametersHelper $coreParametersHelper
     */
    public function __construct(LeadModel $leadModel, CoreParametersHelper $coreParametersHelper)
    {
        $this->model                = $leadModel;
        $this->coreParametersHelper = $coreParametersHelper;
    }

    /**
     * Rebuild lead's lists based on updated data.
     *
     * @param LeadEvent $event
     */
    public function onLeadSave(LeadEvent $event)
    {
        if (defined('ALLYDE_IGNORE_LEAD_LIST_BUILDING') || defined('MAUTIC_REBUILDING_LEAD_LISTS') || defined('MASS_LEADS_MANIPULATION')) {
            return;
        }

        if ($event->getLead()->isAnonymous()) {
            // @todo - allow anonymous list rebuilding at some point maybe
            return;
        }

        if (!$changes = $event->getChanges()) {
            return;
        }

        $affectedFields = $this->getCustomFieldChanges($changes);

        if (isset($changes['owner'])) {
            $affectedFields['owner'] = [
                'oldOwnerId' => (int) $changes['owner'][0],
                'newOwnerId' => (int) $changes['owner'][1],
            ];
        }

        if (isset($changes['dateIdentified'])) {
            $affectedFields['date_identified'] = true;
        }

        if (isset($changes['dateLastActive'])) {
            $affectedFields['last_active'] = true;
        }

        if ($event->isNew()) {
            // Allow date_added for new leads
            $affectedFields['date_added'] = true;
        }

        if (isset($changes['tags'])) {
            // Get all available tags
            $changedTags = [];
            foreach ($changes['tags'] as $tagSet) {
                $changedTags += $tagSet;
            }
            $changedTags = array_unique($changedTags);

            $tags = $this->model->getTagRepository()->getTagsByName($changedTags);

            $affectedFields['tags'] = [];
            /** @var \Mautic\LeadBundle\Entity\Tag $tag */
            foreach ($tags as $tag) {
                $affectedFields['tags'][] = $tag->getId();
            }
        }

        if (isset($changes['dnc_channel_status']) && isset($changes['dnc_channel_status']['email'])) {
            switch ($changes['dnc_channel_status']['email']['reason']) {
                case DoNotContact::IS_CONTACTABLE:
                    if ($changes['dnc_channel_status']['email']['old_reason'] == DoNotContact::UNSUBSCRIBED) {
                        $affectedFields['dnc_unsubscribed'] = true;
                    } else {
                        $affectedFields['dnc_bounced'] = true;
                    }
                    break;
                case DoNotContact::BOUNCED:
                    $affectedFields['dnc_bounced'] = true;
                    break;
                case DoNotContact::MANUAL:
                    $affectedFields['dnc_unsubscribed_manually'] = true;
                    break;
                case DoNotContact::UNSUBSCRIBED:
                    $affectedFields['dnc_unsubscribed'] = true;
                    break;
            }
        }

        if (isset($changes['dnc_channel_status']) && isset($changes['dnc_channel_status']['sms'])) {
            switch ($changes['dnc_channel_status']['sms']['reason']) {
                case DoNotContact::IS_CONTACTABLE:
                    if ($changes['dnc_channel_status']['sms']['old_reason'] == DoNotContact::UNSUBSCRIBED) {
                        $affectedFields['dnc_unsubscribed_sms'] = true;
                    } else {
                        $affectedFields['dnc_bounced_sms'] = true;
                    }
                    break;
                case DoNotContact::BOUNCED:
                    $affectedFields['dnc_bounced_sms'] = true;
                    break;
                case DoNotContact::MANUAL:
                    $affectedFields['dnc_unsubscribed_sms_manually'] = true;
                    break;
                case DoNotContact::UNSUBSCRIBED:
                    $affectedFields['dnc_unsubscribed_sms'] = true;
                    break;
            }
        }

        if (isset($changes['points'])) {
            $affectedFields['points'] = true;
        }

        if (isset($changes['stage'])) {
            $affectedFields['stages'] = [];
            if (!empty($changes['stage'][0])) {
                $affectedFields['stages'][] = $changes['stage'][0];
            }
            if (!empty($changes['stage'][1])) {
                $affectedFields['stages'][] = $changes['stage'][1];
            }
        }

        $this->queueListBuildJob($affectedFields, $event->getLead());
    }

    /**
     * Check for changes of company fields to determine if a beanstalk job needs to be injected.
     *
     * @param CompanyEvent $event
     */
    public function onCompanySave(CompanyEvent $event)
    {
        if (!$changes = $event->getChanges()) {
            return;
        }

        $affectedFields = $this->getCustomFieldChanges($changes);

        $this->queueListBuildJob($affectedFields, $event->getLead());
    }

    /**
     * @param LeadChangeCompanyEvent $event
     */
    public function onCompanyLeadChange(LeadChangeCompanyEvent $event)
    {
        $this->queueListBuildJob(['company' => true], $event->getLead());
    }

    /**
     * Rebuild the lead's lists if other lists are affected by the change.
     *
     * @param ListChangeEvent $event
     */
    public function onListChange(ListChangeEvent $event)
    {
        if (defined('ALLYDE_IGNORE_LEAD_LIST_BUILDING') || defined('MAUTIC_REBUILDING_LEAD_LISTS')) {
            return;
        }

        $affectedFields = [
            'leadlist' => $event->getList()->getId(),
        ];

        $this->queueListBuildJob($affectedFields, $event->getLead());
    }

    /**
     * @param CategoryChangeEvent $event
     */
    public function onCategoryChange(CategoryChangeEvent $event)
    {
        $affectedFields = [
            'globalcategory' => $event->getCategory()->getId(),
        ];

        $this->queueListBuildJob($affectedFields, $event->getLead());
    }

    /**
     * @param SocialMonitorEvent $event
     */
    public function onMonitorProcess(SocialMonitorEvent $event)
    {
        if ($totalLeads = $event->getTotalLeadCount()) {
            $handleField = $this->coreParametersHelper->getParameter('twitter_handle_field', $event->getIntegrationName());

            $affectedFields = [
                $handleField => true,
                'firstname'  => true,
                'lastname'   => true,
                'country'    => true,
            ];

            $manipulatedLeads = $event->getLeadIds();
            $leadId           = null;
            if ($totalLeads === 1) {
                // Just do a single job to rebuild this lead's list
                reset($manipulatedLeads);
                $leadId = key($manipulatedLeads);
            }

            $this->queueListBuildJob($affectedFields, $leadId);
        }
    }

    /**
     * @param EmailOpenEvent $event
     */
    public function onEmailOpened(EmailOpenEvent $event)
    {
        $stat = $event->getStat();

        if ($stat && $lead = $stat->getLead()) {
            if ($email = $event->getEmail()) {
                $affectedFields = [
                    'email_opened' => $email->getId(),
                ];

                $this->queueListBuildJob($affectedFields, $lead);
            }
        }
    }

    /**
     * @param PageHitEvent $event
     */
    public function onPageHit(PageHitEvent $event)
    {
        $lead = $event->getHit()->getLead();

        if ($lead->isAnonymous()) {
            // Ignore this one for anonymous leads or else we'll be in trouble

            return;
        }

        $affectedFields = [
            'url_hit' => $event->getHit()->getUrl(),
        ];

        $this->queueListBuildJob($affectedFields, $lead);
    }

    /**
     * @param CitrixEventUpdateEvent $event
     */
    public function onCitrixEvent(CitrixEventUpdateEvent $event)
    {
        $lead = $event->getLead();

        $affectedFields = [
            'citrix' => [$event->getProduct(), $event->getEventType(), $event->getEventName()],
        ];

        $this->queueListBuildJob($affectedFields, $lead);
    }

    /**
     * @param LeadDeviceEvent $event
     */
    public function onDeviceSave(LeadDeviceEvent $event)
    {
        if ($event->isNew()) {
            $device = $event->getDevice();
            $lead   = $device->getLead();

            if ($lead->isAnonymous()) {
                // Ignore visitors
                return;
            }

            $affectedFields = [];
            if ($brand = $device->getDeviceBrand()) {
                $affectedFields['device_brand'] = $brand;
            }

            if ($model = $device->getDeviceModel()) {
                $affectedFields['device_model'] = $model;
            }

            if ($os = $device->getDeviceOs()) {
                $affectedFields['device_os'] = $os;
            }

            if ($device = $device->getDevice()) {
                $affectedFields['device_type'] = $device;
            }

            $this->queueListBuildJob($affectedFields, $lead);
        }
    }

    /**
     * @param $changes
     *
     * @return array
     */
    protected function getCustomFieldChanges($changes)
    {
        $affectedFields = [];
        if (isset($changes['fields'])) {
            foreach ($changes['fields'] as $fieldName => $fieldChange) {
                if (($fieldChange[0] === null || $fieldChange[0] === '') && ($fieldChange[1] === null || $fieldChange[1] === '')) {
                    // null to '' or '' to null so don't bother
                } else {
                    $affectedFields[$fieldName] = true;
                }
            }
        }

        return $affectedFields;
    }
}
