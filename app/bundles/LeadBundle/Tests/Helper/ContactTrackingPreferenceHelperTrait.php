<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Helper;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Persisters\Entity\EntityPersister;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\ManagerRegistry;
use Mautic\LeadBundle\Entity\TrackingPreference;
use Mautic\LeadBundle\Entity\TrackingPreferenceRepository;
use Mautic\LeadBundle\Helper\ContactTrackingPreferenceHelper;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * For unit tests of services that need the final helper: builds one that finds no stored choice, so every contact allows tracking.
 */
trait ContactTrackingPreferenceHelperTrait
{
    private function createTrackingPreferenceHelper(): ContactTrackingPreferenceHelper
    {
        $unitOfWork = $this->createStub(UnitOfWork::class);
        $unitOfWork->method('getEntityPersister')->willReturn($this->createStub(EntityPersister::class));

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getClassMetadata')->willReturn(new ClassMetadata(TrackingPreference::class));
        $entityManager->method('getUnitOfWork')->willReturn($unitOfWork);

        $managerRegistry = $this->createStub(ManagerRegistry::class);
        $managerRegistry->method('getManagerForClass')->willReturn($entityManager);

        return new ContactTrackingPreferenceHelper(
            new TrackingPreferenceRepository($managerRegistry),
            $this->createStub(EntityManagerInterface::class),
            $this->createStub(EventDispatcherInterface::class),
        );
    }
}
