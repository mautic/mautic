<?php

namespace Mautic\EmailBundle\EventListener;

use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Segment\OperatorOptions;

trait MatchFilterForLeadTrait
{
    protected function matchFilterForLead(array $filter, array $lead): bool
    {
        if (empty($lead['id'])) {
            // Lead in generated for preview with faked data
            return false;
        }
        $groups   = [];
        $groupNum = 0;

        foreach ($filter as $data) {
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
                        case 'multiselect':
                            if (!is_array($leadVal) && !empty($leadVal)) {
                                $leadVal = explode('|', $leadVal);
                            }
                            if (!is_null($filterVal) && !is_array($filterVal)) {
                                $filterVal = explode('|', $filterVal);
                            }
                            break;
                        case 'number':
                            $leadVal   = (float) $leadVal;
                            $filterVal = (float) $filterVal;
                            break;
                        case 'select':
                            if (!is_array($filterVal)) {
                                $filterVal = explode('|', $filterVal);
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
                        case OperatorOptions::IN:
                            $groups[$groupNum] = $this->checkLeadValueIsInFilter($leadVal, $filterVal, false);
                            break;
                        case OperatorOptions::NOT_IN:
                            $groups[$groupNum] = $this->checkLeadValueIsInFilter($leadVal, $filterVal, true);
                            break;
                        case 'regexp':
                            $groups[$groupNum] = 1 === preg_match('/'.$filterVal.'/i', $leadVal);
                            break;
                        case '!regexp':
                            $groups[$groupNum] = 1 !== preg_match('/'.$filterVal.'/i', $leadVal);
                            break;
                        case 'startsWith':
                            $groups[$groupNum] = 0 === strncmp($leadVal, $filterVal, strlen($filterVal));
                            break;
                        case 'endsWith':
                            $endOfString       = substr($leadVal, strlen($leadVal) - strlen($filterVal));
                            $groups[$groupNum] = 0 === strcmp($endOfString, $filterVal);
                            break;
                        case 'contains':
                            $groups[$groupNum] = false !== strpos((string) $leadVal, (string) $filterVal);
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
            $primaryCompany = $lead['companies'][0] ?: null;

            return empty($primaryCompany) ? null : [$primaryCompany[$data['field']]];
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
     * Duplicate method. Needs refactoring.
     *
     * @see \Mautic\LeadBundle\EventListener\DynamicContentSubscriber::isContactSegmentRelationshipValid
     *
     * @param string $operator   empty, !empty, in, !in
     * @param int[]  $segmentIds
     */
    private function isContactSegmentRelationshipValid(LeadListRepository $segmentRepository, int $contactId, string $operator, ?array $segmentIds = null): bool
    {
        return match ($operator) {
            OperatorOptions::EMPTY     => $segmentRepository->isNotContactInAnySegment($contactId), // Contact is not in any segment
            OperatorOptions::NOT_EMPTY => $segmentRepository->isContactInAnySegment($contactId), // Contact is in any segment
            OperatorOptions::IN        => $segmentRepository->isContactInSegments($contactId, $segmentIds), // Contact is in one of the segment provided in $segmentsIds
            OperatorOptions::NOT_IN    => $segmentRepository->isNotContactInSegments($contactId, $segmentIds), // Contact is not in all segments provided in $segmentsIds
            default                    => throw new \InvalidArgumentException(sprintf("Unexpected operator '%s'", $operator)),
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
