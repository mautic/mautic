<?php

namespace Mautic\EmailBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class ParseEmailEvent extends Event
{
    /**
     * @var mixed[]
     */
    private array $criteriaRequests = [];

    /**
     * @var mixed[]
     */
    private array $markAsSeen = [];

    /**
     * @param mixed[] $keys
     */
    public function __construct(
        private array $messages = [],
        private array $keys = []
    ) {
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
     * @return $this
     */
    public function setMessages($messages)
    {
        $this->messages = $messages;

        return $this;
    }

    /**
     * @return array
     */
    public function getKeys()
    {
        return $this->keys;
    }

    /**
     * @param array $keys
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
     */
    public function isApplicable($bundleKey, $folderKeys): bool
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
    public function setCriteriaRequest($bundleKey, $folderKeys, $criteria, $markAsSeen = true): void
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
