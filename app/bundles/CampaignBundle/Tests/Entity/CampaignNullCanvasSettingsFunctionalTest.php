<?php

declare(strict_types=1);

namespace Mautic\CampaignBundle\Tests\Entity;

use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class CampaignNullCanvasSettingsFunctionalTest extends MauticMysqlTestCase
{
    /**
     * Checks that a campaign whose canvas_settings column holds a null value loads with empty canvas settings.
     *
     * @param string|null $storedValue the raw value written to the canvas_settings column
     */
    #[DataProvider('provideNullValues')]
    public function testCampaignWithNullCanvasSettingsLoads(?string $storedValue): void
    {
        $campaign = new Campaign();
        $campaign->setName('Campaign with null canvas settings');
        $this->em->persist($campaign);
        $this->em->flush();

        $this->em->getConnection()->executeStatement(
            'UPDATE '.MAUTIC_TABLE_PREFIX.'campaigns SET canvas_settings = :value WHERE id = :id',
            ['value' => $storedValue, 'id' => $campaign->getId()]
        );
        $this->em->clear();

        $loaded = $this->em->find(Campaign::class, $campaign->getId());

        $this->assertInstanceOf(Campaign::class, $loaded);
        $this->assertSame([], $loaded->getCanvasSettings());
        $this->assertFalse($loaded->hasOrphanEvents());
    }

    /**
     * Lists the stored values that read back as null.
     *
     * @return iterable<string, array{string|null}> the SQL NULL and the serialized null
     */
    public static function provideNullValues(): iterable
    {
        yield 'SQL NULL' => [null];
        yield 'serialized null' => ['N;'];
    }
}
