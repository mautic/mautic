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

    public function setNoIndexDataProvider(): iterable
    {
        yield [null, null, []];
        yield [true, true, ['noIndex' => [null, true]]];
        yield [false, false, ['noIndex' => [null, false]]];
        yield ['', false, ['noIndex' => [null, false]]];
        yield [0, false, ['noIndex' => [null, false]]];
        yield ['string', true, ['noIndex' => [null, true]]];
    }

    public function testGetMappedFieldObjects(): void
    {
        $form           = new Form();
        $contactField   = new Field();
        $companyFieldA  = new Field();
        $companyFieldB  = new Field();
        $notMappedField = new Field();
        $contactField->setMappedObject('contact');
        $companyFieldA->setMappedObject('company');
        $companyFieldB->setMappedObject('company');
        $form->addField('contact_field_a', $contactField);
        $form->addField('company_field_a', $companyFieldA);
        $form->addField('company_field_b', $companyFieldB);
        $form->addField('not_mapped_field_a', $notMappedField);

        Assert::assertSame(['contact', 'company'], $form->getMappedFieldObjects());
    }
}
