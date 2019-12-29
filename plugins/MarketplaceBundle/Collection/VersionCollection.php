<?php

/*
 * @copyright   2019 Mautic. All rights reserved
 * @author      Mautic.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace MauticPlugin\MarketplaceBundle\Collection;

use ArrayAccess;
use Composer\Package\Package;
use Countable;
use Iterator;
use MauticPlugin\MarketplaceBundle\DTO\Version;
use MauticPlugin\MarketplaceBundle\Exception\RecordNotFoundException;

class VersionCollection implements Iterator, Countable, ArrayAccess
{
    /**
     * @var Version[]
     */
    private $records;

    /**
     * @var int
     */
    private $position = 0;

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
                function (array $record) {
                    return Version::fromArray($record);
                },
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

    public function findLatestVersionPackage(int $stabilityPriority = Package::STABILITY_STABLE, string $mauticVersion = MAUTIC_VERSION): Version
    {
        $latestVersion = null;

        $this->map(function (Version $version) use (&$latestVersion, $mauticVersion, $stabilityPriority) {
            // @todo check for the right Mautic supported version as well.

            if ($version->getStabilityPriority() >= $stabilityPriority) {
                return $version;
            }

            if (empty($latestVersion)) {
                $latestVersion = $version;
            }

            if (version_compare($version->getVersion(), $latestVersion->getVersion(), '>')) {
                $latestVersion = $version;
            }
        });

        if (empty($latestVersion)) {
            $stability = array_search($stabilityPriority, Package::$stabilities);
            throw new \Exception("No version was found for Mautic version {$mauticVersion} and {$stability} stability. There are {$this->count()} other versions. Try to lower the stability.");
        }

        return $latestVersion;
    }

    /**
     * {@inheritdoc}
     */
    public function current(): Version
    {
        return $this->records[$this->position];
    }

    /**
     * {@inheritdoc}
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * {@inheritdoc}
     */
    public function valid()
    {
        return isset($this->records[$this->position]);
    }

    /**
     * {@inheritdoc}
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * {@inheritdoc}
     */
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
