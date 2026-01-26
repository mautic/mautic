<?php

declare(strict_types=1);

namespace Mautic\LeadBundle\Tests\Command;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadList;
use Mautic\LeadBundle\Entity\LeadListRepository;
use Mautic\LeadBundle\Entity\LeadRepository;
use Mautic\LeadBundle\Entity\ListLead;
use Mautic\LeadBundle\Model\FieldModel;
use Mautic\LeadBundle\Segment\OperatorOptions;
use PHPUnit\Framework\Attributes\DataProvider;

class SegmentFiltersFunctionalTest extends MauticMysqlTestCase
{
    private const FIELD_NAME = 'car';

    protected $useCleanupRollback = false;

    private string $testIdentifier;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testIdentifier = 'test_'.uniqid();
    }

    /**
     * @param array<string, mixed> $fieldDetails
     * @param array<string, mixed> $segmentData
     *
     * @throws \Exception
     */
    #[DataProvider('filtersSegmentsContacts')]
    public function testFiltersHasCorrectContactsIncludedInSegment(
        array $fieldDetails,
        array $segmentData,
        callable $checkValidContact,
    ): void {
        $this->saveCustomField($fieldDetails);
        $contacts  = $this->saveContacts();
        $segment   = $this->saveSegment($segmentData);
        $segmentId = $segment->getId();

        /** @var array<int> $contactIds */
        $contactIds       = [];
        /** @var array<int> $leadListLeadsIds */
        $leadListLeadsIds = [];

        // get contacts with valid filter
        foreach ($contacts as $contact) {
            if ($checkValidContact($contact)) {
                $contactIds[] = (int) $contact->getId();
            }
        }

        // update the segment
        $this->testSymfonyCommand('mautic:segments:update', ['-i' => $segmentId]);

        // get the lead list leads stored in db after the segment update
        $leadListLeads = $this->em->getRepository(ListLead::class)->findBy(['list' => $segment]);
        foreach ($leadListLeads as $listLead) {
            $leadListLeadsIds[] = (int) $listLead->getLead()->getId();
        }

        sort($leadListLeadsIds);
        sort($contactIds);

        // assert filter lead ids are the same as contact ids saved in db
        $this->assertSame($leadListLeadsIds, $contactIds);
    }

    /**
     * @param array<string, mixed> $fieldDetails
     */
    private function saveCustomField(array $fieldDetails = []): void
    {
        // Create a field and add it to the lead object.
        $field = new LeadField();
        $field->setLabel($fieldDetails['label']);
        $field->setType($fieldDetails['type']);
        $field->setObject('lead');
        $field->setGroup('core');
        $field->setAlias($fieldDetails['alias']);
        $field->setProperties($fieldDetails['properties']);

        /** @var FieldModel $fieldModel */
        $fieldModel = static::getContainer()->get('mautic.lead.model.field');
        $fieldModel->saveEntity($field);
    }

    /**
     * @return array<object>
     */
    private function saveContacts(): array
    {
        $numberOfContacts               = 8;
        $numberOfContactsWithBlankValue = 2;
        /** @var LeadRepository $contactRepo */
        $contactRepo = $this->em->getRepository(Lead::class);
        $contacts    = [];
        $cars        = [
            'value1', 'value2', 'value3',
        ];
        for ($i = 1; $i <= $numberOfContacts; ++$i) {
            $contact = new Lead();
            $contact->setFirstname('Contact '.$i);
            $contact->setLastname($this->testIdentifier); // Use lastname to identify test contacts

            if ($i > $numberOfContactsWithBlankValue) {
                $contact->setFields([
                    'core' => [
                        self::FIELD_NAME => [
                            'value' => '',
                            'type'  => 'multiselect',
                            'alias' => self::FIELD_NAME,
                        ],
                    ],
                ]);
                $leadModel = static::getContainer()->get('mautic.lead.model.lead');
                $leadModel->setFieldValues($contact, [self::FIELD_NAME => [$cars[$i % 3]]]);
            }
            $contacts[] = $contact;
        }
        $contactRepo->saveEntities($contacts);

        return $contacts;
    }

    /**
     * @param array<string, mixed> $segmentData
     */
    private function saveSegment(array $segmentData = []): LeadList
    {
        /** @var LeadListRepository $segmentRepo */
        $segmentRepo = $this->em->getRepository(LeadList::class);
        $segment     = new LeadList();

        $filterToSave = $segmentData['filterToSave'];
        $filters      = [
            [
                'glue'     => 'and',
                'field'    => $filterToSave['field'],
                'object'   => 'lead',
                'type'     => 'multiselect',
                'filter'   => $filterToSave['filter'],
                'display'  => null,
                'operator' => $filterToSave['operator'],
            ],
            [
                'glue'     => 'and',
                'field'    => 'lastname',
                'object'   => 'lead',
                'type'     => 'text',
                'filter'   => $this->testIdentifier,
                'display'  => null,
                'operator' => '=',
            ],
        ];

        $segment->setName($segmentData['name'])
            ->setFilters($filters)
            ->setAlias($segmentData['alias'])
            ->setPublicName($segmentData['name']);
        $segmentRepo->saveEntity($segment);

        return $segment;
    }

    /**
     * @return iterable<int, mixed>
     */
    public static function filtersSegmentsContacts(): iterable
    {
        $customField = [
            'label'               => 'Cars',
            'alias'               => self::FIELD_NAME,
            'type'                => 'multiselect',
            'properties'          => [
                'list' => [
                    ['label' => 'car1', 'value' => 'value1'],
                    ['label' => 'car2', 'value' => 'value2'],
                    ['label' => 'car3', 'value' => 'value3'],
                ],
            ],
        ];
        $segmentData = [
            'alias'              => 'segment-a',
            'name'               => 'Segment A',
            'filterToSave'       => [
                'field'     => self::FIELD_NAME,
                'filter'    => [
                    'value1',
                ],
                'operator'  => OperatorOptions::EXCLUDING_ANY,
            ],
        ];
        // to test excluding filter, should contain blank values as well
        yield [
            // custom field
            $customField,
            $segmentData,
            function ($contact): bool {
                return empty($contact->getFields()) || 'value1' !== $contact->getField(self::FIELD_NAME)['value'];
            },
        ];

        // to test multiple excluding values
        $segmentData['filterToSave']['filter'] = ['value1', 'value2'];
        yield [
            // custom field
            $customField,
            $segmentData,
            function ($contact): bool {
                return
                    empty($contact->getFields())
                    || !in_array($contact->getField(self::FIELD_NAME)['value'], ['value1', 'value2']);
            },
        ];

        // to test including filter, should NOT contain blank values
        $segmentData['filterToSave']['operator'] = OperatorOptions::INCLUDING_ANY;
        $segmentData['filterToSave']['filter']   = ['value1'];
        yield [
            // custom field
            $customField,
            $segmentData,
            function ($contact): bool {
                return !empty($contact->getFields()) && 'value1' === $contact->getField(self::FIELD_NAME)['value'];
            },
        ];

        // to test multiple including values
        $segmentData['filterToSave']['filter'] = ['value1', 'value2'];
        yield [
            // custom field
            $customField,
            $segmentData,
            function ($contact): bool {
                return
                    !empty($contact->getFields())
                    && in_array($contact->getField(self::FIELD_NAME)['value'], ['value1', 'value2']);
            },
        ];
    }
}
