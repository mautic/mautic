<?php

declare(strict_types=1);

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\EmailBundle\Model;

use Doctrine\ORM\EntityManagerInterface;
use Mautic\EmailBundle\EmailEvents;
use Mautic\EmailBundle\Entity\Stat;
use Mautic\EmailBundle\Entity\StatRepository;
use Mautic\EmailBundle\Event\EmailStatEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

class EmailStatModel
{
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * @var EventDispatcherInterface
     */
    private $dispatcher;

    public function __construct(EntityManagerInterface $entityManager, EventDispatcherInterface $dispatcher)
    {
        $this->entityManager = $entityManager;
        $this->dispatcher    = $dispatcher;
    }

    public function saveEntity(Stat $stat): void
    {
        $this->saveEntities([$stat]);
    }

    /**
     * @var Stat[]
     */
    public function saveEntities(array $stats): void
    {
        $event = new EmailStatEvent($stats);

        $this->dispatcher->dispatch(EmailEvents::ON_EMAIL_STAT_PRE_SAVE, $event);

        $this->getRepository()->saveEntities($stats);

        $this->dispatcher->dispatch(EmailEvents::ON_EMAIL_STAT_POST_SAVE, $event);
    }

    public function getRepository(): StatRepository
    {
        return $this->entityManager->getRepository(Stat::class);
    }
}
