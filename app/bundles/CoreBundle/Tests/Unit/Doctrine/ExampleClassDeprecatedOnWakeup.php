<?php

namespace Mautic\CoreBundle\Tests\Unit\Doctrine;

final class ExampleClassDeprecatedOnWakeup
{
    /**
     * @phpstan-ignore-next-line
     */
    public $test = 'value';

    public function __wakeup(): void
    {
        trigger_error('This shape is deprecated.', E_USER_DEPRECATED);
    }
}
