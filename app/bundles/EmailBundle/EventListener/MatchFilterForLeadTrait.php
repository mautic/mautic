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
 * Trait MatchFilterForLeadTrait
 */
trait MatchFilterForLeadTrait
{
    /**
     * @param array $filter
     * @param array $lead
     *
     * @return bool
     */
    protected function matchFilterForLead(array $filter, array $lead) : bool
    {
        $groups   = [];
        $groupNum = 0;

        foreach ($filter as $key => $data) {
            $isCompanyField = (strpos($data['field'], 'company') === 0 && $data['field'] !== 'company');
            $primaryCompany = ($isCompanyField && !empty($lead['companies'])) ? $lead['companies'][0] : null;

            if (!array_key_exists($data['field'], $lead) && !$isCompanyField) {
                continue;
            }

            /*
             * Split the filters into groups based on the glue.
             * The first filter and any filters whose glue is
             * "or" will start a new group.
             */
            if ($groupNum === 0 || $data['glue'] === 'or') {
                ++$groupNum;
                $groups[$groupNum] = null;
            }

            /*
             * If the group has been marked as false, there
             * is no need to continue checking the others
             * in the group.
             */
            if ($groups[$groupNum] === false) {
                continue;
            }

            /*
             * If we are checking the first filter in a group
             * assume that the group will not match.
             */
            if ($groups[$groupNum] === null) {
                $groups[$groupNum] = false;
            }

            $leadVal   = ($isCompanyField ? $primaryCompany[$data['field']] : $lead[$data['field']]);
            $filterVal = $data['filter'];

            switch ($data['type']) {
                case 'boolean':
                    if ($leadVal !== null) {
                        $leadVal = (bool) $leadVal;
                    }

                    if ($filterVal !== null) {
                        $filterVal = (bool) $filterVal;
                    }
                    break;
                case 'date':
                    if (!$leadVal instanceof \DateTime) {
                        $leadVal = new \DateTime($leadVal);
                    }

                    if (!$filterVal instanceof \DateTime) {
                        $filterVal = new \DateTime($filterVal);
                    }
                    break;
                case 'datetime':
                case 'time':
                    $leadValCount   = substr_count($leadVal, ':');
                    $filterValCount = substr_count($filterVal, ':');

                    if ($leadValCount === 2 && $filterValCount === 1) {
                        $filterVal .= ':00';
                    }
                    break;
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
                    $groups[$groupNum] = $leadVal == $filterVal;
                    break;
                case '!=':
                    $groups[$groupNum] = $leadVal != $filterVal;
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
                    $groups[$groupNum] = preg_match('/'.$filterVal.'/', $leadVal) === 1;
                    break;
                case '!like':
                    $filterVal         = str_replace(['.', '*'], ['\.', '\*'], $filterVal);
                    $filterVal         = str_replace('%', '.*', $filterVal);
                    $groups[$groupNum] = preg_match('/'.$filterVal.'/', $leadVal) !== 1;
                    break;
                case 'in':
                    foreach ($leadVal as $k => $v) {
                        if (in_array($v, $filterVal)) {
                            $groups[$groupNum] = true;
                            // Break once we find a match
                            break;
                        }
                    }
                    break;
                case '!in':
                    $leadValNotMatched = true;

                    foreach ($leadVal as $k => $v) {
                        if (in_array($v, $filterVal)) {
                            $leadValNotMatched = false;
                            // Break once we find a match
                            break;
                        }
                    }

                    $groups[$groupNum] = $leadValNotMatched;
                    break;
                case 'regexp':
                    $groups[$groupNum] = preg_match('/'.$filterVal.'/i', $leadVal) === 1;
                    break;
                case '!regexp':
                    $groups[$groupNum] = preg_match('/'.$filterVal.'/i', $leadVal) !== 1;
                    break;
            }
        }

        return in_array(true, $groups);
    }
}
