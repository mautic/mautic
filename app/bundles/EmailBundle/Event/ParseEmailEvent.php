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
     * @param array $messages
     *
     * @return ParseEmailEvent
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
     * @return ParseEmailEvent
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
     * @param $bundleKey
     * @param $folderKeys
     * @param $criteria     This should be a string using combinations of Mautic\EmailBundle\MonitoredEmail\Mailbox::CRITERIA_* constants
     */
    public function setCriteriaRequest($bundleKey, $folderKeys, $criteria)
    {
        if (!is_array($folderKeys)) {
            $folderKeys = [$folderKeys];
        }

        foreach ($folderKeys as $folderKey) {
            $key = $bundleKey.'_'.$folderKey;

            $this->criteriaRequests[$key] = $criteria;
        }
    }

    /**
     * @return array
     */
    public function getCriteriaRequests()
    {
        return $this->criteriaRequests;
    }
}
