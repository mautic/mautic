<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\DependencyInjection;

use Mautic\EmailBundle\Stats\StatHelperContainer;
use PHPUnit\Framework\TestCase;

/**
 * The StatHelperPass collects every "mautic.email_stat_helper" tagged service into the StatHelperContainer.
 */
final class StatHelperPassTest extends TestCase
{
    /**
     * There are 6 stat helpers in the container, keep a small reserve for removed ones.
     */
    private const MINIMAL_STAT_HELPER_COUNT = 6;

    public function testStatHelpersAreAddedToStatHelperContainer(): void
    {
        $kernel = new TestKernel();
        $kernel->boot();

        $statHelperContainer = $kernel->getContainer()->get(StatHelperContainer::class);
        $this->assertInstanceOf(StatHelperContainer::class, $statHelperContainer);

        $helpers = (new \ReflectionProperty(StatHelperContainer::class, 'helpers'))
            ->getValue($statHelperContainer);

        $this->assertGreaterThanOrEqual(self::MINIMAL_STAT_HELPER_COUNT, count($helpers));
    }
}
