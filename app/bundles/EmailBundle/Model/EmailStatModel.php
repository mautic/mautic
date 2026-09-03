<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Model;

use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\Event\EmailStatEvent;
use Mautic\EmailBundle\Event\EmailStatPostSaveEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EmailStatModel
{
    public function __construct(
        private readonly EventDispatcherInterface $dispatcher,
        private readonly StatRepository $statRepository,
    ) {
    }

    public function saveEntity(Stat $stat): void
    {
        $this->saveEntities([$stat]);
    }

    /**
     * @param Stat[] $stats
     */
    public function saveEntities(array $stats): void
    {
        $this->dispatcher->dispatch(new EmailStatEvent($stats));

        $this->statRepository->saveEntities($stats);

        $this->dispatcher->dispatch(new EmailStatPostSaveEvent($stats));
    }
}
