<?php

namespace Mautic\LeadBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LeadRepositoryFunctionalTest extends MauticMysqlTestCase
{
    private Lead $lead;

    protected function setUp(): void
    {
        parent::setUp();

        $this->lead = $this->createLead();
    }

    public function testPointsAreAdded(): void
    {
        $model = static::getContainer()->get('mautic.lead.model.lead');

        $this->lead->adjustPoints(100);

        $model->saveEntity($this->lead);

        $this->assertEquals(200, $this->lead->getPoints());

        $changes = $this->lead->getChanges(true);
        $this->assertEquals(200, $changes['points'][1]);
    }

    public function testPointsAreSubtracted(): void
    {
        $model = static::getContainer()->get('mautic.lead.model.lead');

        $this->lead->adjustPoints(100, Lead::POINTS_SUBTRACT);

        $model->saveEntity($this->lead);

        $this->assertEquals(0, $this->lead->getPoints());

        $changes = $this->lead->getChanges(true);
        $this->assertEquals(0, $changes['points'][1]);
    }

    public function testPointsAreMultiplied(): void
    {
        $model = static::getContainer()->get('mautic.lead.model.lead');

        $this->lead->adjustPoints(2, Lead::POINTS_MULTIPLY);

        $model->saveEntity($this->lead);

        $this->assertEquals(200, $this->lead->getPoints());

        $changes = $this->lead->getChanges(true);
        $this->assertEquals(200, $changes['points'][1]);
    }

    public function testPointsAreDivided(): void
    {
        $model = static::getContainer()->get('mautic.lead.model.lead');

        $this->lead->adjustPoints(2, Lead::POINTS_DIVIDE);

        $model->saveEntity($this->lead);

        $this->assertEquals(50, $this->lead->getPoints());

        $changes = $this->lead->getChanges(true);
        $this->assertEquals(50, $changes['points'][1]);
    }

    public function testMixedOperatorPointsAreCalculated(): void
    {
        $model = static::getContainer()->get('mautic.lead.model.lead');

        $this->lead->adjustPoints(100, Lead::POINTS_SUBTRACT);
        $this->lead->adjustPoints(120, Lead::POINTS_ADD);
        $this->lead->adjustPoints(2, Lead::POINTS_MULTIPLY);
        $this->lead->adjustPoints(4, Lead::POINTS_DIVIDE);

        $model->saveEntity($this->lead);

        $this->assertEquals(60, $this->lead->getPoints());

        $changes = $this->lead->getChanges(true);
        $this->assertEquals(60, $changes['points'][1]);
    }

    public function testMixedModelAndRepositorySavesDoNotDoublePoints(): void
    {
        $model = static::getContainer()->get('mautic.lead.model.lead');
        $this->lead->adjustPoints(120, Lead::POINTS_ADD);
        $model->saveEntity($this->lead);
        // Changes should be stored with points
        $changes = $this->lead->getChanges(true);
        $this->assertEquals(220, $changes['points'][1]);
        // Points should now not be in changes
        $model->saveEntity($this->lead);
        $changes = $this->lead->getChanges(true);
        $this->assertFalse(isset($changes['points']));
        // Points should remain the same
        $model->saveEntity($this->lead);
        $this->em->getRepository(Lead::class)->saveEntity($this->lead);
        $this->assertEquals(220, $this->lead->getPoints());
    }

    /**
     * @param string[]|string $emails
     *
     * @dataProvider dataForTestAjaxGetLeadsByFieldValue
     */
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
     * @return array<string, array<int, int|string|bool|string[]>>
     */
    public function dataForTestAjaxGetLeadsByFieldValue(): iterable
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
