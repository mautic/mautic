<?php

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\PluginBundle\Tests\Entity;

use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Mautic\PluginBundle\Entity\IntegrationEntityRepository;
use PHPUnit\Framework\Assert;

/**
 * IntegrationRepository.
 */
class IntegrationEntityRepositoryTest extends MauticMysqlTestCase
{
    /**
     * @var string
     */
    private $prefix;

    /**
     * @var IntegrationEntityRepository
     */
    private $integrationEntityRepository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->prefix                      = $this->container->getParameter('mautic.db_table_prefix');
        $this->integrationEntityRepository = $this->em->getRepository('MauticPluginBundle:IntegrationEntity');
    }

    public function testThatGetIntegrationsEntityIdReturnsCorrectValues(): void
    {
        $now                 = new \DateTimeImmutable();
        $integrationEntityId = random_int(1, 1000);
        $internalEntityId    = random_int(1, 1000);

        $this->connection->insert($this->prefix.'integration_entity', [
            'date_added'            => $now->format('Y-m-d H:i:s'),
            'integration'           => 'someIntegration',
            'integration_entity'    => 'someIntegrationEntity',
            'integration_entity_id' => $integrationEntityId,
            'internal_entity'       => 'someInternalEntity',
            'internal_entity_id'    => $internalEntityId,
            'last_sync_date'        => null,
            'internal'              => 'someInternalValue',
        ]);

        $results = $this->integrationEntityRepository->getIntegrationsEntityId(
            'someIntegration',
            'someIntegrationEntity',
            'someInternalEntity',
            [$internalEntityId],
            null,
            null,
            false,
            0,
            0,
            null
        );

        Assert::assertCount(1, $results);
    }
}
