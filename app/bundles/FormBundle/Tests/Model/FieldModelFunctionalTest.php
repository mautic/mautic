<?php

namespace Mautic\FormBundle\Tests\Model;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\LeadBundle\Entity\LeadField;
use Mautic\LeadBundle\Entity\LeadFieldRepository;

class FieldModelFunctionalTest extends MauticMysqlTestCase
{
    public function testGetObjectFieldsUnpublishedField(): void
    {
        /** @var \Mautic\FormBundle\Model\FieldModel $fieldModel */
        $fieldModel   = self::$container->get('mautic.form.model.field');
        $fieldsBefore = $fieldModel->getObjectFields('lead');

        /** @var LeadFieldRepository $leadFieldRepository */
        $leadFieldRepository = $this->em->getRepository(LeadField::class);
        $field               = $leadFieldRepository->findOneBy(['alias' => 'firstname']);
        $field->setIsPublished(false);
        $leadFieldRepository->saveEntity($field);

        $fieldsAfter = $fieldModel->getObjectFields('lead');

        self::assertTrue(array_key_exists('firstname', array_flip($fieldsBefore[1]['Core'])));
        self::assertFalse(array_key_exists('firstname', array_flip($fieldsAfter[1]['Core'])));
    }
}
