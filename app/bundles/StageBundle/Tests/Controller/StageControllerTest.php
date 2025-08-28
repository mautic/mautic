<?php

declare(strict_types=1);

namespace Mautic\StageBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\StageBundle\Entity\Stage;

final class StageControllerTest extends MauticMysqlTestCase
{
    public function testIndexDisplaysContactCounts(): void
    {
        $stageA = (new Stage())
            ->setName('Stage A')
            ->setIsPublished(true);
        $stageB = (new Stage())
            ->setName('Stage B')
            ->setIsPublished(true);

        $this->em->persist($stageA);
        $this->em->persist($stageB);
        $this->em->flush();

        $lead1 = (new Lead())
            ->setFirstname('John')
            ->setLastname('Doe')
            ->setEmail('john@example.com')
            ->setStage($stageA);
        $lead2 = (new Lead())
            ->setFirstname('Jane')
            ->setLastname('Roe')
            ->setEmail('jane@example.com')
            ->setStage($stageA);

        $this->em->persist($lead1);
        $this->em->persist($lead2);
        $this->em->flush();

        $this->client->request('GET', '/s/stages');
        $response = $this->client->getResponse();

        self::assertResponseIsSuccessful();
        $content = $response->getContent();
        self::assertStringContainsString('View 2 Contacts', $content);
        self::assertStringContainsString('No Contacts', $content);
    }
}
