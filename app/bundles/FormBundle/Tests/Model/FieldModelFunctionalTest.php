<?php

namespace Mautic\FormBundle\Tests\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;

class FieldModelFunctionalTest extends MauticMysqlTestCase
{
    public function testGetObjectFieldsUnpublishedField(): void
    {
        /** @var \Mautic\FormBundle\Model\FieldModel $fieldModel */
        $fieldModel   = self::$container->get('mautic.form.model.field');
        $fieldsBefore = $fieldModel->getObjectFields('lead');

        $leadFieldModel = self::$container->get('mautic.lead.model.field');
        $field          = $leadFieldModel->getRepository()->findOneBy(['alias' => 'firstname']);
        $field->setIsPublished(false);
        $leadFieldModel->saveEntity($field);

        $fieldsAfter = $fieldModel->getObjectFields('lead');

        self::assertTrue(array_key_exists('firstname', array_flip($fieldsBefore[1]['Core'])));
        self::assertFalse(array_key_exists('firstname', array_flip($fieldsAfter[1]['Core'])));
    }
}
