<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\HttpFoundation\Request;

final class OperatorIncludeExcludeFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    /**
     * The include/exclude operator should return the multiselect field.
     *
     * @dataProvider dataCustomFields
     */
    public function testOperatorConditionIncludeExclude(array $fieldDetails, string $expectedFieldType): void
    {
        $this->createField($fieldDetails['type'], $fieldDetails['alias']);

        $payload = [
            'action'            => 'lead:updateLeadFieldValues',
            'alias'             => $fieldDetails['alias'],
            'operator'          => $fieldDetails['operator'],
            'changed'           => 'operator',
        ];

        $this->client->request(Request::METHOD_POST, '/s/ajax', $payload, [], $this->createAjaxHeaders());
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $this->assertTrue($clientResponse->isOk());
        $this->assertSame($expectedFieldType, $response['fieldType']);
    }

    /**
     * @dataProvider dataCustomFieldsOperators
     *
     * @param array<mixed>  $fieldDetails
     * @param array<string> $expectedOperators
     * @param array<string> $unExpectedOperators
     */
    public function testOperatorsAreCorrectOnFieldChange(array $fieldDetails, array $expectedOperators, array $unExpectedOperators): void
    {
        $this->createField($fieldDetails['type'], $fieldDetails['alias']);

        $payload = [
            'action'            => 'lead:updateLeadFieldValues',
            'alias'             => $fieldDetails['alias'],
            'operator'          => $fieldDetails['operator'],
            'changed'           => 'field',
        ];

        $this->client->request(Request::METHOD_POST, '/s/ajax', $payload, [], $this->createAjaxHeaders());
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $this->assertTrue($clientResponse->isOk());

        array_map(function ($operator) use ($response) {
            $this->assertTrue(array_key_exists($operator, $response['operators']));
        }, $expectedOperators);

        array_map(function ($operator) use ($response) {
            $this->assertFalse(array_key_exists($operator, $response['operators']));
        }, $unExpectedOperators);
    }

    public function dataCustomFields(): iterable
    {
        yield [
            [
                'type'      => 'multiselect',
                'alias'     => 'test_multiselect_field',
                'operator'  => 'in',
            ],
            'multiselect',
        ];
        yield [
            [
                'type'      => 'multiselect',
                'alias'     => 'test_multiselect_field_one',
                'operator'  => '!in',
            ],
            'multiselect',
        ];
        yield [
            [
                'type'      => 'text',
                'alias'     => 'test_text_field',
                'operator'  => 'like',
            ],
            'text',
        ];
        yield [
            [
                'type'      => 'date',
                'alias'     => 'test_date_field',
                'operator'  => 'gt',
            ],
            'date',
        ];
        yield [
            [
                'type'      => 'datetime',
                'alias'     => 'test_date_field_between',
                'operator'  => 'between',
            ],
            'datetime',
        ];
    }

    /**
     * @return array<mixed> iterable
     */
    public function dataCustomFieldsOperators(): iterable
    {
        yield [
            [
                'type'      => 'date',
                'alias'     => 'test_date_field',
                'operator'  => 'gt',
            ],
            [
                'After',
                'After (Including day)',
                'Before',
                'Before (Including day)',
                'date',
                'between',
                'not between',
            ],
            [
              'including',
            ],
        ];
        yield [
            [
                'type'      => 'text',
                'alias'     => 'test_text_field',
                'operator'  => 'like',
            ],
            [
                'equals',
                'regexp',
            ],
            [
                'After',
                'After (Including day)',
                'Before',
                'Before (Including day)',
                'date',
                'between',
                'not between',
            ],
        ];
    }

    private function createField(string $type, string $alias): void
    {
        $field = new LeadField();
        $field->setType($type);
        $field->setObject('lead');
        $field->setAlias($alias);
        $field->setName($alias);

        /** @var FieldModel $fieldModel */
        $fieldModel = self::$container->get('mautic.lead.model.field');
        $fieldModel->saveEntity($field);
    }
}
