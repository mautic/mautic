<?php

namespace Mautic\FormBundle\Tests\Model;

use Doctrine\DBAL\Schema\Column;
use Mautic\CoreBundle\Doctrine\Helper\ColumnSchemaHelper;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\FormBundle\Entity\Form;
use Mautic\FormBundle\Model\FieldModel;
use Mautic\FormBundle\Model\FormModel;
use Symfony\Component\HttpFoundation\Request;

class FieldModelFunctionalTest extends MauticMysqlTestCase
{
    protected $useCleanupRollback = false;

    public function testDeleteFormFieldShouldRemoveTableColumn(): void
    {
        $formData = [
            'name'        => 'FormTest',
            'description' => 'Form created via submission test',
            'formType'    => 'standalone',
            'isPublished' => true,
            'fields'      => [
                [
                    'label'        => 'Email',
                    'type'         => 'email',
                    'alias'        => 'email',
                    'mappedObject' => 'contact',
                    'mappedField'  => 'email',
                ],
                [
                    'label'        => 'First name',
                    'type'         => 'text',
                    'alias'        => 'fname',
                ],
                [
                    'label'        => 'Last name',
                    'type'         => 'text',
                    'alias'        => 'lname',
                ],
                [
                    'label' => 'Submit',
                    'type'  => 'button',
                ],
            ],
        ];

        // Create form.
        $form = $this->createForm($formData);

        /** @var ColumnSchemaHelper $helper */
        $helper = $this->getContainer()->get('mautic.schema.helper.column');

        // Table name to check the fields.
        $name         = 'form_results_'.$form->getId().'_'.$form->getAlias();
        $schemaHelper = $helper->setName($name);

        // The table will have four column, 'submission_id', 'form_id', 'email', and 'fname'.
        $this->assertEquals(5, count($schemaHelper->getColumns()));

        /** @var FieldModel $fieldModel */
        $fieldModel = $this->getContainer()->get('mautic.form.model.field');

        $ids = $this->getDeleteIds($fieldModel);

        // Let's delete the 'First name' field.
        $fieldModel->deleteEntities($ids);

        $this->assertTrue($schemaHelper->checkColumnExists('email'), 'The table has \'email\' field.');
        $this->assertFalse($schemaHelper->checkColumnExists('fname'), 'The table does not have the \'fname\' field.');
        $this->assertFalse($schemaHelper->checkColumnExists('lname'), 'The table does not have the \'lname\' field.');
    }

    /**
     * @param mixed[] $payload
     */
    private function createForm(array $payload): Form
    {
        $this->client->request(Request::METHOD_POST, '/api/forms/new', $payload);
        $clientResponse = $this->client->getResponse();
        $response       = json_decode($clientResponse->getContent(), true);

        /** @var FormModel $formModel */
        $formModel = $this->getContainer()->get('mautic.form.model.form');

        return $formModel->getEntity($response['form']['id']);
    }

    /**
     * @return mixed[]
     */
    private function getDeleteIds(FieldModel $fieldModel): array
    {
        $fields = $fieldModel->getEntities([
            'filter' => [
                'force' => [
                    [
                        'column' => $fieldModel->getRepository()->getTableAlias().'.alias',
                        'expr'   => 'in',
                        'value'  => ['fname', 'lname'],
                    ],
                ],
            ],
            'ignore_paginator' => true,
        ]);

        $ids = [];
        foreach ($fields as $field) {
            $ids[] = $field->getId();
        }

        return $ids;
    }
}
