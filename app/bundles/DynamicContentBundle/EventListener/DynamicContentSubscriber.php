<?php

namespace Mautic\DynamicContentBundle\EventListener;

use Mautic\AssetBundle\Helper\TokenHelper as AssetTokenHelper;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\DynamicContentBundle\DynamicContentEvents;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Event as Events;
use Mautic\DynamicContentBundle\Helper\DynamicContentHelper;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Mautic\EmailBundle\EventListener\MatchFilterForLeadTrait;
use Mautic\FormBundle\Helper\TokenHelper as FormTokenHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Exception\PrimaryCompanyNotFoundException;
use Mautic\LeadBundle\Helper\TokenHelper;
use Mautic\LeadBundle\Model\CompanyModel;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\PageBundle\Entity\Trackable;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\Helper\TokenHelper as PageTokenHelper;
use Mautic\PageBundle\Model\TrackableModel;
use Mautic\PageBundle\PageEvents;
use MauticPlugin\MauticFocusBundle\Helper\TokenHelper as FocusTokenHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class DynamicContentSubscriber implements EventSubscriberInterface
{
    use MatchFilterForLeadTrait;

    public function __construct(
        private TrackableModel $trackableModel,
        private PageTokenHelper $pageTokenHelper,
        private AssetTokenHelper $assetTokenHelper,
        private FormTokenHelper $formTokenHelper,
        private FocusTokenHelper $focusTokenHelper,
        private AuditLogModel $auditLogModel,
        private DynamicContentHelper $dynamicContentHelper,
        private DynamicContentModel $dynamicContentModel,
        private CorePermissions $security,
        private ContactTracker $contactTracker,
        private CompanyModel $companyModel,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DynamicContentEvents::POST_SAVE         => ['onPostSave', 0],
            DynamicContentEvents::POST_DELETE       => ['onDelete', 0],
            DynamicContentEvents::TOKEN_REPLACEMENT => ['onTokenReplacement', 0],
            PageEvents::PAGE_ON_DISPLAY             => ['decodeTokens', 254],
        ];
    }

    /**
     * Add an entry to the audit log.
     */
    public function onPostSave(Events\DynamicContentEvent $event): void
    {
        $entity = $event->getDynamicContent();
        if ($details = $event->getChanges()) {
            $log = [
                'bundle'   => 'dynamicContent',
                'object'   => 'dynamicContent',
                'objectId' => $entity->getId(),
                'action'   => ($event->isNew()) ? 'create' : 'update',
                'details'  => $details,
            ];
            $this->auditLogModel->writeToLog($log);
        }
    }

    /**
     * Add a delete entry to the audit log.
     */
    public function onDelete(Events\DynamicContentEvent $event): void
    {
        $entity = $event->getDynamicContent();
        $log    = [
            'bundle'   => 'dynamicContent',
            'object'   => 'dynamicContent',
            'objectId' => $entity->deletedId,
            'action'   => 'delete',
            'details'  => ['name' => $entity->getName()],
        ];
        $this->auditLogModel->writeToLog($log);
    }

    public function onTokenReplacement(MauticEvents\TokenReplacementEvent $event): void
    {
        /** @var Lead $lead */
        $lead         = $event->getLead();
        $content      = $event->getContent();
        $clickthrough = $event->getClickthrough();

        if ($lead instanceof Lead && $content) {
            $leadArray = $lead->getProfileFields();
            try {
                $primaryCompany         = $this->companyModel->getCompanyLeadRepository()->getPrimaryCompanyByLeadId($lead->getId());
                $leadArray['companies'] = [$primaryCompany];
            } catch (PrimaryCompanyNotFoundException) {
            }
            $tokens = array_merge(
                TokenHelper::findLeadTokens($content, $leadArray),
                $this->pageTokenHelper->findPageTokens($content, $clickthrough),
                $this->assetTokenHelper->findAssetTokens($content, $clickthrough),
                $this->formTokenHelper->findFormTokens($content),
                $this->focusTokenHelper->findFocusTokens($content)
            );

            [$content, $trackables] = $this->trackableModel->parseContentForTrackables(
                $content,
                $tokens,
                'dynamicContent',
                $clickthrough['dynamic_content_id']
            );

            $dwc     =  $this->dynamicContentModel->getEntity($clickthrough['dynamic_content_id']);
            $utmTags = [];
            if ($dwc && $dwc instanceof DynamicContent) {
                $utmTags = $dwc->getUtmTags();
            }

            /**
             * @var string    $token
             * @var Trackable $trackable
             */
            foreach ($trackables as $token => $trackable) {
                $tokens[$token] = $this->trackableModel->generateTrackableUrl($trackable, $clickthrough, false, $utmTags);
            }

            $content = str_replace(array_keys($tokens), array_values($tokens), $content);

            $event->setContent($content);
        }
    }

    public function decodeTokens(PageDisplayEvent $event): void
    {
        if (!$lead = $event->getLead()) {
            $lead = $this->security->isAnonymous() ? $this->contactTracker->getContact() : null;
        }

        if (!$lead) {
            return;
        }

        $content = $event->getContent();
        if (empty($content)) {
            return;
        }

        $tokens = $this->dynamicContentHelper->findDwcTokens($content, $lead);

        // replace slots
        $dom = new \DOMDocument('1.0', 'utf-8');
        $dom->loadHTML(mb_encode_numericentity($content, [0x80, 0x10FFFF, 0, 0xFFFFF], 'UTF-8'), LIBXML_NOERROR);
        $xpath = new \DOMXPath($dom);

        $contentSlots = $xpath->query('//*[@data-slot="dwc"]');

        for ($i = 0; $i < $contentSlots->length; ++$i) {
            $slot = $contentSlots->item($i);
            if (!$slotName = $slot->getAttribute('data-param-slot-name')) {
                continue;
            }

            if (!$slotContent = $this->dynamicContentHelper->getDynamicContentForLead($slotName, $lead)) {
                continue;
            }

            $newnode = $dom->createDocumentFragment();
            $newnode->appendXML('<![CDATA['.mb_encode_numericentity($slotContent, [0x80, 0x10FFFF, 0, 0xFFFFF], 'UTF-8').']]>');
            $slot->parentNode->replaceChild($newnode, $slot);
        }

        $content = $dom->saveHTML();

        // These tokens need to be replaced after the content, because otherwise the replaced tokens will have encoded
        // HTML entities, which do not conform the tests.
        $result = [];
        foreach ($tokens as $token => $dwc) {
            $result[$token] = $dwc['content'];
        }
        $content = str_replace(array_keys($result), array_values($result), $content);

        $event->setContent($content);
    }
}
