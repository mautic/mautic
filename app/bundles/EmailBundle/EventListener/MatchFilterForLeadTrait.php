<?php

namespace Mautic\EmailBundle\EventListener;

use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Exception\OperatorsNotFoundException;
use Mautic\LeadBundle\Helper\FormFieldHelper;
use Mautic\LeadBundle\Segment\OperatorOptions;

trait MatchFilterForLeadTrait
{
    /**
     * @param array<int, array<string, mixed>> $filter
     * @param array<string, mixed>             $lead
     */
    protected function matchFilterForLead(array $filter, array $lead): bool
    {
        if (empty($lead['id'])) {
            // Lead in generated for preview with faked data
            return false;
        }
        $groups   = [];
        $groupNum = 0;

        foreach ($filter as $data) {
            $isCompanyField = (str_starts_with((string) $data['field'], 'company') && 'company' !== $data['field']);
            $primaryCompany = ($isCompanyField && !empty($lead['companies'])) ? $lead['companies'][0] : null;

            if ('leadlist' === $data['type'] && property_exists($this, 'segmentRepository') && $this->segmentRepository instanceof LeadListRepository) {
                return $this->isContactSegmentRelationshipValid((int) $lead['id'], $data['operator'], $data['filter']);
            }

            if ($isCompanyField) {
                if (empty($primaryCompany)) {
                    continue;
                }
            } else {
                if (!array_key_exists($data['field'] ?? '', $lead)) {
                    continue;
                }
            }

            /*
             * Split the filters into groups based on the glue.
             * The first filter and any filters whose glue is
             * "or" will start a new group.
             */
            if (0 === $groupNum || 'or' === $data['glue']) {
                ++$groupNum;
                $groups[$groupNum] = null;
            }

            /*
             * If the group has been marked as false, there
             * is no need to continue checking the others
             * in the group.
             */
            if (false === $groups[$groupNum]) {
                continue;
            }

            /*
             * If we are checking the first filter in a group
             * assume that the group will not match.
             */
            if (null === $groups[$groupNum]) {
                $groups[$groupNum] = false;
            }

            if ('leadlist' === $data['type']) {
                $groups[$groupNum] = $this->isContactSegmentRelationshipValid(
                    (int) $lead['id'], $data['operator'], $data['filter']
                );
            }

            $leadValues = $this->transformFilterDataForLead($data, $lead);

            if (null === $leadValues) {
                continue;
            }

            $filterVal = $data['filter'];
            $subgroup  = null;

            if (is_array($leadValues)) {
                foreach ($leadValues as $leadVal) {
                    if ($subgroup) {
                        break;
                    }

                    switch ($data['type']) {
                        case 'boolean':
                            if (null !== $leadVal) {
                                $leadVal = (bool) $leadVal;
                            }

                            if (null !== $filterVal) {
                                $filterVal = (bool) $filterVal;
                            }
                            break;
                        case 'datetime':
                        case 'time':
                            if (!is_null($leadVal) && !is_null($filterVal)) {
                                $leadValCount   = substr_count($leadVal, ':');
                                $filterValCount = substr_count($filterVal, ':');

                                if (2 === $leadValCount && 1 === $filterValCount) {
                                    $filterVal .= ':00';
                                }
                            }
                            break;
                        case 'tags':
                        case 'select':
                        case 'multiselect':
                            if (null !== $leadVal && !is_array($leadVal) && !empty($leadVal)) {
                                $leadVal = explode('|', $leadVal);
                            }
                            if (null !== $filterVal && !is_array($filterVal)) {
                                $filterVal = explode('|', $filterVal);
                            }
                            break;
                        case 'number':
                            $leadVal   = (float) $leadVal;
                            $filterVal = (float) $filterVal;
                            break;
                        case 'region':
                            $regionChoices = FormFieldHelper::getRegionChoices();
                            $regions       = [];
                            $currentIndex  = is_array($filterVal) ? 1 : 0; // The index starts at 0 for single value, 1 for array

                            foreach ($regionChoices as $countryRegions) {
                                foreach ($countryRegions as $region) {
                                    $regions[$currentIndex] = $region;
                                    ++$currentIndex;
                                }
                            }

                            if (is_numeric($filterVal) && isset($regions[$filterVal])) {
                                $filterVal = $regions[$filterVal];
                            }
                            if (is_array($filterVal)) {
                                foreach ($filterVal as $key => $value) {
                                    if (is_numeric($value) && isset($regions[$value])) {
                                        $filterVal[$key] = $regions[$value];
                                    }
                                }
                            }
                    }

                    switch ($data['operator']) {
                        case '=':
                            if ('boolean' === $data['type']) {
                                $groups[$groupNum] = $leadVal === $filterVal;
                            } else {
                                $groups[$groupNum] = $leadVal == $filterVal;
                            }
                            break;
                        case '!=':
                            if ('boolean' === $data['type']) {
                                $groups[$groupNum] = $leadVal !== $filterVal;
                            } else {
                                $groups[$groupNum] = $leadVal != $filterVal;
                            }
                            break;
                        case 'gt':
                            $groups[$groupNum] = $leadVal > $filterVal;
                            break;
                        case 'gte':
                            $groups[$groupNum] = $leadVal >= $filterVal;
                            break;
                        case 'lt':
                            $groups[$groupNum] = $leadVal < $filterVal;
                            break;
                        case 'lte':
                            $groups[$groupNum] = $leadVal <= $filterVal;
                            break;
                        case 'empty':
                            $groups[$groupNum] = empty($leadVal);
                            break;
                        case '!empty':
                            $groups[$groupNum] = !empty($leadVal);
                            break;
                        case 'like':
                            $matchVal          = str_replace(['.', '*', '%'], ['\.', '\*', '.*'], $filterVal);
                            $groups[$groupNum] = 1 === preg_match('/'.$matchVal.'/', $leadVal);
                            break;
                        case '!like':
                            $matchVal          = str_replace(['.', '*'], ['\.', '\*'], $filterVal);
                            $matchVal          = str_replace('%', '.*', $matchVal);
                            $groups[$groupNum] = 1 !== preg_match('/'.$matchVal.'/', $leadVal);
                            break;
                        case OperatorOptions::INCLUDING_ANY:
                            $groups[$groupNum] = $this->checkLeadValueIsInFilter($leadVal, $filterVal, false);
                            break;
                        case OperatorOptions::EXCLUDING_ANY:
                            $groups[$groupNum] = $this->checkLeadValueIsInFilter($leadVal, $filterVal, true);
                            break;
                        case OperatorOptions::INCLUDING_ALL:
                            $groups[$groupNum] = $this->checkAllLeadValuesAreInFilter($leadVal, $filterVal, false);
                            break;
                        case OperatorOptions::EXCLUDING_ALL:
                            $groups[$groupNum] = $this->checkAllLeadValuesAreInFilter($leadVal, $filterVal, true);
                            break;
                        case 'regexp':
                            $groups[$groupNum] = 1 === preg_match('/'.$filterVal.'/i', $leadVal);
                            break;
                        case '!regexp':
                            $groups[$groupNum] = 1 !== preg_match('/'.$filterVal.'/i', $leadVal);
                            break;
                        case 'startsWith':
                            $groups[$groupNum] = str_starts_with($leadVal, $filterVal);
                            break;
                        case 'endsWith':
                            $endOfString       = substr($leadVal, strlen($leadVal) - strlen($filterVal));
                            $groups[$groupNum] = 0 === strcmp($endOfString, $filterVal);
                            break;
                        case 'contains':
                            $groups[$groupNum] = str_contains((string) $leadVal, (string) $filterVal);
                            break;
                        default:
                            throw new OperatorsNotFoundException('Operator is not defined or invalid operator found.');
                    }

                    $subgroup = $groups[$groupNum];
                }
            }
        }

        return in_array(true, $groups);
    }

