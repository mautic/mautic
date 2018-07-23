<?php
declare(strict_types=1);

namespace MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Request;

/**
 * Class RequestDAO
 * @package MauticPlugin\MauticIntegrationsBundle\DAO\Sync\Report
 */
class RequestDAO
{
    /**
     * @var int|null
     */
    private $fromTimestamp = null;

    /**
     * @var ObjectDAO[]
     */
    private $objects = [];

    /**
     * RequestDAO constructor.
     *
     * @param int|null $fromTimestamp
     */
    public function __construct(int $fromTimestamp = null)
    {
        $this->fromTimestamp = $fromTimestamp;
    }

    /**
     * @return int|null
     */
    public function getFromTimestamp(): ?int
    {
        return $this->fromTimestamp;
    }

    /**
     * @param ObjectDAO $objectDAO
     *
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
}
