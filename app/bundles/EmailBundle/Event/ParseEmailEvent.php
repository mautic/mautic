<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Event;

use Mautic\EmailBundle\MonitoredEmail\Message;
use Symfony\Contracts\EventDispatcher\Event;

final class ParseEmailEvent extends Event
{
    /**
     * @param mixed[] $keys
     */
    public function __construct(
        private array $messages = [],
        private array $keys = [],
    ) {
    }

    /**
     * Get the array of messages.
     *
     * @return Message[]
     */
    public function getMessages(): array
    {
        return $this->messages;
    }

    /**
     * @param Message[] $messages
     */
    public function setMessages(array $messages): static
    {
        $this->messages = $messages;

        return $this;
    }

    public function getKeys(): array
    {
        return $this->keys;
    }

    public function setKeys(array $keys): static
    {
        $this->keys = $keys;

        return $this;
    }

    /**
     * Check if the set of messages is applicable and should be processed by the listener.
     * @param string|string[] $folderKeys
     */
    public function isApplicable(string $bundleKey, string|array $folderKeys): bool
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
}
