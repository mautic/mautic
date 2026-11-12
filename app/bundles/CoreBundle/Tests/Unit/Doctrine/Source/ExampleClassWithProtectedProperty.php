<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Doctrine\Source;

final class ExampleClassWithProtectedProperty
{
    /**
     * @phpstan-ignore-next-line
     */
    private string $test = 'value';
}
