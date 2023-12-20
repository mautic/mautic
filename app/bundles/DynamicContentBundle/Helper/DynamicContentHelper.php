<?php

namespace Mautic\DynamicContentBundle\Helper;

use Mautic\CampaignBundle\Executioner\RealTimeExecutioner;
use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\DynamicContentBundle\DynamicContentEvents;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Event\ContactFiltersEvaluateEvent;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Mautic\EmailBundle\EventListener\MatchFilterForLeadTrait;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Model\LeadModel;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DynamicContentHelper
{
    use MatchFilterForLeadTrait;

    public function __construct(
        protected DynamicContentModel $dynamicContentModel,
        protected RealTimeExecutioner $realTimeExecutioner,
        protected EventDispatcherInterface $dispatcher,
        protected LeadModel $leadModel
    ) {
    }

    /**
     * @param string     $slot
     * @param Lead|array $lead
     *
     * @return string
     */
    public function getDynamicContentForLead($slot, $lead)
    {
        // Attempt campaign slots first
        $dwcActionResponse = $this->realTimeExecutioner->execute('dwc.decision', $slot, 'dynamicContent')->getActionResponses('dwc.push_content');
        if (!empty($dwcActionResponse)) {
            return array_shift($dwcActionResponse);
        }

        // Attempt stored content second
        $data = $this->dynamicContentModel->getSlotContentForLead($slot, $lead);
        if (!empty($data)) {
            $content = $data['content'];
            $dwc     = $this->dynamicContentModel->getEntity($data['id']);
            if ($dwc instanceof DynamicContent) {
                $content = $this->getRealDynamicContent($slot, $lead, $dwc);
            }

            return $content;
        }

        // Finally attempt standalone DWC
        return $this->getDynamicContentSlotForLead($slot, $lead);
    }

    /**
     * @param string     $slotName
     * @param Lead|array $lead
     *
     * @return string
     */
    public function getDynamicContentSlotForLead($slotName, $lead)
    {
        $leadArray = [];
        if ($lead instanceof Lead) {
            $leadArray = $this->convertLeadToArray($lead);
        }

        $dwcs = $this->getDwcsBySlotName($slotName, true);
        /** @var DynamicContent $dwc */
        foreach ($dwcs as $dwc) {
            if ($dwc->getIsCampaignBased()) {
                continue;
            }
            if ($lead && $this->filtersMatchContact($dwc->getFilters(), $leadArray)) {
                return $lead ? $this->getRealDynamicContent($dwc->getSlotName(), $lead, $dwc) : '';
            }
        }

        return '';
    }

    /**
     * @param string     $content
     * @param Lead|array $lead
     *
     * @return string Content with the {content} tokens replaced with dynamic content
     */
    public function replaceTokensInContent($content, $lead)
    {
        // Find all dynamic content tags
        preg_match_all('/{(dynamiccontent)=(\w+)(?:\/}|}(?:([^{]*(?:{(?!\/\1})[^{]*)*){\/\1})?)/is', $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $slot           = $match[2];
            $defaultContent = $match[3];

            $dwcContent = $this->getDynamicContentForLead($slot, $lead);

            if (!$dwcContent) {
                $dwcContent = $defaultContent;
            }

            $content = str_replace($matches[0], $dwcContent, $content);
        }

        return $content;
    }

    /**
     * @param string    $content
     * @param Lead|null $lead
     */
    public function findDwcTokens($content, $lead): array
    {
        preg_match_all('/{dwc=(.*?)}/', $content, $matches);

        $tokens = [];
        if (!empty($matches[1])) {
            foreach ($matches[1] as $key => $slotName) {
                $token = $matches[0][$key];
                if (!empty($tokens[$token])) {
                    continue;
                }

                $dwcs = $this->getDwcsBySlotName($slotName);

                /** @var DynamicContent $dwc */
                foreach ($dwcs as $dwc) {
                    if ($dwc->getIsCampaignBased()) {
                        continue;
                    }
                    $content                   = $lead ? $this->getRealDynamicContent($dwc->getSlotName(), $lead, $dwc) : '';
                    $tokens[$token]['content'] = $content;
                    $tokens[$token]['filters'] = $dwc->getFilters();
                }
            }

            unset($matches);
        }

        return $tokens;
    }

    /**
     * @param string       $slot
     * @param Lead|mixed[] $lead
     *
     * @return string
     */
    public function getRealDynamicContent($slot, $lead, DynamicContent $dwc)
    {
        $content = $dwc->getContent();
        // Determine a translation based on contact's preferred locale
        /** @var DynamicContent $translation */
        [$ignore, $translation] = $this->dynamicContentModel->getTranslatedEntity($dwc, $lead);
        if ($translation !== $dwc) {
            // Use translated version of content
            $dwc     = $translation;
            $content = $dwc->getContent();
        }
        $this->dynamicContentModel->createStatEntry($dwc, $lead, $slot);

        $tokenEvent = new TokenReplacementEvent($content, $lead, ['slot' => $slot, 'dynamic_content_id' => $dwc->getId()]);
        $this->dispatcher->dispatch($tokenEvent, DynamicContentEvents::TOKEN_REPLACEMENT);

        return $tokenEvent->getContent();
    }

    /**
     * @param string $slotName
     * @param bool   $publishedOnly
     *
     * @return array|\Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function getDwcsBySlotName($slotName, $publishedOnly = false)
    {
        $filter = [
            'where' => [
                [
                    'col'  => 'e.slotName',
                    'expr' => 'eq',
                    'val'  => $slotName,
                ],
            ],
        ];

        if ($publishedOnly) {
            $filter['where'][] = [
                'col'  => 'e.isPublished',
                'expr' => 'eq',
                'val'  => 1,
            ];
        }

        return $this->dynamicContentModel->getEntities(
            [
                'filter'           => $filter,
                'ignore_paginator' => true,
            ]
        );
    }

    /**
     * @param Lead $lead
     */
    public function convertLeadToArray($lead): array
    {
        return array_merge(
            $lead->getProfileFields(),
            [
                'tags' => array_map(
                    fn (Tag $v) => $v->getId(),
                    $lead->getTags()->toArray()
                ),
            ]
        );
    }

    /**
     * @param mixed[] $filters
     * @param mixed[] $contactArray
     */
    private function filtersMatchContact(array $filters, array $contactArray): bool
    {
        if (empty($contactArray['id'])) {
            return false;
        }

        //  We attempt even listeners first
        if ($this->dispatcher->hasListeners(DynamicContentEvents::ON_CONTACTS_FILTER_EVALUATE)) {
            /** @var Lead $contact */
            $contact = $this->leadModel->getEntity($contactArray['id']);

            $event = new ContactFiltersEvaluateEvent($filters, $contact);
            $this->dispatcher->dispatch($event, DynamicContentEvents::ON_CONTACTS_FILTER_EVALUATE);
            if ($event->isMatch()) {
                return true;
            }
        }

        return $this->matchFilterForLead($filters, $contactArray);
    }
}
