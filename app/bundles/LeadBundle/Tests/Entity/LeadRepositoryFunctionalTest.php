<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Model\LeadModel;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\HttpFoundation\Request;

final class LeadRepositoryFunctionalTest extends MauticMysqlTestCase
{
    private Lead $lead;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lead = $this->createLead();
    }

    public function testPointsAreAdded(): void
    {
        /** @var LeadModel $model */
        $model = self::getContainer()->get(LeadModel::class);

        $lead = $this->createLead();
        $lead->adjustPoints(100);

        $model->saveEntity($lead);

        $this->assertEquals(200, $lead->getPoints());

        $changes = $lead->getChanges(true);
        $this->assertEquals(200, $changes['points'][1]);
    }

    public function testPointsAreSubtracted(): void
    {
        /** @var LeadModel $model */
        $model = self::getContainer()->get(LeadModel::class);

        $lead = $this->createLead();
        $lead->adjustPoints(100, Lead::POINTS_SUBTRACT);

        $model->saveEntity($lead);

        $this->assertEquals(0, $lead->getPoints());

        $changes = $lead->getChanges(true);
        $this->assertEquals(0, $changes['points'][1]);
    }

    public function testPointsAreMultiplied(): void
    {
        /** @var LeadModel $model */
        $model = self::getContainer()->get(LeadModel::class);

        $lead = $this->createLead();
        $lead->adjustPoints(2, Lead::POINTS_MULTIPLY);

        $model->saveEntity($lead);

        $this->assertEquals(200, $lead->getPoints());

        $changes = $lead->getChanges(true);
        $this->assertEquals(200, $changes['points'][1]);
    }

    public function testPointsAreDivided(): void
    {
        /** @var LeadModel $model */
        $model = self::getContainer()->get(LeadModel::class);
        $lead  = $this->createLead();
        $lead->adjustPoints(2, Lead::POINTS_DIVIDE);

        $model->saveEntity($lead);

        $this->assertEquals(50, $lead->getPoints());

        $changes = $lead->getChanges(true);
        $this->assertEquals(50, $changes['points'][1]);
    }

    public function testMixedOperatorPointsAreCalculated(): void
    {
        /** @var LeadModel $model */
        $model = self::getContainer()->get(LeadModel::class);

        $lead  = $this->createLead();
        // PostgreSQL strictly forbids multiple assignments to the same column
        // in one SET clause → throws:
        // ERROR: multiple assignments to same column "points"
        // need flush after each change
        $lead->adjustPoints(100, Lead::POINTS_SUBTRACT);
        $model->saveEntity($lead);   // flush #1

        $lead->adjustPoints(120, Lead::POINTS_ADD);
        $model->saveEntity($lead);   // flush #2

        $lead->adjustPoints(2, Lead::POINTS_MULTIPLY);
        $model->saveEntity($lead);   // flush #3

        $lead->adjustPoints(4, Lead::POINTS_DIVIDE);
        $model->saveEntity($lead);  // flush #4

        $this->assertEquals(60, $lead->getPoints());

        $changes = $lead->getChanges(true);
        $this->assertEquals(60, $changes['points'][1]);
    }

    public function testMixedModelAndRepositorySavesDoNotDoublePoints(): void
    {
        /** @var LeadModel $model */
        $model = static::getContainer()->get(LeadModel::class);
        $lead  = $this->createLead();
        $lead->adjustPoints(120, Lead::POINTS_ADD);
        $model->saveEntity($lead);
        // Changes should be stored with points
        $changes = $lead->getChanges(true);
        $this->assertEquals(220, $changes['points'][1]);
        // Points should now not be in changes
        $model->saveEntity($lead);
        $changes = $lead->getChanges(true);
        $this->assertArrayNotHasKey('points', $changes);
        // Points should remain the same
        $model->saveEntity($lead);
        $this->em->getRepository(Lead::class)->saveEntity($lead);
        $this->assertEquals(220, $lead->getPoints());
    }

    /**
     * @param mixed[] $contactIds
     */
    #[DataProvider('dataForGetContacts')]
    public function testGetContacts(array $contactIds, bool $includeLead, int $expectedCount): void
    {
        if ($includeLead) {
            $contactIds[] = $this->lead->getId();
        }

        /** @var LeadRepository $repo */
        $repo     = $this->em->getRepository(Lead::class);
        $contacts = $repo->getContacts($contactIds);

        $this->assertCount($expectedCount, $contacts);
    }

    /**
     * @return iterable<string, mixed>
     */
    public static function dataForGetContacts(): iterable
    {
        yield 'No ids' => [
            [],
            false,
            0,
        ];

        yield 'Random ids only' => [
            [99999, 0],
            false,
            0,
        ];

        yield 'Random ids with lead' => [
            [99999],
            true,
            1,
        ];
    }

    /**
     * @param string[]|string $emails
     */
    #[DataProvider('dataForTestAjaxGetLeadsByFieldValue')]
    public function testAjaxGetLeadsByFieldValue(string|array $emails, bool $createFlag, int $expectedCount): void
    {
        $this->createLeads($emails, $createFlag);

        $payload = [
            'action' => 'lead:getLeadIdsByFieldValue',
            'field'  => 'email',
            'value'  => $emails,
        ];

        $this->client->xmlHttpRequest(Request::METHOD_GET, '/s/ajax', $payload);
        $this->assertResponseIsSuccessful();
        $contentArray = json_decode($this->client->getResponse()->getContent(), true);

        $this->assertCount($expectedCount, $contentArray['items']);
    }

    /**
     * @return iterable<string, array<int, int|string|bool|string[]>>
     */
    public static function dataForTestAjaxGetLeadsByFieldValue(): iterable
    {
        yield 'Email passed as string with associated contact' => [
            'john@doe.com', // Email
            true,
            1, // Count
        ];

        yield 'Email passed as string without associated contact' => [
            'john@doe.com', // Email
            false,
            0, // Count
        ];

        yield 'Email passed as array with associated contacts' => [
            ['john@doe.com', 'doe@doe.com'], // Email
            true,
            2, // Count
        ];

        yield 'Email passed as array without associated contacts' => [
            ['john@doe.com', 'doe@doe.com'], // Email
            false,
            0, // Count
        ];
    }

    /**
     * @param string[]|string $emails
     */
    private function createLeads(string|array $emails, bool $flag): void
    {
        if (!$flag) {
            return;
        }

        if (!is_array($emails)) {
            $emails = [$emails];
        }

        foreach ($emails as $email) {
            $this->createLead($email);
        }
    }

    public function testIfLeadExists(): void
    {
        /** @var LeadRepository $repo */
        $repo = $this->em->getRepository(Lead::class);

        $this->assertFalse($repo->exists('654'));

        $lead = $this->createLead();

        $this->assertTrue($repo->exists((string) $lead->getId()));
    }

    private function createLead(string $email = ''): Lead
    {
        $lead = new Lead();
        $lead->setPoints(100);

        if ($email) {
            $lead->setEmail($email);
        }

        $this->em->persist($lead);
        $this->em->flush();

        return $lead;
    }
}
