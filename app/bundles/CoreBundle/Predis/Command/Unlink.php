<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Predis\Command;

use Predis\Command\Command;
use Predis\Command\PrefixableCommandInterface;

class Unlink extends Command implements PrefixableCommandInterface
{
    public const ID = 'UNLINK';

    public function getId(): string
    {
        return self::ID;
    }

    /**
     * @param list<string> $arguments
     */
    public function setArguments(array $arguments): void
    {
        parent::setArguments(self::normalizeArguments($arguments));
    }

    public function prefixKeys($prefix): void
    {
        if ($arguments = $this->getArguments()) {
            foreach ($arguments as &$key) {
                $key = "$prefix$key";
            }

            $this->setRawArguments($arguments);
        }
    }
}
