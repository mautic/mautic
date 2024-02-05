<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Tests\Functional\Doctrine\Paginator;

use Mautic\CoreBundle\Doctrine\Paginator\SimplePaginator;
use Mautic\CoreBundle\Entity\IpAddress;
use Mautic\CoreBundle\Test\MauticMysqlTestCase;
use Symfony\Bridge\Doctrine\Middleware\Debug\DebugDataHolder;

class SimplePaginatorTest extends MauticMysqlTestCase
{
    /**
     * Enable debug for enabling DBAL query logger.
     *
     * @var array<string,mixed>
     */
    protected array $clientOptions = ['debug' => true];

    private DebugDataHolder $debugDataHolder;

    protected function setUp(): void
    {
        parent::setUp();

        $debugDataHolder = static::getContainer()->get('doctrine.debug_data_holder');
        \assert($debugDataHolder instanceof DebugDataHolder);
        $debugDataHolder->reset();

        $this->debugDataHolder = $debugDataHolder;
    }

    public function testPaginator(): void
    {
        $ipAddress1 = new IpAddress('10.0.0.1');
        $ipAddress2 = new IpAddress('10.0.0.2');
        $ipAddress3 = new IpAddress('10.0.0.3');

        $this->em->persist($ipAddress1);
        $this->em->persist($ipAddress2);
        $this->em->persist($ipAddress3);
        $this->em->flush();

        $repository = $this->em->getRepository(IpAddress::class);

        $paginator  = $repository->getEntities([
            'use_simple_paginator' => true,
            'start'                => 1,
            'limit'                => 5,
            'orderBy'              => $repository->getTableAlias().'.id',
        ]);

        $this->assertInstanceOf(SimplePaginator::class, $paginator);
        $this->assertCount(3, $paginator, 'The total count should be 3.');
        $this->assertCount(3, $paginator, 'The total count should be 3. Running it again to test lazy-loading.');
        $this->assertSame([
            $ipAddress2->getId() => $ipAddress2,
            $ipAddress3->getId() => $ipAddress3,
        ], iterator_to_array($paginator), 'Only 2 last records should be returned.');

        $prefix  = static::getContainer()->getParameter('mautic.db_table_prefix');
        $queries = $this->debugDataHolder->getData()['default'];

        $this->assertCount(5, $queries, 'There should be exactly 5 queries executed.');
        $this->assertMatchesRegularExpression("/^SELECT count\((.{2}_)\.id\) AS sclr_0 FROM {$prefix}ip_addresses \\1$/", $queries[3]['sql'], 'Simple paginator should not use either a DISTINCT keyword or sub-queries.');
        $this->assertMatchesRegularExpression("/^SELECT (.{2}_)\.id AS id_0, \\1\.ip_address AS ip_address_1, \\1\.ip_details AS ip_details_2 FROM {$prefix}ip_addresses \\1 ORDER BY \\1\.id ASC LIMIT 5 OFFSET 1$/", $queries[4]['sql'], 'Ordering and limit/offset have to be reflected.');
    }
}
