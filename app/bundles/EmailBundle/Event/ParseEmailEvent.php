<?php

/*
 * @copyright   2015 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Event;

use Symfony\Component\EventDispatcher\Event;

/**
 * Class ParseEmailEvent.
 */
class ParseEmailEvent extends Event
{
    /**
     * @var array
     */
    private $messages;

    /**
     * @var
     */
    private $keys;

    /**
     * @var array
     */
    private $criteriaRequests = [];

    /**
     * @var array
     */
    private $markAsSeen = [];

    /**
     * @param array $messages
     * @param array $applicableKeys
     */
    public function __construct(array $messages = [], array $applicableKeys = [])
    {
        $this->messages = $messages;
        $this->keys     = $applicableKeys;
    }

    /**
     * Get the array of messages.
     *
     * @return \Mautic\EmailBundle\MonitoredEmail\Message[]
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * @param $messages
     *
     * @return $this
     */
    public function setMessages($messages)
    {
        $this->messages = $messages;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getKeys()
    {
        return $this->keys;
    }

    /**
     * @param mixed $keys
     *
     * @return $this
     */
    public function setKeys($keys)
    {
        $this->keys = $keys;

        return $this;
    }

    /**
     * Check if the set of messages is applicable and should be processed by the listener.
     *
     * @param $bundleKey
     * @param $folderKeys
     *
     * @return bool
     */
    public function isApplicable($bundleKey, $folderKeys)
    {
        if (!is_array($folderKeys)) {
            $folderKeys = [$folderKeys];
        }

        foreach ($folderKeys as $folderKey) {
            $key = $bundleKey.'_'.$folderKey;

            if (in_array($key, $this->keys)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Set a criteria request for filtering fetched mail.
     *
     * @param string $bundleKey
     * @param string $folderKeys
     * @param string $criteria   Should be a string using combinations of Mautic\EmailBundle\MonitoredEmail\Mailbox::CRITERIA_* constants
     * @param bool   $markAsSeen Mark the message as read after being processed
     */
    public function setCriteriaRequest($bundleKey, $folderKeys, $criteria, $markAsSeen = true)
    {
        if (!is_array($folderKeys)) {
            $folderKeys = [$folderKeys];
        }

        foreach ($folderKeys as $folderKey) {
            $key = $bundleKey.'_'.$folderKey;

            $this->criteriaRequests[$key] = $criteria;
            $this->markAsSeen[$key]       = $markAsSeen;
        }
    }

    /**
     * @return array
     */
    public function getCriteriaRequests()
    {
        return $this->criteriaRequests;
    }

    /**
     * @return array
     */
    public function getMarkAsSeenInstructions()
    {
        return $this->markAsSeen;
    }
}
