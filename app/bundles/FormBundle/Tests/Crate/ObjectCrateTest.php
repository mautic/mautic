<?php

declare(strict_types=1);

namespace Mautic\FormBundle\Tests\Crate;

use PHPUnit\Framework\TestCase;
use Mautic\FormBundle\Crate\ObjectCrate;

final class ObjectCrateTest extends TestCase
{
    public function testGetters(): void
    {
        $field = new ObjectCrate('contact', 'Contact');

        $this->assertSame('contact', $field->getKey());
        $this->assertSame('Contact', $field->getName());
    }
}
