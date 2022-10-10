<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\DTO;

final class Note
{
    public const TYPE_WARNING = 'warning';
    public const TYPE_INFO    = 'info';

    private string $note;
    private string $type;

    public function __construct(string $note, string $type)
    {
        $this->note = $note;

        if (!in_array($type, [self::TYPE_INFO, self::TYPE_WARNING])) {
            throw new \InvalidArgumentException(sprintf('Type value can be either "%s" or "%s".', self::TYPE_INFO, self::TYPE_WARNING));
        }

        $this->type = $type;
    }

    public function getNote(): string
    {
        return $this->note;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
