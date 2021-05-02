<?php
/*
 * @copyright   2017 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\EventListener;

/**
 * Trait MatchFilterForLeadTrait.
 */
trait MatchFilterForLeadTrait
{
    /**
     * @return bool
     */
    protected function matchFilterForLead(array $filter, array $lead)
    {
        if (empty($lead['id'])) {
            // Lead in generated for preview with faked data
            return false;
        }
        $groups   = [];
        $groupNum = 0;

        foreach ($filter as $data) {
            $isCompanyField = (0 === strpos($data['field'], 'company') && 'company' !== $data['field']);
            $primaryCompany = ($isCompanyField && !empty($lead['companies'])) ? $lead['companies'][0] : null;

            if (!array_key_exists($data['field'], $lead) && !$isCompanyField) {
                continue;
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
                default:
                    if (is_numeric($leadVal)) {
                        $leadVal   = (int) $leadVal;
                        $filterVal = (int) $filterVal;
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
                case 'in':
                    $leadValMatched = false;
                    foreach ($leadVal as $v) {
                        if (in_array($v, $filterVal)) {
                            $leadValMatched = true;
                            // Break once we find a match
                            break;
                        }
                    }
                    $groups[$groupNum] = $leadValMatched;
                    break;
                case '!in':
                    $leadValNotMatched = true;

                    foreach ($leadVal as $v) {
                        if (in_array($v, $filterVal)) {
                            $leadValNotMatched = false;
                            // Break once we find a match
                            break;
                        }
                    }

                    $groups[$groupNum] = $leadValNotMatched;
                    break;
                case 'regexp':
                    $groups[$groupNum] = 1 === preg_match('/'.$filterVal.'/i', $leadVal);
                    break;
                case '!regexp':
                    $groups[$groupNum] = 1 !== preg_match('/'.$filterVal.'/i', $leadVal);
                    break;
            }
        }

        return in_array(true, $groups);
    }
}
