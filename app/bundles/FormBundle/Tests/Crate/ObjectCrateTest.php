<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Crate;

use Mautic\FormBundle\Crate\ObjectCrate;
use PHPUnit\Framework\Assert;

final class ObjectCrateTest extends \PHPUnit\Framework\TestCase
{
    public function testGetters(): void
    {
        $field = new ObjectCrate('contact', 'Contact');

        Assert::assertSame('contact', $field->getKey());
        Assert::assertSame('Contact', $field->getName());
    }
}
