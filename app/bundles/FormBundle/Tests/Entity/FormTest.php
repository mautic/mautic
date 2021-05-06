<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Entity;

use Mautic\FormBundle\Entity\Form;
use PHPUnit\Framework\Assert;

class FormTest extends \PHPUnit\Framework\TestCase
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
}
