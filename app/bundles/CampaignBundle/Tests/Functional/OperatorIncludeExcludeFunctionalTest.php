<?php

namespace Mautic\CampaignBundle\Tests\Functional;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Model\FieldModel;
use Symfony\Component\HttpFoundation\Request;

class OperatorIncludeExcludeFunctionalTest extends MauticMysqlTestCase
{
    /**
     * The include/exclude operator should retuen the multiselect field.
     *
     * @dataProvider dataCustomFields
     */
    public function testOperatorConditionIncludeExclude(array $fieldDetails, string $expectedFieldType): void
    {
        $this->createField($fieldDetails['type'], $fieldDetails['alias']);

        $payload = [
            'action'            => 'lead:updateLeadFieldValues',
            'alias'             => 'test_multiselect_field',
            'operator'          => 'in',
            'changed'           => 'operator',
        ];

        $this->client->request(Request::METHOD_POST, '/s/ajax', $payload, [], $this->createAjaxHeaders());
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);
        $this->assertTrue($clientResponse->isOk());
        $this->assertSame($expectedFieldType, $response['fieldType']);
    }

    public function dataCustomFields(): iterable
    {
        yield [
            [
                'type'      => 'multiselect',
                'alias'     => 'test_multiselect_field',
                'operator'  => '!in',
            ],
            'multiselect',
        ];
        yield [
            [
                'type'      => 'text',
                'alias'     => 'test_text_field',
                'operator'  => 'in',
            ],
            'multiselect',
        ];
    }

    private function createField(string $type, string $alias): void
    {
        $field = new LeadField();
        $field->setType($type);
        $field->setObject('lead');
        $field->setAlias($alias);
        $field->setName($alias);

        $fields[] = $field;

        /** @var FieldModel $fieldModel */
        $fieldModel = $this->container->get('mautic.lead.model.field');
        $fieldModel->saveEntities($fields);
    }
}
