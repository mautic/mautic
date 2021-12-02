<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\HttpFoundation\Request;

final class OperatorIncludeExcludeFunctionalTest extends MauticMysqlTestCase
{
    /**
     * The include/exclude operator should return the multiselect field.
     *
     * @dataProvider dataCustomFields
     *
     * @param array<string, string> $fieldDetails
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
     * @return iterable<mixed>
     */
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
