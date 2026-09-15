<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper\RandomHelper;

/**
 * Interface RandomHelperIdentifier.
 */
interface RandomHelperInterface
{
    /**
     * Generate random string.
     *
     * @return string
     */
    public function generate(int $length = 10, string $charlist = '0-9a-z');
}
