<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Swiftmailer\Momentum\Callback;

use Mautic\LeadBundle\Entity\DoNotContact;

/**
 * Class CallbackEnum.
 */
class CallbackEnum
{
    const BOUNCE           = 'bounce';
    const OUT_OF_BAND      = 'out_of_band';
    const POLICY_REJECTION = 'policy_rejection';
    const SPAM_COMPLAINT   = 'spam_complaint';
    const LIST_UNSUBSCRIBE = 'list_unsubscribe';
    const LINK_UNSUBSCRIBE = 'link_unsubscribe';

    const BOUNCE_CLASS_INVALID_RECIPIENT     = 10;
    const BOUNCE_CLASS_GENERIC               = 30;
    const BOUNCE_CLASS_MAIL_BLOCK            = 50;
    const BOUNCE_CLASS_SPAM_BLOCK            = 51;
    const BOUNCE_CLASS_SPAM_CONTENT          = 52;
    const BOUNCE_CLASS_PROHIBITED_ATTACHMENT = 53;
    const BOUNCE_CLASS_RELAYING_DENIED       = 54;
    const BOUNCE_CLASS_UNSUBSCRIBE           = 90;

    /**
     * @param string $event
     * @param null   $bounceClass
     *
     * @return bool
     */
    public static function shouldBeEventProcessed($event, $bounceClass = null)
    {
        if ($bounceClass && self::BOUNCE === $event) {
            return in_array($bounceClass, self::getHardBounces(), true);
        }

        return in_array($event, self::getSupportedEvents(), true);
    }

    /**
     * @param $event
     *
     * @return string|null
     */
    public static function convertEventToDncReason($event)
    {
        if (!self::shouldBeEventProcessed($event)) {
            return null;
        }

        $mapping = self::eventMappingToDncReason();

        return $mapping[$event];
    }

    /**
     * @param string $event
     * @param array  $item
     *
     * @return string|null
     */
    public static function getDncComments($event, array $item)
    {
        if (!self::shouldBeEventProcessed($event)) {
            return null;
        }

        $key = self::getCommentsKeyForEvent($event);

        return isset($item[$key]) ? $item[$key] : $key;
    }

    /**
     * @return array
     */
    private static function getSupportedEvents()
    {
        return [
            self::BOUNCE,
            self::OUT_OF_BAND,
            self::POLICY_REJECTION,
            self::SPAM_COMPLAINT,
            self::LIST_UNSUBSCRIBE,
            self::LINK_UNSUBSCRIBE,
        ];
    }

    /**
     * @return array
     */
    private static function getHardBounces()
    {
        return [
            self::BOUNCE_CLASS_INVALID_RECIPIENT,
            self::BOUNCE_CLASS_GENERIC,
            self::BOUNCE_CLASS_MAIL_BLOCK,
            self::BOUNCE_CLASS_SPAM_BLOCK,
            self::BOUNCE_CLASS_SPAM_CONTENT,
            self::BOUNCE_CLASS_PROHIBITED_ATTACHMENT,
            self::BOUNCE_CLASS_RELAYING_DENIED,
            self::BOUNCE_CLASS_UNSUBSCRIBE,
        ];
    }

    /**
     * @return array
     */
    private static function eventMappingToDncReason()
    {
        return [
            self::BOUNCE           => DoNotContact::BOUNCED,
            self::OUT_OF_BAND      => DoNotContact::BOUNCED,
            self::POLICY_REJECTION => DoNotContact::BOUNCED,
            self::SPAM_COMPLAINT   => DoNotContact::UNSUBSCRIBED,
            self::LIST_UNSUBSCRIBE => DoNotContact::UNSUBSCRIBED,
            self::LINK_UNSUBSCRIBE => DoNotContact::UNSUBSCRIBED,
        ];
    }

    /**
     * @param $event
     *
     * @return mixed|null
     */
    private static function getCommentsKeyForEvent($event)
    {
        $mapping = [
            self::BOUNCE           => 'raw_reason',
            self::OUT_OF_BAND      => 'raw_reason',
            self::POLICY_REJECTION => 'raw_reason',
            self::SPAM_COMPLAINT   => 'fbtype',
            self::LIST_UNSUBSCRIBE => 'unsubscribed',
            self::LINK_UNSUBSCRIBE => 'unsubscribed',
        ];

        return (isset($mapping[$event])) ? $mapping[$event] : null;
    }
}
