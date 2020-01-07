<?php

declare(strict_types=1);

/*
 * @copyright   2019 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Sync\DAO\Sync;

use Mautic\IntegrationsBundle\Sync\Exception\ObjectNotFoundException;

/**
 * Holds IDs for different types of objects. Can be used for Mautic or integration objects.
 */
class ObjectIdsDAO
{
    /**
     * Expected structure:
     * [
     *      'objectA' => [12, 13],
     *      'objectB' => ['asfdaswty', 'wetegdfsd'],
     * ].
     *
     * @var array[]
     */
    private $objects = [];

    /**
     * Expected $cliOptions structure:
     * [
     *      'abjectA:12',
     *      'abjectA:13',
     *      'abjectB:asfdaswty',
     *      'abjectB:wetegdfsd',
     * ]
     * Simply put, an array of object types and IDs separated by colon.
     *
     * @param string[] $cliOptions
     *
     * @return ObjectIdsDAO
     */
    public static function createFromCliOptions(array $cliOptions): self
    {
        $objectsIdDAO = new self();

        foreach ($cliOptions as $cliOption) {
            if (is_string($cliOption) && false !== strpos($cliOption, ':')) {
                $objectsIdDAO->addObjectId(...explode(':', $cliOption));
            }
        }

        return $objectsIdDAO;
    }

    public function addObjectId(string $objectType, string $id): void
    {
        if (!isset($this->objects[$objectType])) {
            $this->objects[$objectType] = [];
        }

        $this->objects[$objectType][] = $id;
    }

    /**
     * @return string[]
     *
     * @throws ObjectNotFoundException
     */
    public function getObjectIdsFor(string $objectType): array
    {
        if (empty($this->objects[$objectType])) {
            throw new ObjectNotFoundException("Object {$objectType} doesn't have any IDs to return");
        }

        return $this->objects[$objectType];
    }
}
