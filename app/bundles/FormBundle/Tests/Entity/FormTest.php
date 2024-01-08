<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Entity;

use Mautic\FormBundle\Entity\Field;
use Mautic\FormBundle\Entity\Form;
use PHPUnit\Framework\Assert;

final class FormTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @dataProvider setNoIndexDataProvider
     */
    public function testSetNoIndex($value, $expected, array $changes): void
    {
        $form = new Form();
        $form->setNoIndex($value);

        Assert::assertSame($expected, $form->getNoIndex());
        Assert::assertSame($changes, $form->getChanges());
    }

    public static function setNoIndexDataProvider(): iterable
    {
        yield [null, null, []];
        yield [true, true, ['noIndex' => [null, true]]];
        yield [false, false, ['noIndex' => [null, false]]];
        yield ['', false, ['noIndex' => [null, false]]];
        yield [0, false, ['noIndex' => [null, false]]];
        yield ['string', true, ['noIndex' => [null, true]]];
    }

    public function testGetMappedFieldValues(): void
    {
        $form   = $this->createForm();
        $result = [
            [
                'formFieldId'  => null,
                'mappedObject' => 'contact',
                'mappedField'  => 'email',
            ],
            [
                'formFieldId'  => null,
                'mappedObject' => 'company',
                'mappedField'  => 'companyemail',
            ],
            [
                'formFieldId'  => null,
                'mappedObject' => 'company',
                'mappedField'  => 'companyname',
            ],
        ];

        Assert::assertSame($result, $form->getMappedFieldValues());
    }

    public function testGetMappedFieldObjects(): void
    {
        $form = $this->createForm();

        Assert::assertSame(['contact', 'company'], $form->getMappedFieldObjects());
    }

    private function createForm(): Form
    {
        $form           = new Form();
        $contactField   = new Field();
        $companyFieldA  = new Field();
        $companyFieldB  = new Field();
        $notMappedField = new Field();
        $contactField->setMappedObject('contact');
        $contactField->setMappedField('email');
        $companyFieldA->setMappedObject('company');
        $companyFieldA->setMappedField('companyemail');
        $companyFieldB->setMappedObject('company');
        $companyFieldB->setMappedField('companyname');
        $form->addField('contact_field_a', $contactField);
        $form->addField('company_field_a', $companyFieldA);
        $form->addField('company_field_b', $companyFieldB);
        $form->addField('not_mapped_field_a', $notMappedField);

        return $form;
    }
}
