<?php

namespace Mautic\CoreBundle\Tests\Unit\Doctrine;

final class ExampleClassWithPrivateProperty
{
    /**
     * @phpstan-ignore-next-line
     */
    private string $test = 'value';
}
