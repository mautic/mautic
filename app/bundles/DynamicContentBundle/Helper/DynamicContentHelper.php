<?php

namespace Mautic\DynamicContentBundle\Helper;

use Mautic\CampaignBundle\Executioner\RealTimeExecutioner;
use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\DynamicContentBundle\DynamicContentEvents;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Event\ContactFiltersEvaluateEvent;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\EmailBundle\EventListener\MatchFilterForLeadTrait;
use Mautic\LeadBundle\Entity\CompanyLeadRepository;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Entity\Tag;
use Mautic\LeadBundle\Entity\TagRepository;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\PageBundle\Event\PageDisplayEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class DynamicContentHelper
{
    use MatchFilterForLeadTrait;

    /**
     * @const DYNAMIC_CONTENT_REGEX
     */
    public const DYNAMIC_CONTENT_REGEX = '/{(dynamiccontent)=(\w+)(?:\/}|}(?:([^{]*(?:{(?!\/\1})[^{]*)*){\/\1})?)/is';

    /**
     * @const DYNAMIC_WEB_CONTENT_REGEX
     */
    public const DYNAMIC_WEB_CONTENT_REGEX = '/{dwc=(.*?)}/';

    public function __construct(
        protected DynamicContentModel $dynamicContentModel,
        protected RealTimeExecutioner $realTimeExecutioner,
        protected EventDispatcherInterface $dispatcher,
        protected LeadModel $leadModel,
        private CompanyLeadRepository $companyLeadRepository,
        private TagRepository $tagRepository,
    ) {
    }

    /**
     * @param string     $slot
     * @param Lead|array $lead
     *
     * @return string
     */
    public function getDynamicContentForLead($slot, $lead, ?PageDisplayEvent $event = null)
    {
        // Attempt campaign slots first
        $this->realTimeExecutioner->execute(
            'dwc.decision',
            $slot,
            'dynamicContent'
        )->getActionResponses('dwc.push_content');

        // Attempt stored content second
        $data = $this->dynamicContentModel->getSlotContentForLead($slot, $lead);
        if (!empty($data)) {
            $content = $data['content'];
            $dwc     = $this->dynamicContentModel->getEntity($data['id']);
            if ($dwc instanceof DynamicContent) {
                $content = $this->getRealDynamicContent($lead, $dwc, $event);
            }

            return $content;
        }

        // Finally attempt standalone DWC
        return $this->getDynamicContentSlotForLead($slot, $lead, $event);
    }

    /**
     * @param string     $slotName
     * @param Lead|array $lead
     *
     * @return string
     */
    public function getDynamicContentSlotForLead($slotName, $lead, ?PageDisplayEvent $event = null)
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
                return $lead ? $this->getRealDynamicContent($lead, $dwc, $event) : '';
            }
        }

        return '';
    }

    public function findDwcTokens(string $content): array
    {
        preg_match_all('/{dwc=(.*?)}/', $content, $matches);

        $tokens = [];
        if (!empty($matches[1])) {
            foreach ($matches[1] as $key => $slotName) {
                $token = $matches[0][$key];
                if (!empty($tokens[$token])) {
                    continue;
                }

                $dwcs = $this->getDwcsBySlotName($slotName, true);

                /** @var DynamicContent $dwc */
                foreach ($dwcs as $dwc) {
                    if ($dwc->getIsCampaignBased()) {
                        continue;
                    }

                    $tokens[$token][] = $dwc;
                }
            }

            unset($matches);
        }

        return $tokens;
    }

    /**
     * @param Lead|mixed[] $lead
     *
     * @return string
     */
    public function getRealDynamicContent(
        $lead,
        DynamicContent $dwc,
        PageDisplayEvent|EmailSendEvent|null $event = null,
    ) {
        $content = $dwc->getContent();
        // Determine a translation based on contact's preferred locale
        /** @var DynamicContent $translation */
        list($ignore, $translation) = $this->dynamicContentModel->getTranslatedEntity($dwc, $lead);
        if ($translation !== $dwc) {
            // Use translated version of content
            $dwc     = $translation;
            $content = $dwc->getContent();
        }
        if (is_null($event) || !$event->getIsPreview()) {
            $this->dynamicContentModel->createStatEntry($dwc, $lead, $event);
        }

        $slot       = $dwc->getSlotName();
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
                'orderBy'          => 'e.displayOrder',
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
            if ($event->isEvaluated()) {
                return $event->isMatch();
            }
        }

        return $this->matchFilterForLead($filters, $contactArray);
    }

    /**
     * @param Lead|array<mixed> $lead
     *
     * @return array<mixed>
     */
    public function getDwcTokensWithContent(
        string $content,
        array|Lead $lead,
        PageDisplayEvent|EmailSendEvent $event,
    ): array {
        $result = [];
        $tokens = $this->findDwcTokens($content);

        if (!$tokens) {
            return $result;
        }

        if ($lead instanceof Lead) {
            $lead = $lead->getProfileFields();
        }

        $lead = $this->loadLeadPrimaryCompanyIfNeeded($lead, $tokens);
        $lead = $this->loadLeadTagIdsIfNeeded($lead, $tokens);

        foreach ($tokens as $token => $dwcs) {
            $result[$token] = '';
            foreach ($dwcs as $dwc) {
                if ($this->filtersMatchContact($dwc->getFilters(), $lead)) {
                    $result[$token] = $lead ? $this->getRealDynamicContent($lead, $dwc, $event) : '';
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * @param array<mixed> $lead
     */
    public function replaceTokensWithPlainText(string $getSubject, array|Lead $lead, EmailSendEvent $event): string
    {
        $event->setIsSubject(true);
        $tokens = $this->getDwcTokensWithContent($getSubject, $lead, $event);
        foreach ($tokens as $token => $content) {
            $plainText  = strip_tags($content);
            $getSubject = str_replace($token, $plainText, $getSubject);
        }
        $event->setIsSubject(false);

        return $getSubject;
    }

    /**
     * @param mixed[] $lead
     * @param mixed[] $tokens
     *
     * @return mixed[]
     */
    private function loadLeadPrimaryCompanyIfNeeded(array $lead, array $tokens): array
    {
        if (isset($lead['companies']) || !$this->doFiltersContainCompanyFilter($this->flattenTokenFilters($tokens))) {
            return $lead;
        }

        $lead['companies'] = array_values($this->companyLeadRepository->getPrimaryCompaniesByLeadIds([$lead['id']]));

        return $lead;
    }

    /**
     * @param mixed[] $lead
     * @param mixed[] $tokens
     *
     * @return mixed[]
     */
    private function loadLeadTagIdsIfNeeded(array $lead, array $tokens): array
    {
        if (isset($lead['tags']) || !$this->doFiltersContainTagsFilter($this->flattenTokenFilters($tokens))) {
            return $lead;
        }

        $lead['tags'] = $this->tagRepository->getTagIdsByLeadId($lead['id']);

        return $lead;
    }

    /**
     * @param mixed[] $tokens
     *
     * @return iterable<mixed[]>
     */
    private function flattenTokenFilters(array $tokens): iterable
    {
        foreach ($tokens as $dwcs) {
            foreach ($dwcs as $dwc) {
                \assert($dwc instanceof DynamicContent);
                foreach ($dwc->getFilters() as $filter) {
                    yield $filter;
                }
            }
        }
    }
}
