<?php

namespace Mautic\DynamicContentBundle\EventListener;

use Mautic\AssetBundle\Helper\TokenHelper as AssetTokenHelper;
use Mautic\CoreBundle\Event as MauticEvents;
use Mautic\CoreBundle\Model\AuditLogModel;
use Mautic\CoreBundle\Security\Permissions\CorePermissions;
use Mautic\DynamicContentBundle\DynamicContentEvents;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Entity\DynamicContentRepository;
use Mautic\DynamicContentBundle\Event as Events;
use Mautic\DynamicContentBundle\Helper\DynamicContentHelper;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\FormBundle\Helper\TokenHelper as FormTokenHelper;
use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Exception\PrimaryCompanyNotFoundException;
use Mautic\LeadBundle\Helper\TokenHelper;
use Mautic\LeadBundle\Tracker\ContactTracker;
use Mautic\PageBundle\Entity\Trackable;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Mautic\PageBundle\Helper\TokenHelper as PageTokenHelper;
use Mautic\PageBundle\Model\TrackableModel;
use Mautic\PageBundle\PageEvents;
use MauticPlugin\MauticFocusBundle\Helper\TokenHelper as FocusTokenHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

final readonly class DynamicContentSubscriber implements EventSubscriberInterface
{
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
        private CompanyLeadRepository $companyLeadRepository,
        private DynamicContentRepository $dynamicContentRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            DynamicContentEvents::PRE_SAVE          => ['setDisplayOrder', 0],
            DynamicContentEvents::POST_SAVE         => ['onPostSave', 0],
            DynamicContentEvents::POST_DELETE       => ['onDelete', 0],
            DynamicContentEvents::TOKEN_REPLACEMENT => ['onTokenReplacement', 0],
            PageEvents::PAGE_ON_DISPLAY             => ['decodeTokens', 254],
            EmailEvents::EMAIL_ON_SEND              => ['onEmailGenerate', 255],
            EmailEvents::EMAIL_ON_DISPLAY           => ['onEmailDisplay', 255],
        ];
    }

    public function setDisplayOrder(Events\DynamicContentEvent $event): void
    {
        $dynamicContent         = $event->getDynamicContent();
        $changes                = $dynamicContent->getChanges();
        $isSlotNameChanged      = isset($changes['slotName'][0]) && $changes['slotName'][0] !== $dynamicContent->getSlotName();
        $isCampaignBasedChanged = isset($changes['isCampaignBased'][0]) && $changes['isCampaignBased'][0] != $dynamicContent->getIsCampaignBased();

        if (($dynamicContent->getIsCampaignBased() && !$isCampaignBasedChanged) || (!isset($changes['displayOrder']) && !$isSlotNameChanged)) {
            return;
        }

        $lastOrder    = $this->dynamicContentRepository->getLastDisplayOrder($dynamicContent->getSlotName()) + 1;

        // reorder dwc if non campaign based dwc converted to campaign based
        if ($dynamicContent->getIsCampaignBased() && $isCampaignBasedChanged) {
            $slotName     = $dynamicContent->getSlotName();
            $currentOrder = $changes['displayOrder'][0];
            if ($currentOrder < $lastOrder) {
                $this->dynamicContentRepository->reorderDwc($currentOrder, $lastOrder, $slotName);
            }
            $dynamicContent->setDisplayOrder();

            return;
        }

        if ($isSlotNameChanged) {
            $prevSlotName      = $changes['slotName'][0];
            $prevOrder         = $changes['displayOrder'][0] ?? $dynamicContent->getDisplayOrder();
            $prevCampaignBased = $changes['isCampaignBased'][0];
        }

        if ($dynamicContent->isNew() || $isSlotNameChanged) {
            $newOrder = $dynamicContent->getDisplayOrder() + 1;
            if ($lastOrder !== $newOrder) {
                $this->dynamicContentRepository->reorderDwc($lastOrder, $newOrder, $dynamicContent->getSlotName());
            }
            $dynamicContent->setDisplayOrder($newOrder);
        } else {
            $previousOrder = $changes['displayOrder'][0] ?? $lastOrder;
            if ($previousOrder !== $dynamicContent->getDisplayOrder()) {
                $newOrder = $dynamicContent->getDisplayOrder() + 1;
                $this->dynamicContentRepository->reorderDwc($previousOrder, $newOrder, $dynamicContent->getSlotName());
                if ($previousOrder > $dynamicContent->getDisplayOrder()) {
                    $dynamicContent->setDisplayOrder($newOrder);
                }
            }
        }

        if (!empty($prevSlotName) && $isSlotNameChanged && !$prevCampaignBased
            && $prevOrder < $lastOrder = $this->dynamicContentRepository->getLastDisplayOrder($prevSlotName)) {
            $this->dynamicContentRepository->reorderDwc($prevOrder, $lastOrder + 1, $prevSlotName);
        }
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

        // Reordering other dwc after deletion.
        $slotName     = $entity->getSlotName();
        $currentOrder = $entity->getDisplayOrder();
        if (!$entity->getIsCampaignBased()
            && $currentOrder < ($lastOrder = $this->dynamicContentRepository->getLastDisplayOrder($slotName))) {
            $this->dynamicContentRepository->reorderDwc($currentOrder, ++$lastOrder, $slotName);
        }

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
        /** @var Lead|array<mixed> $lead */
        $lead         = $event->getLead();
        $content      = $event->getContent();
        $clickthrough = $event->getClickthrough();

        if ($content) {
            $leadArray      = $lead instanceof Lead ? $lead->getProfileFields() : $lead;
            try {
                $primaryCompany         = $this->companyLeadRepository->getPrimaryCompanyByLeadId($leadArray['id']);
                $leadArray['companies'] = [$primaryCompany];
            } catch (PrimaryCompanyNotFoundException) {
            }
            $tokens = array_merge(
                TokenHelper::findLeadTokens($content, $leadArray),
                $this->pageTokenHelper->findPageTokens($content),
                $this->assetTokenHelper->findAssetTokens($content),
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

            if ($dwc instanceof DynamicContent) {
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
        $content = $event->getContent();
        if (empty($content)) {
            return;
        }

        if (!$lead = $event->getLead()) {
            $lead = $this->security->isAnonymous() ? $this->contactTracker->getContact() : null;
        }

        // Step 1: Replace {dwc=...} tokens directly with their content
        $dwcTokens = $this->dynamicContentHelper->findDwcTokens($content, $lead, $event);
        if ([] !== $dwcTokens) {
            $content = $this->replaceDwcTokensInContent($content, $dwcTokens);
        }

        if (!$lead) {
            $event->setContent($content);

            return;
        }

        // Step 2: Process HTML data-slot="dwc" elements (for builder-inserted slots)
        // Only use DOM processing if there are data-slot="dwc" elements
        if (!str_contains($content, 'data-slot="dwc"')) {
            $event->setContent($content);

            return;
        }

        $dom = new \DOMDocument('1.0', 'utf-8');
        $dom->loadHTML(mb_encode_numericentity($content, [0x80, 0x10FFFF, 0, 0xFFFFF], 'UTF-8'), LIBXML_NOERROR);
        $xpath = new \DOMXPath($dom);

        $contentSlots = $xpath->query('//*[@data-slot="dwc"]');

        for ($i = 0; $i < $contentSlots->length; ++$i) {
            $slot = $contentSlots->item($i);
            if (!$slot instanceof \DOMElement) {
                continue;
            }
            if (!$slotName = $slot->getAttribute('data-param-slot-name')) {
                continue;
            }

            if (!$slotContent = $this->dynamicContentHelper->getDynamicContentForLead($slotName, $lead, $event)) {
                continue;
            }

            $newnode = $dom->createDocumentFragment();
            $newnode->appendXML('<![CDATA['.mb_encode_numericentity($slotContent, [0x80, 0x10FFFF, 0, 0xFFFFF], 'UTF-8').']]>');
            if ($slot->parentNode instanceof \DOMNode) {
                $slot->parentNode->replaceChild($newnode, $slot);
            }
        }

        $content = $dom->saveHTML();

        $event->setContent($content);
    }

    /**
     * Replace {dwc=...} tokens in content, handling href/src attributes specially.
     *
     * @param array<string, array{content: string, filters: array<mixed>}> $dwcTokens
     */
    private function replaceDwcTokensInContent(string $content, array $dwcTokens): string
    {
        foreach ($dwcTokens as $token => $data) {
            $dwcContent = $data['content'];

            // For href/src: strip HTML tags and decode entities, then escape for attribute context
            $plainContent = strip_tags($dwcContent);
            $plainContent = html_entity_decode($plainContent, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            $escapedContent = htmlspecialchars($plainContent, ENT_QUOTES | ENT_HTML5, 'UTF-8');

            // Replace tokens ANYWHERE inside href/src attribute values with escaped plain text
            // Pattern matches href="...{token}..." or src='...{token}...'
            $content = preg_replace_callback(
                '/((?:href|src)\s*=\s*)(["\'])([^"\']*'.preg_quote($token, '/').'[^"\']*)\2/i',
                fn (array $matches): string => $matches[1].$matches[2].str_replace($token, $escapedContent, $matches[3]).$matches[2],
                $content
            );

            // Replace remaining occurrences with full HTML content
            $content = str_replace($token, $dwcContent, $content);
        }

        return $content;
    }

    public function onEmailDisplay(EmailSendEvent $event): void
    {
        $event->setIsPreview(true);
        $this->onEmailGenerate($event);
    }

    public function onEmailGenerate(EmailSendEvent $event): void
    {
        if ($event->isDynamicContentParsing()) {
            // prevent a loop
            return;
        }

        if (!$lead = $event->getLead()) {
            $lead = $this->security->isAnonymous() ? $this->contactTracker->getContact() : null;
        }

        $content = $event->getContent();

        if (!$lead || empty($content)) {
            return;
        }

        $event->setSubject(
            $this->dynamicContentHelper->replaceTokensWithPlainText($event->getSubject(), $lead, $event)
        );

        // Collect all slot names from both {dwc=...} tokens and HTML data-slot elements
        $slotNamesFromTokens = $this->extractSlotNamesFromDwcTokens($content);
        $slotNamesFromHtml   = $this->dynamicContentHelper->findDwcSlotNameFromContent($content);
        $allSlotNames        = array_unique(array_merge($slotNamesFromTokens, $slotNamesFromHtml));

        $dwcSlotContentForLead = $this->dynamicContentHelper->getDwcTokensWithContent(
            $allSlotNames,
            $lead,
            $event
        );

        // Step 1: Replace {dwc=...} tokens directly
        $index   = 1;
        $content = $this->replaceEmailDwcTokens($content, $dwcSlotContentForLead, $event, $lead, $index);

        // Step 2: Replace HTML data-slot="dwc" elements
        $content = preg_replace_callback(
            '/<([a-z0-9]+)[^>]*data-slot="dwc"[^>]*data-param-slot-name="([^"]+)"[^>]*>.*?<\/\1>/is',
            function (array $matches) use ($dwcSlotContentForLead, &$index, $event, $lead): string {
                $slotName    = $matches[2];
                $token       = '{dwc_'.$slotName.'_'.$index.'}';
                $slotContent = $matches[0];
                if (isset($dwcSlotContentForLead[$slotName])) {
                    $slotContent = '<div>'.$dwcSlotContentForLead[$slotName].'</div>';
                }

                $slotContent = $this->dynamicContentHelper->replaceTokenInsideDWCContent(
                    $slotContent, $event, $lead
                );

                $event->addToken($token, $slotContent);
                ++$index;

                return $token;
            },
            $content
        );

        $event->setContent($content);
    }

    /**
     * Extract slot names from {dwc=...} tokens in content.
     *
     * @return array<string>
     */
    private function extractSlotNamesFromDwcTokens(string $content): array
    {
        $slotNames = [];

        // Match {dwc=slotname}...{/dwc} pattern
        preg_match_all(DynamicContentHelper::DWC_WITH_OPTIONAL_DEFAULT_CONTENT, $content, $matches);
        if (!empty($matches[1])) {
            $slotNames = array_merge($slotNames, $matches[1]);
        }

        // Match simple {dwc=slotname} pattern
        preg_match_all(DynamicContentHelper::DYNAMIC_WEB_CONTENT_REGEX, $content, $matches);
        if (!empty($matches[1])) {
            $slotNames = array_merge($slotNames, $matches[1]);
        }

        return array_unique($slotNames);
    }

    /**
     * Replace {dwc=...} tokens in email content.
     *
     * @param array<string, string> $dwcSlotContentForLead
     * @param Lead|array<mixed>     $lead
     */
    private function replaceEmailDwcTokens(string $content, array $dwcSlotContentForLead, EmailSendEvent $event, Lead|array $lead, int &$index): string
    {
        // First, replace tokens with closing tag: {dwc=slotname}default{/dwc}
        $content = preg_replace_callback(
            DynamicContentHelper::DWC_WITH_OPTIONAL_DEFAULT_CONTENT,
            function (array $matches) use ($dwcSlotContentForLead, &$index, $event, $lead): string {
                $slotName       = $matches[1];
                $defaultContent = $matches[2];
                $token          = '{dwc_'.$slotName.'_'.$index.'}';
                $slotContent    = $dwcSlotContentForLead[$slotName] ?? $defaultContent;

                if (!empty($slotContent)) {
                    $slotContent = $this->dynamicContentHelper->replaceTokenInsideDWCContent(
                        $slotContent, $event, $lead
                    );
                }

                $event->addToken($token, $slotContent);
                ++$index;

                return $token;
            },
            $content
        );

        // Then, replace simple tokens: {dwc=slotname}
        return preg_replace_callback(
            DynamicContentHelper::DYNAMIC_WEB_CONTENT_REGEX,
            function (array $matches) use ($dwcSlotContentForLead, &$index, $event, $lead): string {
                $slotName    = $matches[1];
                $token       = '{dwc_'.$slotName.'_'.$index.'}';
                $slotContent = $dwcSlotContentForLead[$slotName] ?? '';

                if (!empty($slotContent)) {
                    $slotContent = $this->dynamicContentHelper->replaceTokenInsideDWCContent(
                        $slotContent, $event, $lead
                    );
                }

                $event->addToken($token, $slotContent);
                ++$index;

                return $token;
            },
            $content
        );
    }
}
