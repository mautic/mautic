<?php

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadRepository;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LeadRepositoryFunctionalTest extends MauticMysqlTestCase
{
    public function testPointsAreAdded(): void
    {
        $model = static::getContainer()->get('mautic.lead.model.lead');

        $lead = $this->createLead();
        $lead->adjustPoints(100);

        $model->saveEntity($lead);

        $this->assertEquals(200, $lead->getPoints());

        $changes = $lead->getChanges(true);
        $this->assertEquals(200, $changes['points'][1]);
    }

    public function testPointsAreSubtracted(): void
    {
        $model = static::getContainer()->get('mautic.lead.model.lead');

        $lead = $this->createLead();
        $lead->adjustPoints(100, Lead::POINTS_SUBTRACT);

        $model->saveEntity($lead);

        $this->assertEquals(0, $lead->getPoints());

        $changes = $lead->getChanges(true);
        $this->assertEquals(0, $changes['points'][1]);
    }

    public function testPointsAreMultiplied(): void
    {
        $model = static::getContainer()->get('mautic.lead.model.lead');

        $lead = $this->createLead();
        $lead->adjustPoints(2, Lead::POINTS_MULTIPLY);

        $model->saveEntity($lead);

        $this->assertEquals(200, $lead->getPoints());

        $changes = $lead->getChanges(true);
        $this->assertEquals(200, $changes['points'][1]);
    }

    public function testPointsAreDivided(): void
    {
        $model = static::getContainer()->get('mautic.lead.model.lead');
        $lead  = $this->createLead();
        $lead->adjustPoints(2, Lead::POINTS_DIVIDE);

        $model->saveEntity($lead);

        $this->assertEquals(50, $lead->getPoints());

        $changes = $lead->getChanges(true);
        $this->assertEquals(50, $changes['points'][1]);
    }

    public function testMixedOperatorPointsAreCalculated(): void
    {
        $model = static::getContainer()->get('mautic.lead.model.lead');
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
        $model = static::getContainer()->get('mautic.lead.model.lead');
        $lead  = $this->createLead();
        $lead->adjustPoints(120, Lead::POINTS_ADD);
        $model->saveEntity($lead);
        // Changes should be stored with points
        $changes = $lead->getChanges(true);
        $this->assertEquals(220, $changes['points'][1]);
        // Points should now not be in changes
        $model->saveEntity($lead);
        $changes = $lead->getChanges(true);
        $this->assertFalse(isset($changes['points']));
        // Points should remain the same
        $model->saveEntity($lead);
        $this->em->getRepository(Lead::class)->saveEntity($lead);
        $this->assertEquals(220, $lead->getPoints());
    }

    /**
     * @param string[]|string $emails
     */
    #[\PHPUnit\Framework\Attributes\DataProvider('dataForTestAjaxGetLeadsByFieldValue')]
    public function testAjaxGetLeadsByFieldValue($emails, bool $createFlag, int $expectedCount): void
    {
        $this->createLeads($emails, $createFlag);

        $payload = [
            'action' => 'lead:getLeadIdsByFieldValue',
            'field'  => 'email',
            'value'  => $emails,
        ];

        $this->client->xmlHttpRequest(Request::METHOD_GET, '/s/ajax', $payload);
        $this->assertSame(Response::HTTP_OK, $this->client->getResponse()->getStatusCode(), print_r(json_decode($this->client->getResponse()->getContent(), true), true));
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
    private function createLeads($emails, bool $flag): void
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
