<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\StageBundle\Entity\Stage;
use Symfony\Component\HttpFoundation\Request;

final class StageSearchFunctionalTest extends MauticMysqlTestCase
{
    public function testStageIdSearchFiltersContactsByStageIdWhenNamesMatch(): void
    {
        $firstStage  = $this->createStage('Shared stage');
        $secondStage = $this->createStage('Shared stage');
        $this->em->flush();

        $firstStageSearch = 'stage_id%3A'.$firstStage->getId();
        $firstContact     = $this->createContact('first-stage-contact@example.test', $firstStage);
        $secondContact    = $this->createContact('second-stage-contact@example.test', $secondStage);
        $noStageContact   = $this->createContact('no-stage-contact@example.test', null);
        $this->em->flush();
        $this->em->clear();

        $leadRepository = $this->em->getRepository(Lead::class);
        $this->assertInstanceOf(LeadRepository::class, $leadRepository);
        $this->assertContains('mautic.lead.lead.searchcommand.stage_id', $leadRepository->getSearchCommands());

        $this->assertSearchResult($firstStageSearch, [$firstContact], [$secondContact, $noStageContact]);
        $this->assertSearchResult('!'.$firstStageSearch, [$secondContact, $noStageContact], [$firstContact]);
    }

    private function createStage(string $name): Stage
    {
        $stage = new Stage();
        $stage->setName($name);
        $stage->setIsPublished(true);
        $this->em->persist($stage);

        return $stage;
    }

    private function createContact(string $email, ?Stage $stage): Lead
    {
        $contact = new Lead();
        $contact->setEmail($email);
        $contact->setDateIdentified(new \DateTime());
        $contact->setStage($stage);
        $this->em->persist($contact);

        return $contact;
    }

    /**
     * @param Lead[] $expectedContacts
     * @param Lead[] $notExpectedContacts
     */
    private function assertSearchResult(string $search, array $expectedContacts, array $notExpectedContacts): void
    {
        $crawler = $this->client->request(Request::METHOD_GET, '/s/contacts?search='.$search);
        self::assertResponseIsSuccessful();
        $responseText = $crawler->text();

        foreach ($expectedContacts as $expectedContact) {
            $this->assertStringContainsString($expectedContact->getEmail(), $responseText);
        }

        foreach ($notExpectedContacts as $notExpectedContact) {
            $this->assertStringNotContainsString($notExpectedContact->getEmail(), $responseText);
        }
    }
}
