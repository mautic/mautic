<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\DynamicContentBundle\EventListener;

use DOMDocument;
use DOMXPath;
use Mautic\AssetBundle\Helper\TokenHelper as AssetTokenHelper;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\DynamicContentBundle\DynamicContentEvents;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Event as Events;
use Mautic\DynamicContentBundle\Helper\DynamicContentHelper;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Mautic\EmailBundle\EventListener\MatchFilterForLeadTrait;
use Mautic\FormBundle\Helper\TokenHelper as FormTokenHelper;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Helper\TokenHelper;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Entity\Trackable;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\Helper\TokenHelper as PageTokenHelper;
use Mautic\PageBundle\Model\TrackableModel;
use Mautic\PageBundle\PageEvents;
use MauticPlugin\MauticFocusBundle\Helper\TokenHelper as FocusTokenHelper;

/**
 * Class DynamicContentSubscriber.
 */
class DynamicContentSubscriber extends CommonSubscriber
{
    use MatchFilterForLeadTrait;

    /**
     * @var TrackableModel
     */
    protected $trackableModel;

    /**
     * @var PageTokenHelper
     */
    protected $pageTokenHelper;

    /**
     * @var AssetTokenHelper
     */
    protected $assetTokenHelper;

    /**
     * @var FormTokenHelper
     */
    protected $formTokenHelper;

    /**
     * @var FocusTokenHelper
     */
    protected $focusTokenHelper;

    /**
     * @var AuditLogModel
     */
    protected $auditLogModel;

    /**
     * @var LeadModel
     */
    private $leadModel;

    /**
     * @var DynamicContentHelper
     */
    private $dynamicContentHelper;

    /**
     * @var DynamicContentModel
     */
    private $dynamicContentModel;

    /**
     * DynamicContentSubscriber constructor.
     *
     * @param TrackableModel       $trackableModel
     * @param PageTokenHelper      $pageTokenHelper
     * @param AssetTokenHelper     $assetTokenHelper
     * @param FormTokenHelper      $formTokenHelper
     * @param FocusTokenHelper     $focusTokenHelper
     * @param AuditLogModel        $auditLogModel
     * @param LeadModel            $leadModel
     * @param DynamicContentHelper $dynamicContentHelper
     * @param DynamicContentModel  $dynamicContentModel
     */
    public function __construct(
        TrackableModel $trackableModel,
        PageTokenHelper $pageTokenHelper,
        AssetTokenHelper $assetTokenHelper,
        FormTokenHelper $formTokenHelper,
        FocusTokenHelper $focusTokenHelper,
        AuditLogModel $auditLogModel,
        LeadModel $leadModel,
        DynamicContentHelper $dynamicContentHelper,
        DynamicContentModel $dynamicContentModel
    ) {
        $this->trackableModel       = $trackableModel;
        $this->pageTokenHelper      = $pageTokenHelper;
        $this->assetTokenHelper     = $assetTokenHelper;
        $this->formTokenHelper      = $formTokenHelper;
        $this->focusTokenHelper     = $focusTokenHelper;
        $this->auditLogModel        = $auditLogModel;
        $this->leadModel            = $leadModel;
        $this->dynamicContentHelper = $dynamicContentHelper;
        $this->dynamicContentModel  = $dynamicContentModel;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
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
     *
     * @param Events\DynamicContentEvent $event
     */
    public function onPostSave(Events\DynamicContentEvent $event)
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
     *
     * @param Events\DynamicContentEvent $event
     */
    public function onDelete(Events\DynamicContentEvent $event)
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

    /**
     * @param MauticEvents\TokenReplacementEvent $event
     */
    public function onTokenReplacement(MauticEvents\TokenReplacementEvent $event)
    {
        /** @var Lead $lead */
        $lead         = $event->getLead();
        $content      = $event->getContent();
        $clickthrough = $event->getClickthrough();

        if ($content) {
            $tokens = array_merge(
                TokenHelper::findLeadTokens($content, $lead->getProfileFields()),
                $this->pageTokenHelper->findPageTokens($content, $clickthrough),
                $this->assetTokenHelper->findAssetTokens($content, $clickthrough),
                $this->formTokenHelper->findFormTokens($content),
                $this->focusTokenHelper->findFocusTokens($content)
            );

            list($content, $trackables) = $this->trackableModel->parseContentForTrackables(
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
             * @var string
             * @var Trackable $trackable
             */
            foreach ($trackables as $token => $trackable) {
                $tokens[$token] = $this->trackableModel->generateTrackableUrl($trackable, $clickthrough, false, $utmTags);
            }

            $content = str_replace(array_keys($tokens), array_values($tokens), $content);

            $event->setContent($content);
        }
    }

    /**
     * @param PageDisplayEvent $event
     */
    public function decodeTokens(PageDisplayEvent $event)
    {
        $lead = $this->security->isAnonymous() ? $this->leadModel->getCurrentLead() : null;
        if (!$lead) {
            return;
        }

        $content = $event->getContent();
        if (empty($content)) {
            return;
        }

        $tokens    = $this->dynamicContentHelper->findDwcTokens($content, $lead);
        $leadArray = [];
        if ($lead instanceof Lead) {
            $leadArray = $this->dynamicContentHelper->convertLeadToArray($lead);
        }
        $result = [];
        foreach ($tokens as $token => $dwc) {
            $result[$token] = '';
            if ($this->matchFilterForLead($dwc['filters'], $leadArray)) {
                $result[$token] = $dwc['content'];
            }
        }
        $content = str_replace(array_keys($result), array_values($result), $content);

        // replace slots
        $dom = new DOMDocument('1.0', 'utf-8');
        $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOERROR);
        $xpath = new DOMXPath($dom);

        $divContent = $xpath->query('//*[@data-slot="dwc"]');
        for ($i = 0; $i < $divContent->length; ++$i) {
            $slot = $divContent->item($i);
            if (!$slotName = $slot->getAttribute('data-param-slot-name')) {
                continue;
            }

            if (!$slotContent = $this->dynamicContentHelper->getDynamicContentForLead($slotName, $lead)) {
                continue;
            }

            $newnode = $dom->createDocumentFragment();
            $newnode->appendXML('<![CDATA['.mb_convert_encoding($slotContent, 'HTML-ENTITIES', 'UTF-8').']]>');
            $slot->parentNode->replaceChild($newnode, $slot);
        }

        $content = $dom->saveHTML();

        $event->setContent($content);
    }
}
