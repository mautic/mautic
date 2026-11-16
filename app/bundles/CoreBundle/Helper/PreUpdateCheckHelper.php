<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

use Mautic\CoreBundle\Helper\Update\PreUpdateChecks\AbstractPreUpdateCheck;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

class PreUpdateCheckHelper
{
    /**
     * @var AbstractPreUpdateCheck[]
     */
    private array $checks = [];

    /**
     * @param iterable<AbstractPreUpdateCheck> $checks
     */
    public function __construct(
        #[AutowireIterator('mautic.update_check')]
        iterable $checks = [],
    ) {
        foreach ($checks as $check) {
            $this->addCheck($check);
        }
    }

    private function addCheck(AbstractPreUpdateCheck $check): void
    {
        $this->checks[] = $check;
    }

    /**
     * Get all registered pre-update checks.
     *
     * @return AbstractPreUpdateCheck[]
     */
    public function getChecks(): array
    {
        return $this->checks;
    }
}
