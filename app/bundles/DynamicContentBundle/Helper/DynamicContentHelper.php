<?php

namespace Mautic\DynamicContentBundle\Helper;

use Mautic\CampaignBundle\Executioner\RealTimeExecutioner;
use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\DynamicContentBundle\DynamicContentEvents;
use Mautic\DynamicContentBundle\Entity\DynamicContent;
use Mautic\DynamicContentBundle\Event\ContactFiltersEvaluateEvent;
use Mautic\DynamicContentBundle\Model\DynamicContentModel;
use Mautic\EmailBundle\EmailEvents;
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

    public const DYNAMIC_WEB_CONTENT_REGEX = '/{dwc=(.*?)}/';

    public const DWC_WITH_OPTIONAL_DEFAULT_CONTENT = '/\{dwc=([^\{\}=]+)\}(.*?)\{\/dwc\}/s';

    public function __construct(
        protected DynamicContentModel $dynamicContentModel,
        protected RealTimeExecutioner $realTimeExecutioner,
        protected EventDispatcherInterface $dispatcher,
        protected LeadModel $leadModel,
        private LeadListRepository $segmentRepository,
        private CompanyLeadRepository $companyLeadRepository,
        private TagRepository $tagRepository,
    ) {
    }

    /**
     * @return string
     */
    public function getDynamicContentForLead(string $slot, Lead|array|null $lead, ?PageDisplayEvent $event = null)
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
    public function getDynamicContentSlotForLead(array|string $slotName, $lead, ?PageDisplayEvent $event = null)
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
            // enrich lead array with company data if this DWC has company filters
            $leadWithCompanies = $this->loadLeadPrimaryCompanyIfNeeded($leadArray, [$dwc->getSlotName() => [$dwc]]);
            if ($lead && $this->filtersMatchContact($dwc->getFilters(), $leadWithCompanies)) {
                return $this->getRealDynamicContent($lead, $dwc, $event);
            }
        }

        return '';
    }

    /**
     * @param array<string> $slotNames
     *
     * @return array<string, DynamicContent[]>
     */
    public function findDwcVariantsBySlotNames(array $slotNames): array
    {
        $dwcListBySlotName = [];
        $dwcs              = $this->getDwcsBySlotName($slotNames, true);
        if (!empty($dwcs)) {
            foreach ($dwcs as $dwc) {
                /** @var DynamicContent $dwc */
                $dwcListBySlotName[$dwc->getSlotName()][] = $dwc;
            }
        }

        return $dwcListBySlotName;
    }

    /**
     * @param Lead|mixed[] $lead
     *
     * @return string
     */
    public function getRealDynamicContent($slot, Lead|array|null $lead, DynamicContent $dwc)
    {
        $content = $dwc->getContent() ?? '';
        // Determine a translation based on contact's preferred locale
        /** @var DynamicContent $translation */
        [$ignore, $translation] = $this->dynamicContentModel->getTranslatedEntity($dwc, $lead);
        if ($translation !== $dwc) {
            // Use translated version of content
            $dwc     = $translation;
            $content = $dwc->getContent();
        }
        if (is_null($event) || !$event->getIsPreview()) {
            $this->dynamicContentModel->createStatEntry($dwc, $lead, $event);
        }

        if ($event instanceof EmailSendEvent) {
            return $content;
        }

        $slot       = $dwc->getSlotName();
        $tokenEvent = new TokenReplacementEvent($content, $lead, ['slot' => $slot, 'dynamic_content_id' => $dwc->getId()]);
        $this->dispatcher->dispatch($tokenEvent, DynamicContentEvents::TOKEN_REPLACEMENT);

        return $tokenEvent->getContent();
    }

    /**
     * @param array<string>|string $slotNames
     *
     * @return array|\Doctrine\ORM\Tools\Pagination\Paginator
     */
    public function getDwcsBySlotName(array|string $slotNames, bool $publishedOnly = false)
    {
        $filter = [
            'where' => [
                [
                    'col'  => 'e.slotName',
                    'expr' => is_array($slotNames) ? 'in' : 'eq',
                    'val'  => $slotNames,
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
                'id'   => $lead->getId(),
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
     * @param array<string>     $slotNames
     *
     * @return array<mixed>
     */
    public function getDwcTokensWithContent(
        array $slotNames,
        array|Lead $lead,
        EmailSendEvent $event,
    ): array {
        $result            = [];
        $dwcListBySlotName = $this->findDwcVariantsBySlotNames($slotNames);

        if ($dwcListBySlotName === []) {
            return $result;
        }

        if ($lead instanceof Lead) {
            $lead = $lead->getProfileFields();
        }

        $lead = $this->loadLeadPrimaryCompanyIfNeeded($lead, $dwcListBySlotName);
        $lead = $this->loadLeadTagIdsIfNeeded($lead, $dwcListBySlotName);

        $result = [];
        foreach ($dwcListBySlotName as $slotName => $tokenData) {
            foreach ($tokenData as $dwc) {
                if ($this->filtersMatchContact($dwc->getFilters(), $lead)) {
                    $result[$slotName] = $lead ? $this->getRealDynamicContent($lead, $dwc, $event) : '';
                    break;
                }
            }
        }

        return $result;
    }

    /**
     * @param array<mixed> $lead
     */
    public function replaceTokensWithPlainText(string $subject, array|Lead $lead, EmailSendEvent $event): string
    {
        $event->setIsSubject(true);
        preg_match_all(self::DWC_WITH_OPTIONAL_DEFAULT_CONTENT, $subject, $matches);
        if (!empty($matches[1])) {
            $tokens = $this->getDwcTokensWithContent($matches[1], $lead, $event);
            foreach ($matches[1] as $key => $slotName) {
                $content = $tokens[$slotName] ?? $matches[2][$key];
                $content = $this->replaceTokenInsideDWCContent($content, $event, $lead);

                $token          = '{dwc_subject_'.$slotName.'_'.$key.'}';
                $plainText      = strip_tags($content);
                $event->addToken($token, $plainText);
                $subject = str_replace($matches[0][$key], $token, $subject);
            }
        }
        $event->setIsSubject(false);

        return $subject;
    }

    /**
     * @param mixed[] $lead
     * @param mixed[] $tokens
     *
     * @return mixed[]
     */
    private function loadLeadPrimaryCompanyIfNeeded(array $lead, array $tokens): array
    {
        if (!$this->doFiltersContainCompanyFilter($this->flattenTokenFilters($tokens))) {
            return $lead;
        }

        // If companies are missing OR contain placeholders (id = 0 or “[...” values)
        if (
            empty($lead['companies'])
            || (
                isset($lead['companies'][0]['id'])
                && 0 === (int) $lead['companies'][0]['id']
            )
            || (
                isset($lead['companies'][0]['companyname'])
                && str_starts_with((string) $lead['companies'][0]['companyname'], '[')
            )
        ) {
            $lead['companies'] = array_values(
                $this->companyLeadRepository->getPrimaryCompaniesByLeadIds([$lead['id']])
            );
        }

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

    public function replaceDWCTokenToHtmlTag(string $content): string
    {
        return preg_replace_callback(
            self::DYNAMIC_WEB_CONTENT_REGEX,
            function (array $matches): string {
                $slotName = htmlspecialchars($matches[1], ENT_QUOTES);

                return '<div data-slot="dwc" data-param-slot-name="'.$slotName.'"></div>';
            },
            $content
        );
    }

    /**
     * @return array<string>
     */
    public function findDwcSlotNameFromContent(string $content): array
    {
        $dwcSlotNames = [];
        $dom          = new \DOMDocument('1.0', 'utf-8');
        $dom->loadHTML(mb_convert_encoding($content, 'HTML-ENTITIES', 'UTF-8'), LIBXML_NOERROR);
        $xpath        = new \DOMXPath($dom);
        $contentSlots = $xpath->query('//*[@data-slot="dwc" and not(ancestor::title)]');
        for ($i = 0; $i < $contentSlots->length; ++$i) {
            /** @var \DOMElement $slot */
            $slot = $contentSlots->item($i);
            if ((!$slotName = $slot->getAttribute('data-param-slot-name'))
                || in_array($slotName, $dwcSlotNames, true)
            ) {
                continue;
            }
            $dwcSlotNames[] = $slotName;
        }

        return $dwcSlotNames;
    }

    /**
     * @param Lead|mixed[] $lead
     */
    public function replaceTokenInsideDWCContent(string $content, EmailSendEvent $event, Lead|array $lead): string
    {
        if (empty($content)) {
            return '';
        }
        $emailSendEvent = new EmailSendEvent(
            null,
            [
                'content' => $content,
                'email'   => $event->getEmail(),
                'idHash'  => $event->getIdHash(),
                'tokens'  => $event->getTokens(),
                'lead'    => $lead,
            ],
            true
        );

        $this->dispatcher->dispatch($emailSendEvent, EmailEvents::EMAIL_ON_DISPLAY);

        return $emailSendEvent->getContent(true);
    }
}
