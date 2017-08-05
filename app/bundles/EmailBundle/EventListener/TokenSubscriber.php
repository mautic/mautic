<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\EventListener;

use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class TokenSubscriber.
 */
class TokenSubscriber extends CommonSubscriber
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            EmailEvents::EMAIL_ON_SEND     => ['decodeTokens', 254],
            EmailEvents::EMAIL_ON_DISPLAY  => ['decodeTokens', 254],
            EmailEvents::TOKEN_REPLACEMENT => ['onTokenReplacement', 254],
        ];
    }

    /**
     * @param EmailSendEvent $event
     */
    public function decodeTokens(EmailSendEvent $event)
    {
        // Find and replace encoded tokens for trackable URL conversion
        $content = $event->getContent();
        $content = preg_replace('/(%7B)(.*?)(%7D)/i', '{$2}', $content, -1, $count);
        $event->setContent($content);

        if ($plainText = $event->getPlainText()) {
            $plainText = preg_replace('/(%7B)(.*?)(%7D)/i', '{$2}', $plainText);
            $event->setPlainText($plainText);
        }

        $email = $event->getEmail();
        if ($dynamicContentAsArray = $email instanceof Email ? $email->getDynamicContent() : null) {
            $lead       = $event->getLead();
            $tokens     = $event->getTokens();
            $tokenEvent = new TokenReplacementEvent(
                null, $lead, [
                    'tokens'         => $tokens,
                    'lead'           => null,
                    'dynamicContent' => $dynamicContentAsArray,
                ]
            );
            $this->dispatcher->dispatch(EmailEvents::TOKEN_REPLACEMENT, $tokenEvent);
            $event->addTokens($tokenEvent->getTokens());
        }
    }

    /**
     * @param TokenReplacementEvent $event
     */
    public function onTokenReplacement(TokenReplacementEvent $event)
    {
        $clickthrough = $event->getClickthrough();

        if (!array_key_exists('dynamicContent', $clickthrough)) {
            return;
        }

        $lead      = $event->getLead();
        $tokens    = $clickthrough['tokens'];
        $tokenData = $clickthrough['dynamicContent'];

        if ($lead instanceof Lead) {
            $lead = $lead->getProfileFields();
        }

        foreach ($tokenData as $data) {
            // Default content
            $filterContent = $data['content'];

            foreach ($data['filters'] as $filter) {
                if ($this->matchFilterForLead($filter['filters'], $lead)) {
                    $filterContent = $filter['content'];
                }
            }

            // Replace lead tokens in dynamic content (but no recurrence on dynamic content to avoid infinite loop)
            $emailSendEvent = new EmailSendEvent(
                null,
                [
                    'content' => $filterContent,
                    'email'   => null,
                    'idHash'  => null,
                    'tokens'  => $tokens,
                    'lead'    => $lead,
                ]
            );
            $this->dispatcher->dispatch(EmailEvents::EMAIL_ON_DISPLAY, $emailSendEvent);
            $untokenizedContent = $emailSendEvent->getContent(true);

            $event->addToken('{dynamiccontent="'.$data['tokenName'].'"}', $untokenizedContent);
        }
    }

    /**
     * @param array $filter
     * @param array $lead
     *
     * @return bool
     */
    private function matchFilterForLead(array $filter, array $lead)
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
                    $groups[$groupNum] = strpos($leadVal, $filterVal) !== false;
                    break;
                case '!like':
                    $groups[$groupNum] = strpos($leadVal, $filterVal) === false;
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
