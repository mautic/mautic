<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional\Entity;

use Mautic\LeadBundle\Entity\Lead;
use Mautic\StageBundle\Entity\Stage;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Component\HttpFoundation\Request;

final class LeadStageRelationshipTest extends MauticMysqlTestCase
{
    public function testBatchEditApiDoesNotDuplicateStage(): void
    {
        // Step 1: Create a stage
        $stage = new Stage();
        $stage->setName('Test Stage');
        $stage->setDescription('Stage for testing batch edit bug');
        
        // Step 2: Create two contacts and assign them to the same stage
        $contact1 = new Lead();
        $contact1->setFirstname('John');
        $contact1->setLastname('Doe');
        $contact1->setEmail('john.doe@test.com');
        $contact1->setStage($stage);
        
        $contact2 = new Lead();
        $contact2->setFirstname('Jane');
        $contact2->setLastname('Smith');
        $contact2->setEmail('jane.smith@test.com');
        $contact2->setStage($stage);
        
        $this->em->persist($stage);
        $this->em->persist($contact1);
        $this->em->persist($contact2);
        $this->em->flush();
        
        $contact1Id = $contact1->getId();
        $contact2Id = $contact2->getId();
        $originalStageId = $stage->getId();
        
        // Count stages before the batch edit
        $stageRepository = $this->em->getRepository(Stage::class);
        $stageCountBefore = count($stageRepository->findAll());
        
        $payload = [
            ['id' => $contact1Id],
            ['id' => $contact2Id]
        ];
        
        $this->client->request(
            Request::METHOD_PATCH,
            '/api/contacts/batch/edit',
            $payload
        );
        
        $this->assertResponseIsSuccessful();
        $this->em->clear();
        
        // Step 4: Check if stages were duplicated (this is the bug)
        $stageCountAfter = count($stageRepository->findAll());
        $allStages = $stageRepository->findAll();
        
        $this->assertSame(
            $stageCountBefore,
            $stageCountAfter,
            sprintf(
                'Stage duplication detected! Expected %d stages, found %d. Stages: %s',
                $stageCountBefore,
                $stageCountAfter,
                implode(', ', array_map(fn($s) => sprintf('ID:%d Name:"%s"', $s->getId(), $s->getName()), $allStages))
            )
        );
        
        // Verify both contacts still reference the original stage (not duplicates)
        $updatedContact1 = $this->em->find(Lead::class, $contact1Id);
        $updatedContact2 = $this->em->find(Lead::class, $contact2Id);
        
        $this->assertNotNull($updatedContact1->getStage());
        $this->assertNotNull($updatedContact2->getStage());
        
        // Both contacts should reference the same original stage
        $this->assertSame(
            $originalStageId,
            $updatedContact1->getStage()->getId(),
            'Contact 1 should reference the original stage, not a duplicate'
        );
        $this->assertSame(
            $originalStageId,
            $updatedContact2->getStage()->getId(),
            'Contact 2 should reference the original stage, not a duplicate'
        );
        
        // Most importantly: both contacts should reference the exact same stage
        $this->assertSame(
            $updatedContact1->getStage()->getId(),
            $updatedContact2->getStage()->getId(),
            'Both contacts should reference the same stage instance'
        );
    }
}