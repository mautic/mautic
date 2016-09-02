<?php
/**
 * @package     Mautic
 * @copyright   2015 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\EventListener;

use Doctrine\ORM\Internal\Hydration\ObjectHydrator;
use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\TokenReplacementEvent;
use Mautic\CoreBundle\EventListener\CommonSubscriber;
use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Email;
use Mautic\EmailBundle\Event\EmailSendEvent;
use Mautic\LeadBundle\Entity\Lead;

/**
 * Class TokenSubscriber
 */
class TokenSubscriber extends CommonSubscriber
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return array(
            EmailEvents::EMAIL_ON_SEND     => array('decodeTokens', 254),
            EmailEvents::EMAIL_ON_DISPLAY  => array('decodeTokens', 254),
            EmailEvents::TOKEN_REPLACEMENT => ['onTokenReplacement', 254]
        );
    }

    /**
     * @param EmailSendEvent $event
     *
     * @return void
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

        $lead = $event->getLead();
        $dynamicContentAsArray = $event->getEmail()->getDynamicContentAsArray();

        if (! empty($dynamicContentAsArray)) {
            $tokenEvent = new TokenReplacementEvent(null, $lead, ['lead' => null, 'dynamicContent' => $dynamicContentAsArray]);
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

        if (! array_key_exists('dynamicContent', $clickthrough)) {
            return;
        }

        $lead      = $event->getLead();
        $tokenData = $clickthrough['dynamicContent'];

        if ($lead instanceof Lead) {
            $lead = $lead->getProfileFields();
        }

        foreach ($tokenData as $data) {
            $defaultContent = $data['content'];
            $filterContent  = null;

            foreach ($data['filters'] as $i => $filter) {
                if ($this->matchFilterForLead($filter['filters'], $lead)) {
                    $filterContent = $filter['content'];
                }
            }

            $event->addToken('{dynamic_content_'.$i.'}', $filterContent ?: $defaultContent);
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
        foreach ($filter as $key => $data) {
            if (!array_key_exists($data['field'], $lead)) {
                continue; //throw new \InvalidArgumentException(sprintf('The field %s is not a valid profile field to filter on.', $data['field']));
            }

            $leadVal   = $lead[$data['field']];
            $filterVal = $data['filter'];

            switch ($data['operator']) {
                case '=':
                    if ($leadVal === $filterVal) {
                        continue;
                    }

                    return false;
                case '!=':
                    if ($leadVal !== $filterVal) {
                        continue;
                    }

                    return false;
                case 'empty':
                    if (empty($leadVal)) {
                        continue;
                    }

                    return false;
                case '!empty':
                    if (!empty($leadVal)) {
                        continue;
                    }

                    return false;
                case 'like':
                    if (strpos($leadVal, $filterVal) !== false) {
                        continue;
                    }

                    return false;
                case '!like':
                    if (strpos($leadVal, $filterVal) === false) {
                        continue;
                    }
                    break;
            }
        }

        return true;
    }
}
