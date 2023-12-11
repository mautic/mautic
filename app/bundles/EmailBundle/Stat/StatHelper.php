<?php

namespace Mautic\EmailBundle\Stat;

use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\Stat\Exception\StatNotFoundException;

class StatHelper
{
    /**
     * Just store email ID and lead ID to avoid doctrine RAM issues with entities.
     *
     * @var Reference[]
     */
    private $stats = [];

    /**
     * @var array
     */
    private $deleteUs = [];

    public function __construct(
        private StatRepository $repo
    ) {
    }

    public function storeStat(Stat $stat, $emailAddress): void
    {
        $this->repo->saveEntity($stat);

        // to avoid Doctrine RAM issues, we're just going to hold onto ID references
        $this->stats[$emailAddress] = new Reference($stat);

        // clear stat from doctrine memory
        $this->repo->detachEntity($stat);
    }

    public function deletePending(): void
    {
        if (count($this->deleteUs)) {
            $this->repo->deleteStats($this->deleteUs);
        }
    }

    public function markForDeletion(Reference $stat): void
    {
        $this->deleteUs[] = $stat->getStatId();
    }

    /**
     * @return Reference
     *
     * @throws StatNotFoundException
     */
    public function getStat($emailAddress)
    {
        if (!isset($this->stats[$emailAddress])) {
            throw new StatNotFoundException();
        }

        return $this->stats[$emailAddress];
    }

    public function reset(): void
    {
        $this->deleteUs = [];
        $this->stats    = [];
    }
}
