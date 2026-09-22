<?php

namespace Mautic\CoreBundle\Tests\Unit\Doctrine;

final class ExampleClassWithProtectedProperty
{
    /**
     * @phpstan-ignore-next-line
     */
    private string $test = 'value';
}
