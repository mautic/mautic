<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\EventListener;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\ReportBundle\Entity\Report;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;

class ReportNormalizeSubscriberTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    /**
     * @dataProvider normalizeData
     *
     * @param array<int, array<string, array<string, array<string, array<int,string>>|string>|string>> $properties
     */
    public function testOnReportDisplay(string $value, string $type, array $properties, string $expected): void
    {
        $fieldModel = static::getContainer()->get('mautic.lead.model.field');
        \assert($fieldModel instanceof FieldModel);
        $field = new LeadField();
        $field->setType($type);
        $field->setObject('lead');
        $field->setAlias('field1');
        $field->setName($field->getAlias());
        $field->setProperties($properties);
        $fieldModel->saveEntity($field);

        $contact = new Lead();
        $contact->setEmail('contact@example.com');
        $contact->addUpdatedField('field1', $value);
        $contactModel = self::getContainer()->get(LeadModel::class);
        \assert($contactModel instanceof LeadModel);
        $contactModel->saveEntity($contact);

        $report = new Report();
        $report->setName('report subscriber test');
        $report->setColumns([
            'l.email',
            'l.field1',
        ]);
        $report->setSource('leads');
        $this->em->persist($report);
        $this->em->flush();
        $this->em->clear();

        $crawler            = $this->client->request(Request::METHOD_GET, "/s/reports/view/{$report->getId()}");
        $this->assertTrue($this->client->getResponse()->isOk());
        $crawlerReportTable = $crawler->filterXPath('//table[@id="reportTable"]')->first();

        // convert html table to php array
        $crawlerReportTable = array_slice($this->domTableToArray($crawlerReportTable), 1, 1);

        $this->assertSame([
            // no., email, expected
            ['1', 'contact@example.com', $expected],
        ], $crawlerReportTable);

        // Test API response
        $this->client->request(Request::METHOD_GET, "/api/reports/{$report->getId()}");
        $clientResponse = $this->client->getResponse();
        $result         = json_decode($clientResponse->getContent(), true);
        $this->assertEquals(1, $result['totalResults']);
        $this->assertEquals([
            [
                'email'  => 'contact@example.com',
                'field1' => $expected,
            ],
        ], $result['data']);
    }

    /**
     * @return array<int, array<string, array<string, array<string, array<int,string>>|string>|string>> $properties
     */
    public function normalizeData(): array
    {
        return [
            // Test for boolean custom field
            [
                'value'      => '1',
                'type'       => 'boolean',
                'properties' => [
                    'yes' => 'True',
                    'no'  => 'False',
                ],
                'expected'   => 'True',
            ],
            [
                'value'      => '0',
                'type'       => 'boolean',
                'properties' => [
                    'yes' => 'True',
                    'no'  => 'False',
                ],
                'expected'   => 'False',
            ],

            // Test for select custom field
            [
                'value'      => '2',
                'type'       => 'select',
                'properties' => [
                    'list' => [
                        'list' => [
                            1 => 'Option 1',
                            2 => 'Option 2',
                        ],
                    ],
                ],
                'expected'   => 'Option 2',
            ],

            // Test for multiselect custom field
            [
                'value'      => '1|3',
                'type'       => 'multiselect',
                'properties' => [
                    'list' => [
                        'list' => [
                            1 => 'Option 1',
                            2 => 'Option 2',
                            3 => 'Option 3',
                        ],
                    ],
                ],
                'expected'   => 'Option 1|Option 3',
            ],
        ];
    }

    /**
     * @return array<int,array<int,mixed>>
     */
    private function domTableToArray(Crawler $crawler): array
    {
        return $crawler->filter('tr')->each(fn ($tr) => $tr->filter('td')->each(fn ($td) => trim($td->text())));
    }
}
