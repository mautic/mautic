<?php

namespace Mautic\EmailBundle\EventListener;

use Mautic\LeadBundle\Segment\OperatorOptions;

/**
 * Trait MatchFilterForLeadTrait.
 */
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
            $isCompanyField = (str_starts_with((string) $data['field'], 'company') && 'company' !== $data['field']);
            $primaryCompany = ($isCompanyField && !empty($lead['companies'])) ? $lead['companies'][0] : null;

            if ($isCompanyField) {
                if (empty($primaryCompany)) {
                    continue;
                }
            } else {
                if (!array_key_exists($data['field'], $lead)) {
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

            $leadVal   = ($isCompanyField ? $primaryCompany[$data['field']] : $lead[$data['field']]);
            $filterVal = $data['filter'];

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
                    $leadValCount   = substr_count($leadVal, ':');
                    $filterValCount = substr_count($filterVal, ':');

                    if (2 === $leadValCount && 1 === $filterValCount) {
                        $filterVal .= ':00';
                    }
                    break;
                case 'tags':
                case 'multiselect':
                    if (!is_array($leadVal)) {
                        $leadVal = explode('|', $leadVal);
                    }
                    if (!is_array($filterVal)) {
                        $filterVal = explode('|', $filterVal);
                    }
                    break;
                case 'number':
                    $leadVal   = (int) $leadVal;
                    $filterVal = (int) $filterVal;
                    break;
                case 'select':
                    if (!is_array($filterVal)) {
                        $filterVal = explode('|', $filterVal);
                    }
                    break;
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
                    $filterVal         = str_replace(['.', '*', '%'], ['\.', '\*', '.*'], $filterVal);
                    $groups[$groupNum] = 1 === preg_match('/'.$filterVal.'/', $leadVal);
                    break;
                case '!like':
                    $filterVal         = str_replace(['.', '*'], ['\.', '\*'], $filterVal);
                    $filterVal         = str_replace('%', '.*', $filterVal);
                    $groups[$groupNum] = 1 !== preg_match('/'.$filterVal.'/', $leadVal);
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
                    $groups[$groupNum] = str_starts_with($leadVal, $filterVal);
                    break;
                case 'endsWith':
                    $endOfString       = substr($leadVal, strlen($leadVal) - strlen($filterVal));
                    $groups[$groupNum] = 0 === strcmp($endOfString, $filterVal);
                    break;
                case 'contains':
                    $groups[$groupNum] = str_contains((string) $leadVal, (string) $filterVal);
                    break;
            }
        }

        return in_array(true, $groups);
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
}
