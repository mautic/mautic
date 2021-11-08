<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Controller;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Model\LeadModel;
use Mautic\LeadBundle\Model\ListModel;
use PHPUnit\Framework\Assert;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ListControllerFunctionalTest extends MauticMysqlTestCase
{
    /**
     * @var ListModel
     */
    protected $listModel;

    /**
     * @var LeadListRepository
     */
    protected $listRepo;

    private FieldModel $fieldModel;

    private LeadModel $leadModel;

    protected function setUp(): void
    {
        parent::setUp();
        /* @var ListModel $listModel */
        $this->listModel = self::$container->get('mautic.lead.model.list');
        /* @var LeadListRepository listRepo */
        $this->listRepo = $this->listModel->getRepository();

        $this->fieldModel = self::$container->get('mautic.lead.model.field');
        $this->leadModel  = self::$container->get('mautic.lead.model.lead');
    }

    public function testSegmentDateTimeFieldDayMonthOperator(): void
    {
        $application = new Application(self::$kernel);
        $application->setAutoExit(false);
        $applicationTester = new ApplicationTester($application);

        $dateTimeFieldAlias = 'datetime_field1';
        if (!$this->fieldModel->getRepository()->findOneBy(['alias' => $dateTimeFieldAlias])) {
            $field = new LeadField();
            $field->setName('Datetime Field')
                ->setAlias($dateTimeFieldAlias)
                ->setType('datetime')
                ->setObject('lead');

            $this->fieldModel->saveEntity($field);
        }

        $dateTimeValues = [
            (new \DateTime())->format('Y-m-d 00:00:00'),
            (new \DateTime('+1 month'))->format('Y-m-d 00:00:00'),
            (new \DateTime('-1  month'))->modify('-1 day')->format('Y-m-d 00:00:00'),
        ];
        $contacts = [];
        foreach ($dateTimeValues as $dateTimeValue) {
            $contact = new Lead();
            $this->leadModel->setFieldValues($contact, [$dateTimeFieldAlias => $dateTimeValue]);
            $this->leadModel->saveEntity($contact);
        }

        $filter = [[
            'glue'     => 'and',
            'field'    => $dateTimeFieldAlias,
            'object'   => 'lead',
            'type'     => 'datetime',
            'operator' => '=',
            'display'  => '',
            'filter'   => 'month',
        ]];

        $segmentMonthOperator  = $this->saveSegment('segmentMonthOperator', 'segmentMonthOperator', $filter);

        $filter = [[
            'glue'     => 'and',
            'field'    => $dateTimeFieldAlias,
            'object'   => 'lead',
            'type'     => 'datetime',
            'operator' => '=',
            'display'  => '',
            'filter'   => 'day',
        ]];

        $segmentDayOperator  = $this->saveSegment('segmentDayOperator', 'segmentDayOperator', $filter);

        $this->em->clear();
        // Execute the campaign.
        $exitCode = $applicationTester->run(
            [
                'command'       => 'mautic:segment:rebuild',
            ]
        );
        Assert::assertSame(0, $exitCode, $applicationTester->getDisplay());
        $segmentCounts = $this->listModel->getRepository()->getLeadCount([$segmentMonthOperator->getId(), $segmentDayOperator->getId()]);
        Assert::assertEquals(1, $segmentCounts[$segmentMonthOperator->getId()]);
        Assert::assertEquals(2, $segmentCounts[$segmentDayOperator->getId()]);
    }

    public function testUnpublishUsedSegment(): void
    {
        $filter = [[
            'glue'     => 'and',
            'field'    => 'email',
            'object'   => 'lead',
            'type'     => 'email',
            'operator' => '!empty',
            'display'  => '',
        ]];
        $list1  = $this->saveSegment('s1', 's1', $filter);
        $filter = [[
            'object'     => 'lead',
            'glue'       => 'and',
            'field'      => 'leadlist',
            'type'       => 'leadlist',
            'operator'   => 'in',
            'properties' => [
                'filter' => [$list1->getId()],
            ],
            'display' => '',
        ]];
        $list2 = $this->saveSegment('s2', 's2', $filter);
        $this->em->clear();
        $expectedErrorMessage = sprintf('This segment is used in %s, please go back and check segments before unpublishing', $list2->getName());

        $crawler = $this->client->request(Request::METHOD_POST, '/s/ajax', ['action' => 'togglePublishStatus', 'model' => 'lead.list', 'id' => $list1->getId()]);
        $this->assertSame(Response::HTTP_UNPROCESSABLE_ENTITY, $this->client->getResponse()->getStatusCode());
        $this->assertStringContainsString($expectedErrorMessage, $this->client->getResponse()->getContent());
        $this->client->restart();
        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments/edit/'.$list1->getId());
        $form    = $crawler->selectButton('leadlist_buttons_apply')->form();
        $form['leadlist[isPublished]']->setValue('');
        $crawler = $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk());
        $this->assertStringContainsString($expectedErrorMessage, $this->client->getResponse()->getContent());
    }

    public function testUnpublishUnUsedSegment(): void
    {
        $filter = [[
            'glue'     => 'and',
            'field'    => 'email',
            'object'   => 'lead',
            'type'     => 'email',
            'operator' => '!empty',
            'display'  => '',
        ]];
        $list1 = $this->saveSegment('s1', 's1', $filter);
        $list2 = $this->saveSegment('s2', 's2', $filter);
        $this->em->clear();

        $crawler = $this->client->request(Request::METHOD_POST, '/s/ajax', ['action' => 'togglePublishStatus', 'model' => 'lead.list', 'id' => $list1->getId()]);
        $this->assertTrue($this->client->getResponse()->isOk());

        $crawler = $this->client->request(Request::METHOD_GET, '/s/segments/edit/'.$list2->getId());
        $form    = $crawler->selectButton('leadlist_buttons_apply')->form();
        $form['leadlist[isPublished]']->setValue('');
        $crawler = $this->client->submit($form);
        $this->assertTrue($this->client->getResponse()->isOk());

        $rows = $this->listRepo->findAll();
        $this->assertCount(2, $rows);
        $this->assertFalse($rows[0]->isPublished());
        $this->assertFalse($rows[1]->isPublished());
    }

    private function saveSegment(string $name, string $alias, array $filters = [], LeadList $segment = null): LeadList
    {
        $segment = $segment ?? new LeadList();
        $segment->setName($name)->setAlias($alias)->setFilters($filters);
        $this->listModel->saveEntity($segment);

        return $segment;
    }
}
