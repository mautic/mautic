<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\DTO;

final class Note
{
    public const TYPE_WARNING = 'warning';

    public const TYPE_INFO    = 'info';

    private string $type;

    public function __construct(
        private string $note,
        string $type,
    ) {
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