    /**
     * @param mixed[] $data
     * @param mixed[] $lead
     *
     * @return ?mixed[]
     */
    private function transformFilterDataForLead(array $data, array $lead): ?array
    {
        if ($this->isFilterCompany($data)) {
            $primaryCompany = $lead['companies'][0] ?? null;

            // new behavior: if no company, return empty string so it fails !empty or equals checks
            if (empty($primaryCompany)) {
                return [''];
            }

            return [$primaryCompany[$data['field']]];
        }

        return !array_key_exists($data['field'], $lead) ? null : [$lead[$data['field']]];
    }

    /**
     * @param mixed $leadVal
     * @param mixed $filterVal
     */
    private function checkLeadValueIsInFilter($leadVal, $filterVal, bool $defaultFlag): bool
    {
        $leadVal    = !is_array($leadVal) ? [$leadVal] : $leadVal;
        $filterVal  = !is_array($filterVal) ? [$filterVal] : $filterVal;
        $retFlag    = $defaultFlag;
        foreach ($leadVal as $v) {
            if (in_array($v, $filterVal)) {
                $retFlag = !$defaultFlag;
                // Break once we find a match
                break;
            }
        }

        return $retFlag;
    }

    /**
     * @param mixed $leadVal
     * @param mixed $filterVal
     */
    private function checkAllLeadValuesAreInFilter($leadVal, $filterVal, bool $defaultFlag): bool
    {
        $leadVal       = !is_array($leadVal) ? [$leadVal] : $leadVal;
        $filterVal     = !is_array($filterVal) ? [$filterVal] : $filterVal;
        $valuesMatched = 0;

        foreach ($leadVal as $value) {
            if (in_array($value, $filterVal)) {
                ++$valuesMatched;
            }
        }

        return $valuesMatched === count($filterVal) ? !$defaultFlag : $defaultFlag;
    }

