<?php

declare(strict_types=1);

/*
 * @copyright   2018 Mautic Inc. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://www.mautic.com
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\IntegrationsBundle\Sync\DAO\Sync\Request;

use Mautic\IntegrationsBundle\Sync\DAO\Sync\InputOptionsDAO;

class RequestDAO
{
    /**
     * @var int
     */
    private $syncIteration;

    /**
     * @var InputOptionsDAO
     */
    private $inputOptionsDAO;

    /**
     * @var string
     */
    private $syncToIntegration;

    /**
     * @var ObjectDAO[]
     */
    private $objects = [];

    public function __construct(string $syncToIntegration, int $syncIteration, InputOptionsDAO $inputOptionsDAO)
    {
        $this->syncIteration     = (int) $syncIteration;
        $this->inputOptionsDAO   = $inputOptionsDAO;
        $this->syncToIntegration = $syncToIntegration;
    }

    /**
     * @return self
     */
    public function addObject(ObjectDAO $objectDAO)
    {
        $this->objects[] = $objectDAO;

        return $this;
    }

    /**
     * @return ObjectDAO[]
     */
    public function getObjects(): array
    {
        return $this->objects;
    }

    public function getSyncIteration(): int
    {
        return $this->syncIteration;
    }

    public function isFirstTimeSync(): bool
    {
        return $this->inputOptionsDAO->isFirstTimeSync();
    }

    /**
     * The integration that will be synced to.
     */
    public function getSyncToIntegration(): string
    {
        return $this->syncToIntegration;
    }

    /**
     * Returns DAO object with all input options.
     */
    public function getInputOptionsDAO(): InputOptionsDAO
    {
        return $this->inputOptionsDAO;
    }

    /**
     * Returns true if there are objects to sync.
     *
     * @return bool
     */
    public function shouldSync()
    {
        return !empty($this->objects);
    }
}
