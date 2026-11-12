<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Unit\Doctrine\Source;

final class ExampleClassWithPublicProperty
{
    /**
     * @phpstan-ignore-next-line
     */
    public $test = 'value';
}