    /**
     * Duplicate method. Needs refactoring.
     *
     * @see \Mautic\LeadBundle\EventListener\DynamicContentSubscriber::isContactSegmentRelationshipValid
     *
     * @param string $operator   empty, !empty, in, !in
     * @param int[]  $segmentIds
     */
    private function isContactSegmentRelationshipValid(int $contactId, string $operator, ?array $segmentIds = null): bool
    {
        return match ($operator) {
            OperatorOptions::EMPTY         => $this->segmentRepository->isNotContactInAnySegment($contactId), // Contact is not in any segment
            OperatorOptions::NOT_EMPTY     => $this->segmentRepository->isContactInAnySegment($contactId), // Contact is in any segment
            OperatorOptions::INCLUDING_ANY => $this->segmentRepository->isContactInSegments($contactId, $segmentIds), // Contact is in one of the segment provided in $segmentsIds
            OperatorOptions::EXCLUDING_ANY => $this->segmentRepository->isNotContactInSegments($contactId, $segmentIds), // Contact is not in some segments provided in $segmentsIds
            OperatorOptions::INCLUDING_ALL => $this->segmentRepository->isContactInAllSegments($contactId, $segmentIds), // Contact is in all segments provided in $segmentsIds
            OperatorOptions::EXCLUDING_ALL => $this->segmentRepository->isNotContactInAllSegments($contactId, $segmentIds), // Contact is not in all segments provided in $segmentsIds
            default                        => throw new \InvalidArgumentException(sprintf("Unexpected operator '%s'", $operator)),
        };
    }

    /**
     * @param iterable<mixed> $filters
     */
    private function doFiltersContainCompanyFilter(iterable $filters): bool
    {
        foreach ($filters as $filter) {
            if ($this->isFilterCompany($filter)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param iterable<mixed> $filters
     */
    private function doFiltersContainTagsFilter(iterable $filters): bool
    {
        foreach ($filters as $filter) {
            if ('tags' === ($filter['type'] ?? null)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed[] $filter
     */
    private function isFilterCompany(array $filter): bool
    {
        $object = $filter['object'] ?? '';

        if ('company' === $object) {
            return true;
        }

        $field = $filter['field'] ?? '';

        return str_starts_with($field, 'company') && 'company' !== $field;
    }
}
