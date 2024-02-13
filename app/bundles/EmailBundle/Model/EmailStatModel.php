<?php

declare(strict_types=1);

namespace Mautic\EmailBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\Event\EmailStatEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EmailStatModel
{
    public function __construct(private EntityManagerInterface $entityManager, private EventDispatcherInterface $dispatcher)
    {
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
        $event = new EmailStatEvent($stats);

        $this->dispatcher->dispatch($event, EmailEvents::ON_EMAIL_STAT_PRE_SAVE);

        $this->getRepository()->saveEntities($stats);

        $this->dispatcher->dispatch($event, EmailEvents::ON_EMAIL_STAT_POST_SAVE);
    }

    public function getRepository(): StatRepository
    {
        return $this->entityManager->getRepository(Stat::class);
    }
}
