<?php

declare(strict_types=1);

namespace Mautic\MarketplaceBundle\Collection;

use Mautic\MarketplaceBundle\DTO\Version;
use Mautic\MarketplaceBundle\Exception\RecordNotFoundException;

class VersionCollection implements \Iterator, \Countable, \ArrayAccess
{
    /**
     * @var Version[]
     */
    private array $records;

    private int $position = 0;

    /**
     * @param Version[] $records
     */
    public function __construct(array $records = [])
    {
        $this->records = array_values($records);
    }

    public static function fromArray(array $array): VersionCollection
    {
        return new self(
            array_map(
                fn (array $record) => Version::fromArray($record),
                $array
            )
        );
    }

    public function map(callable $callback): VersionCollection
    {
        return new self(array_map($callback, $this->records));
    }

    public function add(Version $record): void
    {
        $this->records[] = $record;
    }

    public function filter(callable $callback): VersionCollection
    {
        return new self(array_values(array_filter($this->records, $callback)));
    }

    public function findLatestVersionPackage(int $stabilityPriority = 0, string $mauticVersion = MAUTIC_VERSION): ?Version
    {
        $latestVersion = null;

        $this->map(function (Version $version) use (&$latestVersion, $mauticVersion, $stabilityPriority) {
            // @todo check for the right Mautic supported version.
            // @todo check for the right stability.

            if (empty($latestVersion)) {
                $latestVersion = $version;
            }

            if (version_compare($version->getVersion(), $latestVersion->getVersion(), '>')) {
                $latestVersion = $version;
            }
        });

        return $latestVersion;
    }

    public function current(): Version
    {
        return $this->records[$this->position];
    }

    public function next()
    {
        ++$this->position;
    }

    public function key()
    {
        return $this->position;
    }

    public function valid()
    {
        return isset($this->records[$this->position]);
    }

    public function rewind()
    {
        $this->position = 0;
    }

    public function count(): int
    {
        return count($this->records);
    }

    public function offsetSet($offset, $value)
    {
        if (is_null($offset)) {
            $this->records[] = $value;
        } else {
            $this->records[$offset] = $value;
        }
    }

    public function offsetExists($offset)
    {
        return isset($this->records[$offset]);
    }

    public function offsetUnset($offset)
    {
        unset($this->records[$offset]);
    }

    public function offsetGet($offset): Version
    {
        if (isset($this->records[$offset])) {
            return $this->records[$offset];
        }

        throw new RecordNotFoundException("Version on offset {$offset} was not found");
    }
}
