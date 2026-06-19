<?php

namespace Mautic\CoreBundle\Exception;

class FileInvalidException extends \Exception
{
    private string $messageId = '';

    /**
     * @var array<string, mixed>
     */
    private array $parameters = [];

    public function getMessageId(): string
    {
        return $this->messageId;
    }

    public function setMessageId(string $messageId): void
    {
        $this->messageId = $messageId;
    }

    /**
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param array<string, mixed> $parameters
     */
    public function setParameters(array $parameters): void
    {
        $this->parameters = $parameters;
    }
}
